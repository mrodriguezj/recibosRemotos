<?php
// api/clientes.php

// AJUSTA ESTA RUTA SEGÚN LA UBICACIÓN REAL DE TU Database.php
require_once __DIR__ . '/database.php';


header('Content-Type: application/json'); // Indicar que la respuesta es JSON

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
    $data = json_decode($input, true); // true para obtener un array asociativo

    // --- Validar y sanear datos recibidos ---
    // NOTA: La validación del lado del servidor es CRÍTICA.

    $nombres = isset($data['nombres']) ? trim($data['nombres']) : '';
    $apellido_paterno = isset($data['apellido_paterno']) ? trim($data['apellido_paterno']) : '';
    $apellido_materno = isset($data['apellido_materno']) && $data['apellido_materno'] !== null ? trim($data['apellido_materno']) : null;
    $correo_electronico = isset($data['correo_electronico']) ? trim($data['correo_electronico']) : '';
    $telefono = isset($data['telefono']) ? trim($data['telefono']) : '';
    $curp = isset($data['curp']) ? trim($data['curp']) : '';
    $rfc = isset($data['rfc']) && $data['rfc'] !== null ? trim($data['rfc']) : null;
    $ine = isset($data['ine']) ? trim($data['ine']) : '';
    $direccion = isset($data['direccion']) && $data['direccion'] !== null ? trim($data['direccion']) : null;

    // Convertir a mayúsculas
    $nombres = strtoupper($nombres);
    $apellido_paterno = strtoupper($apellido_paterno);
    $apellido_materno = ($apellido_materno !== null) ? strtoupper($apellido_materno) : null;
    $curp = strtoupper($curp);
    $rfc = ($rfc !== null) ? strtoupper($rfc) : null;
    $ine = strtoupper($ine);

    // Validaciones de negocio con REGEX actualizadas
    $errors = [];

    if (empty($nombres) || !preg_match('/^[A-ZÁÉÍÓÚÜÑ\s]+$/u', $nombres)) {
        $errors[] = "El campo 'Nombre(s)' es requerido y solo debe contener letras y espacios.";
    }
    if (empty($apellido_paterno) || !preg_match('/^[A-ZÁÉÍÓÚÜÑ\s]+$/u', $apellido_paterno)) {
        $errors[] = "El campo 'Apellido Paterno' es requerido y solo debe contener letras y espacios.";
    }
    if ($apellido_materno !== null && !preg_match('/^[A-ZÁÉÍÓÚÜÑ\s]*$/u', $apellido_materno)) {
        $errors[] = "El campo 'Apellido Materno' solo debe contener letras y espacios.";
    }
    if (empty($correo_electronico) || !filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El campo 'Correo Electrónico' es requerido y debe ser un formato válido.";
    }
    if (empty($telefono) || !preg_match('/^[0-9]{10}$/', $telefono)) {
        $errors[] = "El campo 'Teléfono' es requerido y debe ser de 10 dígitos numéricos.";
    }
    
    // --- REGEX DE CURP, RFC, INE AJUSTADAS ---
    // CURP: 18 caracteres alfanuméricos (letras y números)
    if (empty($curp) || !preg_match('/^[A-Z0-9]{18}$/', $curp)) {
        $errors[] = "El campo 'CURP' es requerido y debe tener 18 caracteres alfanuméricos (letras y números).";
    }
    // RFC: 12 o 13 caracteres alfanuméricos (letras y números)
    // El & y Ñ no se consideran en la regex simplificada, solo A-Z0-9. Si los necesitas, reincorporarlos.
    if ($rfc !== null && !empty($rfc) && !preg_match('/^[A-Z0-9]{12,13}$/', $rfc)) {
        $errors[] = "El campo 'RFC' es inválido (12 o 13 caracteres alfanuméricos).";
    }
    // INE: 15 caracteres alfanuméricos (letras y números)
    if (empty($ine) || !preg_match('/^[A-Z0-9]{15}$/', $ine)) {
        $errors[] = "El campo 'INE' es requerido y debe tener 15 caracteres alfanuméricos (letras y números).";
    }
    // --- FIN REGEX AJUSTADAS ---

    if (!empty($errors)) {
        $response["message"] = implode("\n", $errors);
        echo json_encode($response);
        exit();
    }

    // --- Llamar al Procedimiento Almacenado ---
    $stmt = $conn->prepare("CALL sp_registrar_cliente(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Bind de parámetros. Usamos null si el valor es null, de lo contrario PDO::PARAM_STR
    $stmt->bindParam(1, $nombres, PDO::PARAM_STR);
    $stmt->bindParam(2, $apellido_paterno, PDO::PARAM_STR);
    $stmt->bindParam(3, $apellido_materno, $apellido_materno === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(4, $correo_electronico, PDO::PARAM_STR);
    $stmt->bindParam(5, $telefono, PDO::PARAM_STR);
    $stmt->bindParam(6, $curp, PDO::PARAM_STR);
    $stmt->bindParam(7, $rfc, $rfc === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(8, $ine, PDO::PARAM_STR);
    $stmt->bindParam(9, $direccion, $direccion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

    $stmt->execute();

    // El SP devuelve el LAST_INSERT_ID()
    $result_sp = $stmt->fetch(PDO::FETCH_ASSOC);
    $last_id = $result_sp ? $result_sp['id_cliente'] : null;

    $response["success"] = true;
    $response["message"] = "Cliente registrado exitosamente. ID: " . $last_id;

} catch (PDOException $e) {
    // Capturar errores de PDO, incluyendo los lanzados por SIGNAL SQLSTATE del SP
    $error_message = $e->getMessage();

    if (strpos($error_message, 'El correo electrónico ya está registrado') !== false ||
        strpos($error_message, 'La CURP ya está registrada') !== false ||
        strpos($error_message, 'El INE ya está registrado') !== false) {
        $response["message"] = "Error de registro: " . $error_message;
    } else {
        $response["message"] = "Error de base de datos al registrar cliente: " . $error_message;
        // Considera loggear $e para más detalles en producción
    }

} catch (Exception $e) {
    $response["message"] = "Error inesperado en el servidor: " . $e->getMessage();
} finally {
    if ($conn) {
        $database->closeConnection();
    }
    echo json_encode($response);
}