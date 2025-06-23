<?php
// auth_middleware.php (en la raíz)

require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException; 

const LOGIN_URL = 'login.php';

define('JWT_SECRET', getenv('JWT_SECRET'));
define('JWT_ALGO', 'HS256');

// Función auxiliar para detectar si la solicitud es una API (AJAX/Fetch)
function isApiRequest() {
    // Comprobar el encabezado Accept
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        return true;
    }
    // Comprobar el encabezado X-Requested-With (común en peticiones AJAX)
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    return false;
}

// Función para enviar respuesta de error y terminar la ejecución
function sendAuthError($message, $httpCode = 401) {
    // Si ya se enviaron encabezados (raro en este punto, pero posible), no intentes cambiarlos.
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($httpCode); // Establecer el código de estado HTTP
    }
    echo json_encode(["success" => false, "message" => $message, "code" => $httpCode]);
    exit();
}

// Función para redirigir al login (solo para solicitudes HTML)
function redirectToLogin($message = '') {
    // Limpiar la cookie JWT al redirigir al login
    setcookie('jwt_token', '', time() - 3600, '/', '', false, true);
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
        // Si es una API, envía error JSON. Si es HTML, redirige.
        if (isApiRequest()) {
            sendAuthError('No hay token de sesión.');
        } else {
            redirectToLogin('No hay sesión iniciada.');
        }
    }

    try {
        $key = new Key(JWT_SECRET, JWT_ALGO);
        $decoded = JWT::decode($jwt, $key);

        $database_check = new Database();
        $conn_check = $database_check->getConnection();

        $stmt_check_revoked = $conn_check->prepare("SELECT revocado FROM tokens_sesion WHERE token_valor = ? LIMIT 1");
        $stmt_check_revoked->bindParam(1, $jwt, PDO::PARAM_STR);
        $stmt_check_revoked->execute();
        $is_revoked = $stmt_check_revoked->fetchColumn();
        $stmt_check_revoked->closeCursor();
        $database_check->closeConnection();

        if ($is_revoked === 1) {
            if (isApiRequest()) {
                sendAuthError('Sesión revocada.', 401);
            } else {
                redirectToLogin('Sesión revocada. Por favor, inicia sesión de nuevo.');
            }
        }

        // Verificar primer_login_requiere_cambio (solo para HTML, no para APIs que solo piden datos)
        if (!isApiRequest() && basename($_SERVER['PHP_SELF']) !== 'cambiar_contrasena_forzado.php') {
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
        
        return (array) $decoded->data;

    } catch (ExpiredException $e) {
        if (isApiRequest()) {
            sendAuthError('Sesión expirada.', 401);
        } else {
            redirectToLogin('Sesión expirada. Por favor, inicia sesión de nuevo.');
        }
    } catch (SignatureInvalidException $e) {
        error_log("Error de firma JWT: " . $e->getMessage());
        if (isApiRequest()) {
            sendAuthError('Firma de sesión inválida.', 401);
        } else {
            redirectToLogin('Firma de sesión inválida. Por favor, inicia sesión de nuevo.');
        }
    } catch (Exception $e) {
        error_log("Error de verificación JWT general: " . $e->getMessage());
        if (isApiRequest()) {
            sendAuthError('Token de sesión inválido.', 401);
        } else {
            redirectToLogin('Token de sesión inválido. Por favor, inicia sesión de nuevo.');
        }
    }
}

// Ejecutar la verificación si la página actual no es la de login
if (basename($_SERVER['PHP_SELF']) !== basename(LOGIN_URL)) {
    $user_data = verifyJwtToken();
}