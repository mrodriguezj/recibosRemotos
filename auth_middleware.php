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
use Firebase\JWT\ExpiredException; // Para manejar tokens expirados

// Definir la URL del login
const LOGIN_URL = 'login.php';

// Obtener el secreto de JWT del .env.
// Es CRÍTICO que JWT_SECRET en tu .env tenga un valor FUERTE y que el env_loader.php funcione.
// Si getenv() falla, esto podría resultar en un secreto vacío o un error si no se maneja adecuadamente.
define('JWT_SECRET', getenv('JWT_SECRET'));
define('JWT_ALGO', 'HS256');

// Función para redirigir al login
function redirectToLogin($message = '') {
    // Puedes pasar un mensaje de error o razón de redirección
    header('Location: ' . LOGIN_URL . ($message ? '?error=' . urlencode($message) : ''));
    exit();
}

// Función para verificar el token JWT
function verifyJwtToken() {
    // 1. Intentar obtener el token de las cookies (más seguro y común para JWT en navegadores)
    $jwt = null;
    if (isset($_COOKIE['jwt_token'])) {
        $jwt = $_COOKIE['jwt_token'];
    }
    // 2. Si no está en cookies, intentar de la cabecera Authorization (común para APIs o pruebas con Postman)
    if (!$jwt && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];
        }
    }

    if (!$jwt) {
        // No hay token, redirigir al login
        redirectToLogin('No hay sesión iniciada.');
    }

    try {
        // Decodificar y verificar el token
        // Usar Key::class para JWT v6+
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET, JWT_ALGO));

        // Puedes añadir más verificaciones aquí, como:
        // - Si el usuario_id del token existe en tu BD (ej: si el usuario fue eliminado)
        // - Si el token está en tu lista de tokens revocados (si implementas esa tabla y lógica)

        // Devolver los datos del token decodificado como array asociativo
        return (array) $decoded->data; // Asegúrate de acceder a los datos dentro del 'data' payload

    } catch (ExpiredException $e) {
        // Token expirado
        // Limpiar la cookie para asegurar que el usuario no quede en un bucle si el JS no lo hace
        setcookie('jwt_token', '', time() - 3600, '/');
        redirectToLogin('Sesión expirada. Por favor, inicia sesión de nuevo.');
    } catch (Exception $e) {
        // Otro error de verificación (firma inválida, token corrupto, etc.)
        error_log("Error de verificación JWT: " . $e->getMessage() . " - Token: " . ($jwt ?? 'N/A')); // Loggear el error completo
        // Limpiar la cookie también en caso de token inválido
        setcookie('jwt_token', '', time() - 3600, '/');
        redirectToLogin('Token de sesión inválido. Por favor, inicia sesión de nuevo.');
    }
}

// Ejecutar la verificación si la página actual no es la de login
// Basename para obtener solo el nombre del archivo (ej: index.php)
if (basename($_SERVER['PHP_SELF']) !== basename(LOGIN_URL)) {
    // $user_data contendrá los datos del usuario del token si es válido
    // o redirigirá al login si no lo es
    $user_data = verifyJwtToken();

    // Opcional: Re-validar si el usuario requiere cambio de contraseña y redirigir
    // Esto es especialmente útil si la sesión se mantiene activa pero el administrador
    // marca la cuenta para un cambio de contraseña forzado después del login inicial.
    // (Solo haz esto si NO estás en cambiar_contrasena_forzado.php ya)
    if (basename($_SERVER['PHP_SELF']) !== 'cambiar_contrasena_forzado.php') {
        $database_check = new Database(); // Crear una nueva instancia de Database
        $conn_check = null;
        try {
            $conn_check = $database_check->getConnection();
            $stmt_check = $conn_check->prepare("SELECT primer_login_requiere_cambio FROM usuarios WHERE usuario_id = ?");
            $stmt_check->bindParam(1, $user_data['user_id'], PDO::PARAM_INT);
            $stmt_check->execute();
            $needs_change = $stmt_check->fetchColumn();
            $stmt_check->closeCursor();

            if ($needs_change && $needs_change == 1) { // MySQL devuelve 1 para TRUE
                redirectToLogin('Debes cambiar tu contraseña.'); // O redirigir directamente a cambiar_contrasena_forzado.php
                                                                // pero redirección via login.php con error es más fácil de manejar.
            }
        } catch (Exception $e) {
            error_log("Error al verificar primer_login_requiere_cambio en middleware: " . $e->getMessage());
            redirectToLogin('Error interno de autenticación.');
        } finally {
            if ($conn_check) {
                $database_check->closeConnection();
            }
        }
    }
}