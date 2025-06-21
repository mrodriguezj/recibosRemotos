<?php
// api/pagos/buscar.php

// AJUSTA ESTAS RUTAS SEGÚN LA UBICACIÓN REAL DE TUS ARCHIVOS
// Asume que Database.php está en la raíz de tu proyecto (dos niveles arriba de api/pagos/)
//require_once dirname(__DIR__, 2) . '/Database.php';
require_once __DIR__ . '/database.php';
header('Content-Type: application/json');

// Asegurar que solo se procesen solicitudes POST (o GET, si la búsqueda fuera GET)
// Para el formulario de búsqueda, POST es más común al enviar datos complejos.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit();
}

$database = new Database();
$conn = null;
$response = ["success" => false, "message" => "", "data" => null];

try {
    $conn = $database->getConnection();

    // Obtener los datos JSON del cuerpo de la solicitud
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $id_lote = isset($data['id_lote']) && $data['id_lote'] !== '' ? (int)$data['id_lote'] : 0; // Usar 0 si está vacío
    $pago_id = isset($data['pago_id']) && $data['pago_id'] !== '' ? (int)$data['pago_id'] : 0;   // Usar 0 si está vacío

    // Validaciones básicas de entrada
    if ($id_lote <= 0 && $pago_id <= 0) {
        $response["message"] = "Debe proporcionar al menos un ID de Lote o un ID de Pago.";
        echo json_encode($response);
        exit();
    }

    // Llamar al procedimiento almacenado para buscar el pago
    // Los 0 se traducirán a NULL en el SP si pides NULL, o el SP está diseñado para 0 como 'no proporcionado'
    $stmt = $conn->prepare("CALL sp_buscar_pago_detalle(?, ?)");
    $stmt->bindParam(1, $id_lote, PDO::PARAM_INT);
    $stmt->bindParam(2, $pago_id, PDO::PARAM_INT);
    $stmt->execute();
    $pago_detalle = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor(); // CRÍTICO para liberar el cursor

    if ($pago_detalle) {
        $response["success"] = true;
        $response["message"] = "Comprobante encontrado.";
        $response["data"] = $pago_detalle;
    } else {
        $response["message"] = "Comprobante no encontrado o no está en estado 'Vigente'.";
        $response["data"] = null;
    }

} catch (Exception $e) {
    $response["message"] = "Error en el servidor al buscar comprobante: " . $e->getMessage();
    // Loggear $e en producción
} finally {
    if ($conn) {
        $database->closeConnection();
    }
    echo json_encode($response);
}
?>