<?php
// generar_pdf.php (en la raíz del proyecto)

// AJUSTA ESTAS RUTAS SEGÚN LA UBICACIÓN REAL DE TUS ARCHIVOS
// Asume que env_loader.php, Database.php y vendor/autoload.php están en la raíz del proyecto
require_once __DIR__ . '/env_loader.php';      // env_loader.php en la raíz
require_once __DIR__ . '/Database.php';        // Database.php en la raíz
require_once __DIR__ . '/vendor/autoload.php'; // vendor/autoload.php en la raíz

use Dompdf\Dompdf;
use Dompdf\Options; 

header('Content-Type: application/json');

$database = new Database();
$conn = null;
$response = ["success" => false, "message" => ""];

try {
    $conn = $database->getConnection();

    // Obtener el pago_id de la solicitud GET
    $pago_id = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;

    if ($pago_id <= 0) {
        throw new Exception("ID de pago inválido o no proporcionado.");
    }

    // 1. Obtener los datos del pago y cliente desde el procedimiento almacenado
    $stmt = $conn->prepare("CALL sp_obtener_pago_para_pdf(?)");
    $stmt->bindParam(1, $pago_id, PDO::PARAM_INT);
    $stmt->execute();
    $pago_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$pago_data) {
        throw new Exception("Pago no encontrado para generar el comprobante.");
    }

    // --- APLICAR FORMATOS Y ESTILOS A LOS DATOS ANTES DE RELLENAR LA PLANTILLA ---
    
    // Formato de Teléfono (XXX)XXX-XXXX
    $formatted_telefono = $pago_data['cliente_telefono'];
    if (strlen($formatted_telefono) === 10) { // Asume que el teléfono siempre viene con 10 dígitos
        $formatted_telefono = '(' . substr($formatted_telefono, 0, 3) . ')' . substr($formatted_telefono, 3, 3) . '-' . substr($formatted_telefono, 6, 4);
    }
    
    // Formato de ID de Pago como "CPE XX"
    $formatted_pago_id_cpe = 'CPE ' . $pago_data['pago_id'];

    // Estilo y color condicional para Estado de Pago (Vigente/Cancelado)
    $estado_pago_html = '';
    if ($pago_data['estado_pago'] === 'Vigente') {
        $estado_pago_html = '<span style="color: #28a745; font-weight: bold;">Vigente</span>'; // Verde Bootstrap
    } elseif ($pago_data['estado_pago'] === 'Cancelado') {
        $estado_pago_html = '<span style="color: #dc3545; font-weight: bold;">Cancelado</span>'; // Rojo Bootstrap
    } else {
        $estado_pago_html = $pago_data['estado_pago']; // Por si hay otros estados no mapeados
    }
    // --- FIN APLICACIÓN DE FORMATOS Y ESTILOS ---


    // 2. Cargar la plantilla HTML
    $template_path = __DIR__ . '/plantilla.html'; 

    if (!file_exists($template_path)) {
        throw new Exception("Plantilla HTML de comprobante no encontrada en: " . $template_path);
    }
    $html_template = file_get_contents($template_path);

    // 3. Rellenar la plantilla con los datos
    $placeholders = [
        '[PAGO_ID]' => $formatted_pago_id_cpe,          // Usar el formato "CPE XX"
        '[FECHA_EMISION]' => date('d/m/Y'),
        '[ID_LOTE]' => $pago_data['id_lote'],
        '[FECHA_PAGO]' => date('d/m/Y', strtotime($pago_data['fecha_pago'])),
        '[METODO_PAGO]' => $pago_data['metodo_pago'],
        '[ESTADO_PAGO]' => $estado_pago_html,           // Usar el HTML generado con estilo
        '[NOMBRE_CLIENTE]' => $pago_data['cliente_nombres'] . ' ' . $pago_data['cliente_apellido_paterno'] . ' ' . ($pago_data['cliente_apellido_materno'] ?? ''),
        '[TELEFONO_CLIENTE]' => $formatted_telefono,     // Usar el formato (XXX)XXX-XXXX
        '[CATEGORIA_PAGO]' => $pago_data['categoria_pago'],
        '[FECHA_ESPERADA_PAGO]' => date('d/m/Y', strtotime($pago_data['fecha_esperada_pago'])),
        '[MONTO_PAGADO]' => number_format($pago_data['monto_pagado'], 2, '.', ','),
        
        // Datos bancarios simulados (directamente en el PHP, o desde un archivo de config)
        '[BANCO_SIMULADO]' => 'Banco ficticio del Mayab',
        '[NUM_CUENTA_SIMULADO]' => '1234567890',
        '[CLABE_SIMULADA]' => '012345678901234567',
        '[TITULAR_SIMULADO]' => 'CobranzaPro S.A. de C.V.',

        // Pie de página
        '[FECHA_GENERACION_SISTEMA]' => date('d/m/Y H:i:s', strtotime($pago_data['fecha_creacion'])),
        '[NOMBRE_USUARIO_GENERADOR]' => $pago_data['nombre_usuario_generador'] ?? 'N/A'
    ];

    $html = str_replace(array_keys($placeholders), array_values($placeholders), $html_template);

    // 4. Configurar Dompdf
    $options = new Options();
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);

    // 5. Establecer tamaño del papel (Carta: 'letter', orientación 'portrait' o 'landscape')
    $dompdf->setPaper('letter', 'portrait');

    // 6. Renderizar el HTML a PDF
    $dompdf->render();

    // 7. Enviar el PDF al navegador
    $filename = "CPE_" . $pago_data['pago_id'] . ".pdf"; // Nombre de archivo con el formato CPE

    // Limpiar cualquier salida anterior para evitar corrupción del PDF
    ob_clean(); 

    // Establecer encabezados para descarga de PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($dompdf->output()));
    
    // Enviar el PDF al navegador
    echo $dompdf->output();

} catch (Exception $e) {
    // Si algo falla, se enviará una respuesta JSON de error
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    $response["message"] = "Error al generar comprobante PDF: " . $e->getMessage();
    error_log("ERROR al generar PDF: " . $e->getMessage());
    echo json_encode($response);
} finally {
    if ($conn) {
        $database->closeConnection();
    }
}