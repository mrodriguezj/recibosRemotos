<?php
// test_db_connection.php

// Usamos dirname(__DIR__) para construir la ruta relativa de forma segura
// ASUMIENDO TODOS LOS ARCHIVOS EN EL MISMO DIRECTORIO TEMPORALMENTE
require_once __DIR__ . '/database.php';

// Establecer cabeceras para JSON si lo deseas, pero para una prueba HTML es mejor HTML
// header('Content-Type: application/json');

echo "<h1>Test de Conexión a la Base de Datos</h1>"; // Para una salida legible en navegador

$database = new Database();
$response = ["success" => false, "message" => "", "tables" => []];
$conn_successful = false; // Bandera para saber si la conexión fue exitosa

try {
    $conn = $database->getConnection();
    $conn_successful = true; // La conexión fue exitosa
    $response["success"] = true;
    $response["message"] = "Conexión a la base de datos exitosa.";

    echo "<p style='color: green; font-weight: bold;'>&#10004; Conexión a la base de datos exitosa.</p>"; // Salida HTML

    // Obtener y listar las tablas
    echo "<h2>Tablas en la base de datos:</h2>"; // Salida HTML
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($tables) > 0) {
        $response["tables"] = $tables;
        echo "<ul>"; // Salida HTML
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>"; // Salida HTML
        }
        echo "</ul>"; // Salida HTML
    } else {
        echo "<p>No se encontraron tablas en la base de datos.</p>"; // Salida HTML
    }

} catch (Exception $e) {
    // Captura la excepción lanzada desde Database.php
    $response["success"] = false;
    $response["message"] = "Error de conexión o al obtener tablas: " . $e->getMessage();

    echo "<p style='color: red; font-weight: bold;'>&#10060; Error:</p>"; // Salida HTML
    echo "<p style='color: red;'>Detalles: " . htmlspecialchars($e->getMessage()) . "</p>"; // Salida HTML
} finally {
    // CORRECCIÓN AQUÍ: Usar el método público closeConnection()
    // Y verificar si la conexión fue establecida antes de intentar cerrarla
    if ($conn_successful) {
        $database->closeConnection();
        echo "<p>Conexión a la base de datos cerrada.</p>"; // Salida HTML
    }
    // Opcional: Si quieres la salida JSON para una API, quita las líneas HTML y deja esto:
    // echo json_encode($response);
}

?>