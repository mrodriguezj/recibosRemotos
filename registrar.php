<?php
// api/pagos/registrar.php

// AJUSTA ESTAS RUTAS SEGÚN LA UBICACIÓN REAL DE TUS ARCHIVOS
// Asume que Database.php está en la raíz de tu proyecto (dos niveles arriba de api/pagos/)
//require_once dirname(__DIR__, 2) . '/Database.php';
require_once __DIR__ . '/database.php';

// Si tu API estuviera protegida, necesitarías el auth_middleware.php aquí
// Por ahora, lo omitimos según lo acordado, pero se añadiría así:
// require_once dirname(__DIR__, 2) . '/auth_middleware.php'; 

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
    // Cuando la API esté protegida, este ID vendrá del token JWT ($user_data del auth_middleware.php)
    // Por ahora, usa un ID de usuario de prueba (ej: el ID del primer admin que registraste)
    $usuario_realizador_id = 1; // ID de usuario de prueba (AJUSTAR ESTO A UN ID REAL DE TU TABLA 'usuarios')
    // EJEMPLO de cómo se obtendría si el auth_middleware.php estuviera incluido:
    // global $user_data; // Hacer que $user_data del middleware sea accesible
    // if (!isset($user_data['user_id'])) {
    //     throw new Exception("Usuario no autenticado o ID de usuario no disponible en el token.");
    // }
    // $usuario_realizador_id = $user_data['user_id'];


    // --- Validar y sanear datos recibidos ---
    $id_lote = isset($data['id_lote']) ? (int)$data['id_lote'] : 0;
    $cliente_id = isset($data['cliente_id']) ? (int)$data['cliente_id'] : 0;
    $fecha_esperada_pago = isset($data['fecha_esperada_pago']) ? $data['fecha_esperada_pago'] : '';
    $fecha_pago = isset($data['fecha_pago']) ? $data['fecha_pago'] : '';
    $categoria_pago = isset($data['categoria_pago']) ? $data['categoria_pago'] : '';
    $monto_pagado = isset($data['monto_pagado']) ? (float)$data['monto_pagado'] : 0.0;
    $metodo_pago = isset($data['metodo_pago']) ? $data['metodo_pago'] : '';
    $observaciones_pago = isset($data['observaciones_pago']) && $data['observaciones_pago'] !== null ? $data['observaciones_pago'] : null;


    // Validaciones de negocio (server-side, CRÍTICO)
    $errors = [];

    if ($id_lote <= 0) {
        $errors[] = "El 'ID Lote' es requerido y debe ser un número entero positivo.";
    }
    if ($cliente_id <= 0) {
        $errors[] = "El 'Cliente' es requerido y debe ser válido.";
    }
    // Validar existencia del cliente en la BD
    $stmt_cliente_exists = $conn->prepare("SELECT COUNT(*) FROM clientes WHERE id_cliente = ? AND estatus = 'Activo'");
    $stmt_cliente_exists->bindParam(1, $cliente_id, PDO::PARAM_INT);
    $stmt_cliente_exists->execute();
    if ($stmt_cliente_exists->fetchColumn() == 0) {
        $errors[] = "El cliente seleccionado no existe o está inactivo.";
    }
    $stmt_cliente_exists->closeCursor();

    // Validar formato de fechas
    if (empty($fecha_esperada_pago) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_esperada_pago) || !strtotime($fecha_esperada_pago)) {
        $errors[] = "La 'Fecha Esperada de Pago' es requerida y debe ser una fecha válida (YYYY-MM-DD).";
    }
    if (empty($fecha_pago) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_pago) || !strtotime($fecha_pago)) {
        $errors[] = "La 'Fecha de Pago Real' es requerida y debe ser una fecha válida (YYYY-MM-DD).";
    }
    // Puedes añadir validaciones de lógica de fechas, ej: fecha_pago no futura, fecha_pago >= fecha_esperada_pago
    // if (strtotime($fecha_pago) > time()) { $errors[] = "La Fecha de Pago Real no puede ser futura."; }
    // if (strtotime($fecha_pago) < strtotime($fecha_esperada_pago)) { $errors[] = "La Fecha de Pago Real no puede ser anterior a la Fecha Esperada de Pago."; }

    $allowed_categorias = ['Enganche', 'Contado', 'Mensualidad', 'Anualidad'];
    if (empty($categoria_pago) || !in_array($categoria_pago, $allowed_categorias)) {
        $errors[] = "La 'Categoría de Pago' es requerida y debe ser una opción válida.";
    }
    if ($monto_pagado <= 0) {
        $errors[] = "El 'Monto Pagado' es requerido y debe ser un valor positivo.";
    }
    // Validar método de pago estrictamente si es ENUM
    if ($metodo_pago !== 'EFECTIVO') {
        $errors[] = "El 'Método de Pago' debe ser EFECTIVO.";
    }

    if (!empty($errors)) {
        $response["message"] = implode("\n", $errors);
        echo json_encode($response);
        exit();
    }

    // --- Llamar al Procedimiento Almacenado para registrar el pago ---
    $stmt = $conn->prepare("CALL sp_registrar_pago(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Bind de parámetros
    $stmt->bindParam(1, $id_lote, PDO::PARAM_INT);
    $stmt->bindParam(2, $cliente_id, PDO::PARAM_INT);
    $stmt->bindParam(3, $fecha_esperada_pago, PDO::PARAM_STR);
    $stmt->bindParam(4, $fecha_pago, PDO::PARAM_STR);
    $stmt->bindParam(5, $categoria_pago, PDO::PARAM_STR);
    $stmt->bindParam(6, $monto_pagado, PDO::PARAM_STR); // PDO::PARAM_STR para DECIMAL es común
    $stmt->bindParam(7, $metodo_pago, PDO::PARAM_STR);
    $stmt->bindParam(8, $observaciones_pago, $observaciones_pago === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(9, $usuario_realizador_id, PDO::PARAM_INT); // ID del usuario del JWT

    $stmt->execute();

    // El SP devuelve el ID del pago insertado
    $result_sp = $stmt->fetch(PDO::FETCH_ASSOC);
    $last_pago_id = $result_sp ? $result_sp['pago_id'] : null;

    $response["success"] = true;
    $response["message"] = "Comprobante de pago registrado exitosamente. ID de Pago: " . $last_pago_id;

} catch (PDOException $e) {
    // Capturar errores de PDO, incluyendo los lanzados por SIGNAL SQLSTATE del SP
    $error_message = $e->getMessage();
    $response["message"] = "Error de base de datos al registrar pago: " . $error_message;
    // Considera loggear $e para más detalles en producción
} catch (Exception $e) {
    // Capturar cualquier otra excepción
    $response["message"] = "Error inesperado en el servidor: " . $e->getMessage();
} finally {
    if ($conn) {
        $database->closeConnection();
    }
    echo json_encode($response);
}