<?php
// api/clientes/listar.php

// AJUSTA ESTAS RUTAS SEGÚN LA UBICACIÓN REAL DE TUS ARCHIVOS
// Asume que Database.php está en la raíz de tu proyecto o en el nivel de api/
// require_once dirname(__DIR__) . '/Database.php'; // Si Database.php está en la raíz
//require_once dirname(__DIR__, 2) . '/Database.php'; // Si Database.php está en la raíz y este script en api/clientes/
require_once __DIR__ . '/database.php';

// O si Database.php está en 'config/' y este script en 'api/clientes/':
// require_once dirname(__DIR__, 2) . '/config/Database.php';


header('Content-Type: application/json');

$database = new Database();
$conn = null;
$response = ["success" => false, "message" => "", "data" => []];

try {
    $conn = $database->getConnection();

    // Llamar al procedimiento almacenado
    $stmt = $conn->prepare("CALL sp_obtener_clientes_simples()");
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor(); // CRÍTICO para permitir futuras operaciones PDO

    $response["success"] = true;
    $response["message"] = "Clientes obtenidos exitosamente.";
    $response["data"] = $clientes;

} catch (Exception $e) {
    $response["message"] = "Error al obtener clientes: " . $e->getMessage();
    // En un entorno de producción, loggear $e->getMessage()
} finally {
    if ($conn) {
        $database->closeConnection();
    }
    echo json_encode($response);
}
?>