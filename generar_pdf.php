<?php
// generar_pdf.php (en la raíz del proyecto)

// AJUSTA ESTAS RUTAS SEGÚN LA UBICACIÓN REAL DE TUS ARCHIVOS
// Asume que env_loader.php, Database.php y vendor/autoload.php están en la raíz del proyecto
require_once __DIR__ . '/env_loader.php';      // env_loader.php en la raíz
require_once __DIR__ . '/database.php';        // Database.php en la raíz
require_once __DIR__ . '/vendor/autoload.php'; // vendor/autoload.php en la raíz

use Dompdf\Dompdf;
use Dompdf\Options; 

header('Content-Type: application/json');

$database = new Database();
$conn = null;
$response = ["success" => false, "message" => ""];

try {
    $conn = $database->getConnection();

    $pago_id = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;

    if ($pago_id <= 0) {
        throw new Exception("ID de pago inválido o no proporcionado.");
    }

    $stmt = $conn->prepare("CALL sp_obtener_pago_para_pdf(?)");
    $stmt->bindParam(1, $pago_id, PDO::PARAM_INT);
    $stmt->execute();
    $pago_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$pago_data) {
        throw new Exception("Pago no encontrado para generar el comprobante.");
    }

    // --- APLICAR FORMATOS Y ESTILOS A LOS DATOS ANTES DE RELLENAR LA PLANTILLA ---
    
    $formatted_telefono = $pago_data['cliente_telefono'];
    if (strlen($formatted_telefono) === 10) {
        $formatted_telefono = '(' . substr($formatted_telefono, 0, 3) . ')' . substr($formatted_telefono, 3, 3) . '-' . substr($formatted_telefono, 6, 4);
    }
    
    $formatted_pago_id_cpe = 'CPE ' . $pago_data['pago_id'];

    $estado_pago_html = '';
    if ($pago_data['estado_pago'] === 'Vigente') {
        $estado_pago_html = '<span style="color: #28a745; font-weight: bold;">Vigente</span>';
    } elseif ($pago_data['estado_pago'] === 'Cancelado') {
        $estado_pago_html = '<span style="color: #dc3545; font-weight: bold;">Cancelado</span>';
    } else {
        $estado_pago_html = $pago_data['estado_pago'];
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
        '[PAGO_ID]' => $formatted_pago_id_cpe,
        '[FECHA_EMISION]' => date('d/m/Y'),
        '[ID_LOTE]' => $pago_data['id_lote'],
        '[FECHA_PAGO]' => date('d/m/Y', strtotime($pago_data['fecha_pago'])),
        '[METODO_PAGO]' => $pago_data['metodo_pago'],
        '[ESTADO_PAGO]' => $estado_pago_html,
        '[NOMBRE_CLIENTE]' => $pago_data['cliente_nombres'] . ' ' . $pago_data['cliente_apellido_paterno'] . ' ' . ($pago_data['cliente_apellido_materno'] ?? ''),
        '[TELEFONO_CLIENTE]' => $formatted_telefono,
        '[CATEGORIA_PAGO]' => $pago_data['categoria_pago'],
        '[FECHA_ESPERADA_PAGO]' => date('d/m/Y', strtotime($pago_data['fecha_esperada_pago'])),
        '[MONTO_PAGADO]' => number_format($pago_data['monto_pagado'], 2, '.', ','),
        
        '[BANCO_SIMULADO]' => 'Banco ficticio del Mayab',
        '[NUM_CUENTA_SIMULADO]' => '1234567890',
        '[CLABE_SIMULADA]' => '012345678901234567',
        '[TITULAR_SIMULADO]' => 'CobranzaPro S.A. de C.V.',

        '[FECHA_GENERACION_SISTEMA]' => date('d/m/Y H:i:s', strtotime($pago_data['fecha_creacion'])),
        '[NOMBRE_USUARIO_GENERADOR]' => $pago_data['nombre_usuario_generador'] ?? 'N/A'
    ];

    $html = str_replace(array_keys($placeholders), array_values($placeholders), $html_template);

    // 4. Configurar Dompdf
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', TRUE); // Necesario si el logo está en una URL remota o path absoluto de sistema
    $options->set('isHtml5ParserEnabled', TRUE); // Puede ayudar con renderizado

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);

    // 5. Establecer tamaño del papel (Carta: 'letter', orientación 'portrait' o 'landscape')
    $dompdf->setPaper('letter', 'portrait');

    // 6. Renderizar el HTML a PDF
    $dompdf->render();

    // --- INICIO: IMPLEMENTACIÓN DE MARCA DE AGUA VÍA DOMPDF ---
    $canvas = $dompdf->getCanvas();

    // Ruta a tu imagen de logo. Asume 'logo.png' está en la raíz del proyecto
    $logoPath = __DIR__ . '/logo.png'; // <--- ¡COLOCA LA RUTA CORRECTA A TU LOGO!

    if (file_exists($logoPath)) {
        $logoWidth = 500; // Ancho deseado de la marca de agua en puntos (1 pulgada = 72 puntos)
        $logoHeight = 500; // Altura deseada. Si es cuadrado, ancho y alto iguales.

        // Calcular posición para centrar la imagen en la página
        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();

        $x = ($pageWidth - $logoWidth) / 2;
        $y = ($pageHeight - $logoHeight) / 2;

        // Dibujar la imagen con opacidad
        // Parámetros: ruta_imagen, x, y, ancho, alto, opacidad
        $canvas->set_opacity(0.1); // Opacidad del 10% (0.0 a 1.0)
        $canvas->image($logoPath, $x, $y, $logoWidth, $logoHeight);
        $canvas->set_opacity(1.0); // Restablecer la opacidad para el contenido normal

    } else {
        error_log("ADVERTENCIA: Archivo de logo para marca de agua no encontrado en: " . $logoPath);
    }
    // --- FIN: IMPLEMENTACIÓN DE MARCA DE AGUA VÍA DOMPDF ---


    // 7. Enviar el PDF al navegador
    $filename = "CPE_" . $pago_data['pago_id'] . ".pdf";

    ob_clean(); 

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($dompdf->output()));
    
    echo $dompdf->output();

} catch (Exception $e) {
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