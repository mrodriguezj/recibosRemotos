<?php
// api/pagos/cancelar.php

// AJUSTA ESTA RUTA SEGÚN LA UBICACIÓN REAL DE TU Database.php
// SI 'cancelar.php' ESTÁ EN 'api/pagos/' y 'Database.php' ESTÁ EN LA RAÍZ DEL PROYECTO
//require_once dirname(__DIR__, 2) . '/Database.php'; 
require_once __DIR__ . '/database.php';
header('Content-Type: application/json');

// Asegurar que solo se procesen solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit();
}

$database = new Database();
$conn = null;
$response = ["success" => false, "message" => ""];

try {
    $conn = $database->getConnection();

    // Obtener los datos JSON del cuerpo de la solicitud
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // --- Obtener el ID del usuario que realiza la acción (simulado por ahora) ---
    $usuario_realizador_id = 1; // ID de usuario de prueba (AJUSTAR ESTO A UN ID REAL DE TU TABLA 'usuarios')


    // --- Validar y sanear datos recibidos ---
    $pago_id = isset($data['pago_id']) ? (int)$data['pago_id'] : 0;
    $motivo_cancelacion = isset($data['motivo_cancelacion']) ? trim($data['motivo_cancelacion']) : '';
    $id_lote_context = isset($data['id_lote']) ? (int)$data['id_lote'] : 0;


    // --- DEPURACIÓN PHP ---
    // ESTAS LÍNEAS SE EJECUTARÁN SIEMPRE QUE LLEGUE UNA SOLICITUD POST Y ANTES DE LA VALIDACIÓN
    error_log("DEBUG PHP - Motivo recibido (rawValue): '" . $data['motivo_cancelacion'] . "'"); // Ver el valor antes del trim
    error_log("DEBUG PHP - Motivo recibido (trimmedValue): '" . $motivo_cancelacion . "'");
    error_log("DEBUG PHP - Longitud (strlen): " . strlen($motivo_cancelacion));
    // --- FIN DEPURACIÓN PHP ---


    // Validaciones de negocio (server-side)
    $errors = [];

    if ($pago_id <= 0) {
        $errors[] = "El 'ID de Pago' es requerido y debe ser un número entero positivo.";
    }
    
    // --- NUEVA LÓGICA DE VALIDACIÓN SIN MBSTRING ---
    // 1. Verificar si está vacío después del trim
    if (empty($motivo_cancelacion)) {
        $errors[] = "El 'Motivo de Cancelación' es requerido.";
    }
    // 2. Verificar la longitud mínima en bytes.
    // Aunque no es perfecto para caracteres UTF-8, es la mejor opción sin mbstring.
    // El usuario deberá escribir un poco más si usa tildes o ñ.
    if (strlen($motivo_cancelacion) < 10 && !empty($motivo_cancelacion)) { // Solo aplica si NO está vacío
        $errors[] = "El 'Motivo de Cancelación' debe tener al menos 10 caracteres (contando bytes).";
    }
    // --- FIN NUEVA LÓGICA ---

    if (!empty($errors)) {
        $response["message"] = implode("\n", $errors);
        echo json_encode($response);
        exit();
    }

    // --- Llamar al Procedimiento Almacenado para cancelar el pago ---
    $stmt = $conn->prepare("CALL sp_cancelar_pago(?, ?, ?)");
    
    // Bind de parámetros
    $stmt->bindParam(1, $pago_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $motivo_cancelacion, PDO::PARAM_STR);
    $stmt->bindParam(3, $usuario_realizador_id, PDO::PARAM_INT);

    $stmt->execute();

    // El SP devuelve 'status' y 'message' si la operación fue exitosa
    $result_sp = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result_sp && $result_sp['status'] === 'success') {
        $response["success"] = true;
        $response["message"] = $result_sp['message'];
    } else {
        $response["message"] = "No se pudo completar la cancelación. Contacta a soporte.";
    }

} catch (PDOException $e) {
    $error_message = $e->getMessage();
    if (strpos($error_message, 'El pago especificado no existe.') !== false ||
        strpos($error_message, 'El pago ya se encuentra en estado "Cancelado".') !== false) {
        $response["message"] = $error_message; 
    } else {
        $response["message"] = "Error de base de datos al cancelar el pago: " . $error_message;
        // En producción: Loggear $e->getMessage() y mostrar un mensaje genérico.
    }
} catch (Exception $e) {
    $response["message"] = "Error inesperado en el servidor: " . $e->getMessage();
} finally {
    if ($conn) {
        $database->closeConnection();
    }
    echo json_encode($response);
    exit();
}