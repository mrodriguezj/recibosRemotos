<?php
// api/auth/logout.php

// AJUSTA ESTAS RUTAS SEGÚN LA UBICACIÓN REAL DE TUS ARCHIVOS
// Asume que Database.php, env_loader.php y vendor/autoload.php están en la raíz del proyecto
//require_once dirname(__DIR__, 2) . '/env_loader.php'; // Sube dos niveles desde api/auth/ a la raíz
//require_once dirname(__DIR__, 2) . '/database.php';   // Sube dos niveles desde api/auth/ a la raíz
//require_once dirname(__DIR__, 2) . '/vendor/autoload.php'; // Sube dos niveles desde api/auth/ a la raíz

require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException; 

header('Content-Type: application/json');

// Permitir solicitudes OPTIONS para CORS Preflight (si aplica en producción)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *'); // Ajustar a tu dominio en producción
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit();
}

// Asegurar que solo se procesen solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit();
}

$database = new Database();
$conn = null;
$response = ["success" => false, "message" => ""];

// Obtener el secreto de JWT (debe ser el mismo que en login.php y auth_middleware.php)
define('JWT_SECRET', getenv('JWT_SECRET'));
define('JWT_ALGO', 'HS256');

try {
    $conn = $database->getConnection();

    // 1. Obtener el token JWT de la cookie
    $jwt = null;
    if (isset($_COOKIE['jwt_token'])) {
        $jwt = $_COOKIE['jwt_token'];
    }

    if (!$jwt) {
        // No hay token que revocar, pero lo tratamos como logout exitoso para el cliente
        $response["success"] = true;
        $response["message"] = "No hay sesión activa para cerrar.";
        // Limpiar la cookie de todas formas por si acaso (aunque no la haya, no hace daño)
        setcookie('jwt_token', '', time() - 3600, '/', '', false, true); // Expira en el pasado, HttpOnly
        echo json_encode($response);
        exit();
    }

    // 2. Intentar decodificar el token para obtener su valor y payload
    $decoded_token = null;
    try {
        // Decodificar el token SIN verificar expiración estricta en este punto
        $key = new Key(JWT_SECRET, JWT_ALGO);
        $decoded_token = JWT::decode($jwt, $key); // Esto aún verifica la firma
    } catch (ExpiredException $e) {
        // El token ya está expirado, aún así, lo revocamos si existe en BD (si es que la BD lo registró)
        error_log("DEBUG: Token expirado al intentar logout. Se procederá a intentar revocarlo en BD.");
        $response["message"] = "Sesión expirada. Se ha cerrado correctamente."; // Mensaje para el usuario
    } catch (Exception $e) {
        // Token inválido (firma, formato), no se puede revocar en BD si no es válido
        setcookie('jwt_token', '', time() - 3600, '/', '', false, true); // Limpiar cookie localmente
        throw new Exception("Token inválido al intentar logout: " . $e->getMessage()); // Lanzar para el catch general
    }

    // 3. Llamar al procedimiento almacenado para revocar el token en la BD
    if ($decoded_token) { // Solo si pudo ser decodificado (firma válida)
        $stmt = $conn->prepare("CALL sp_revocar_token(?)");
        $stmt->bindParam(1, $jwt, PDO::PARAM_STR);
        $stmt->execute();
        $rows_affected_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($rows_affected_result && $rows_affected_result['rows_affected'] > 0) {
            $response["message"] = "Sesión cerrada exitosamente.";
        } else {
            // El token no se encontró en la BD o ya estaba revocado.
            // Para el usuario, es un éxito de logout si la cookie se va a limpiar.
            $response["message"] = "Sesión ya inactiva o no encontrada en el registro de tokens.";
        }
    } else {
        // Si no se pudo decodificar el token (ej. firma inválida), no se intenta revocar en BD.
        $response["message"] = "No se pudo decodificar el token para revocar en BD. Sesión cerrada localmente.";
    }

    $response["success"] = true; // El logout desde el cliente siempre se reporta como "exitoso" si la cookie se limpia

} catch (Exception $e) {
    $response["success"] = false;
    $response["message"] = "Error en el servidor al cerrar sesión: " . $e->getMessage();
    error_log("ERROR en logout.php: " . $e->getMessage()); // Loggear el error completo
} finally {
    // 4. Eliminar la cookie JWT del navegador (siempre, independientemente del éxito en BD)
    setcookie('jwt_token', '', time() - 3600, '/', '', false, true); // Expira en el pasado, HttpOnly

    if ($conn) {
        $database->closeConnection();
    }
    echo json_encode($response);
    exit(); // Asegurar que el script termina aquí
}