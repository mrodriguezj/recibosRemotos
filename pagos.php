<?php
// api/pagos.php

// AJUSTA ESTA RUTA SEGÚN LA UBICACIÓN REAL DE TU Database.php
// Si Database.php está en el mismo directorio que api/pagos.php:
require_once __DIR__ . '/database.php';
// SI Database.php está en la carpeta 'config/' y api/pagos.php está en 'api/':
// require_once dirname(__DIR__) . '/config/Database.php';


header('Content-Type: application/json');

$database = new Database();
$conn = null;
$response = ["success" => false, "message" => "", "data" => [], "total_records" => 0, "total_pages" => 0, "current_page" => 1];

try {
    $conn = $database->getConnection();

    // 1. Recibir y sanear parámetros GET
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sortColumn = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'fecha_pago';
    $sortDirection = isset($_GET['sort_direction']) ? strtoupper($_GET['sort_direction']) : 'DESC';

    // 2. Llamar al Procedimiento Almacenado para el CONTEO TOTAL
    $stmt_count = $conn->prepare("CALL sp_contar_pagos_filtrados(:p_search_term)");
    $stmt_count->bindParam(':p_search_term', $searchTerm, PDO::PARAM_STR);
    $stmt_count->execute();
    $totalRecords = $stmt_count->fetchColumn(); // Obtiene la primera columna de la primera fila
    $stmt_count->closeCursor(); // CERRAR EL CURSOR ES CRÍTICO aquí para poder hacer la siguiente llamada al SP

    // 3. Llamar al Procedimiento Almacenado para OBTENER LOS DATOS
    $stmt_data = $conn->prepare("CALL sp_obtener_datos_pagos_paginados(:p_limit, :p_offset, :p_search_term, :p_sort_column, :p_sort_direction)");
    $stmt_data->bindParam(':p_limit', $limit, PDO::PARAM_INT);
    $stmt_data->bindParam(':p_offset', $offset, PDO::PARAM_INT);
    $stmt_data->bindParam(':p_search_term', $searchTerm, PDO::PARAM_STR);
    $stmt_data->bindParam(':p_sort_column', $sortColumn, PDO::PARAM_STR);
    $stmt_data->bindParam(':p_sort_direction', $sortDirection, PDO::PARAM_STR);
    $stmt_data->execute();
    $pagos = $stmt_data->fetchAll(PDO::FETCH_ASSOC); // Obtiene todas las filas como array asociativo
    $stmt_data->closeCursor(); // CERRAR EL CURSOR

    // 4. Calcular paginación y preparar respuesta
    $totalPages = ceil($totalRecords / $limit);
    // Ajuste para asegurar que siempre haya al menos 1 página si hay registros, o 0 si no hay
    if ($totalRecords > 0 && $totalPages == 0) {
        $totalPages = 1;
    } elseif ($totalRecords == 0) {
        $totalPages = 0;
    }


    $response["success"] = true;
    $response["message"] = "Pagos obtenidos exitosamente.";
    $response["data"] = $pagos;
    $response["total_records"] = $totalRecords;
    $response["total_pages"] = $totalPages;
    $response["current_page"] = $page;

} catch (Exception $e) {
    // Captura cualquier excepción (incluyendo las lanzadas desde Database.php)
    $response["message"] = "Error en el servidor al obtener pagos: " . $e->getMessage();
    // En un entorno de producción, loggear $e->getMessage() y mostrar un mensaje genérico al cliente.
} finally {
    // Asegurarse de que la conexión se cierre
    if ($conn) {
        $database->closeConnection();
    }
    echo json_encode($response);
}
?>