<?php
// auth_middleware.php

// Cargar variables de entorno (asume que env_loader.php está en el mismo directorio)
require_once __DIR__ . '/env_loader.php';

// Cargar clase de base de datos (asume que Database.php está en el mismo directorio)
require_once __DIR__ . '/database.php';

// Cargar la librería JWT (Composer) (asume que vendor/autoload.php está en el mismo directorio)
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException; // Añadir para errores de firma

// Definir la URL del login
const LOGIN_URL = 'login.php';

// Obtener el secreto de JWT del .env.
define('JWT_SECRET', getenv('JWT_SECRET'));
define('JWT_ALGO', 'HS256');

// Función para redirigir al login
function redirectToLogin($message = '') {
    // Limpiar la cookie JWT al redirigir al login (por si el token es inválido/expirado/revocado)
    setcookie('jwt_token', '', time() - 3600, '/', '', false, true); // Expira en el pasado, HttpOnly
    header('Location: ' . LOGIN_URL . ($message ? '?error=' . urlencode($message) : ''));
    exit();
}

// Función para verificar el token JWT
function verifyJwtToken() {
    $jwt = null;
    if (isset($_COOKIE['jwt_token'])) {
        $jwt = $_COOKIE['jwt_token'];
    }
    if (!$jwt && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];
        }
    }

    if (!$jwt) {
        redirectToLogin('No hay sesión iniciada.');
    }

    try {
        $key = new Key(JWT_SECRET, JWT_ALGO);
        $decoded = JWT::decode($jwt, $key);

        // --- INICIO: VERIFICACIÓN ADICIONAL EN BASE DE DATOS (REVOCACIÓN) ---
        $database_check = new Database(); // Nueva instancia para la verificación
        $conn_check = $database_check->getConnection();

        $stmt_check_revoked = $conn_check->prepare("SELECT revocado FROM tokens_sesion WHERE token_valor = ? LIMIT 1");
        $stmt_check_revoked->bindParam(1, $jwt, PDO::PARAM_STR);
        $stmt_check_revoked->execute();
        $is_revoked = $stmt_check_revoked->fetchColumn(); // Obtiene el valor de 'revocado'
        $stmt_check_revoked->closeCursor();
        $database_check->closeConnection(); // Cerrar conexión de verificación

        if ($is_revoked === 1) { // tinyint(1) en MySQL devuelve 1 para TRUE, 0 para FALSE
            redirectToLogin('Sesión revocada. Por favor, inicia sesión de nuevo.');
        }
        // --- FIN: VERIFICACIÓN ADICIONAL EN BASE DE DATOS ---

        // Verificar primer_login_requiere_cambio
        // Este bloque es importante si un admin marca la cuenta para un cambio forzado DESPUÉS del login inicial
        // y el token sigue siendo válido pero ahora requiere cambio.
        // Solo lo hacemos si NO estamos ya en la página de cambio de contraseña forzado.
        if (basename($_SERVER['PHP_SELF']) !== 'cambiar_contrasena_forzado.php') {
            $database_check_pass = new Database();
            $conn_check_pass = $database_check_pass->getConnection();
            $stmt_check_pass = $conn_check_pass->prepare("SELECT primer_login_requiere_cambio FROM usuarios WHERE usuario_id = ?");
            $stmt_check_pass->bindParam(1, $decoded->data->user_id, PDO::PARAM_INT);
            $stmt_check_pass->execute();
            $needs_change = $stmt_check_pass->fetchColumn();
            $stmt_check_pass->closeCursor();
            $database_check_pass->closeConnection();

            if ($needs_change === 1) {
                redirectToLogin('Debes cambiar tu contraseña.');
            }
        }


        // Devolver los datos del token decodificado como array asociativo
        return (array) $decoded->data;

    } catch (ExpiredException $e) {
        redirectToLogin('Sesión expirada. Por favor, inicia sesión de nuevo.');
    } catch (SignatureInvalidException $e) {
        error_log("Error de firma JWT: " . $e->getMessage()); // Log para errores de firma
        redirectToLogin('Firma de sesión inválida. Por favor, inicia sesión de nuevo.');
    } catch (Exception $e) {
        error_log("Error de verificación JWT general: " . $e->getMessage());
        redirectToLogin('Token de sesión inválido. Por favor, inicia sesión de nuevo.');
    }
}

// Ejecutar la verificación si la página actual no es la de login
if (basename($_SERVER['PHP_SELF']) !== basename(LOGIN_URL)) {
    $user_data = verifyJwtToken(); // Si es válido, $user_data estará aquí
}