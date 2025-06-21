<?php
// api/usuarios.php

// AJUSTA ESTA RUTA SEGÚN LA UBICACIÓN REAL DE TU Database.php
// Si Database.php está en el mismo directorio que usuarios.php:
require_once __DIR__ . '/database.php';
// SI Database.php está en la carpeta 'config/' y usuarios.php está en 'api/':
// require_once dirname(__DIR__) . '/config/Database.php';


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

    // --- Validar y sanear datos recibidos del formulario de registro de usuario ---
    $nombre_completo = isset($data['nombre_completo']) ? trim($data['nombre_completo']) : '';
    $nombre_usuario = isset($data['nombre_usuario']) ? trim($data['nombre_usuario']) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = isset($data['password']) ? $data['password'] : ''; // La contraseña en texto plano
    $rol = isset($data['rol']) ? trim($data['rol']) : '';

    // --- Aplicar mayúsculas y limpieza similar al frontend para asegurar consistencia ---
    $nombre_completo = strtoupper($nombre_completo);
    // nombre_usuario no lo convertimos a mayúsculas ya que puede ser sensible a mayúsculas/minúsculas en el login
    // email no se convierte a mayúsculas
    
    // Contraseña: HASHEARLA DE FORMA SEGURA
    // Usaremos password_hash() que es el método recomendado en PHP
    $password_hash = password_hash($password, PASSWORD_BCRYPT); // PASSWORD_BCRYPT es un algoritmo robusto

    // Validaciones de negocio (ejemplos, puedes expandir las expresiones regulares)
    $errors = [];

    if (empty($nombre_completo) || !preg_match('/^[A-ZÁÉÍÓÚÜÑ\s]+$/u', $nombre_completo)) {
        $errors[] = "El campo 'Nombre Completo' es requerido y solo debe contener letras y espacios.";
    }
    if (empty($nombre_usuario) || !preg_match('/^[A-Za-z0-9_]+$/', $nombre_usuario)) {
        $errors[] = "El campo 'Nombre de Usuario' es requerido y solo debe contener letras, números y guiones bajos.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El campo 'Correo Electrónico' es requerido y debe ser un formato válido.";
    }
    if (empty($password) || strlen($password) < 8) {
        $errors[] = "El campo 'Contraseña' es requerido y debe tener al menos 8 caracteres.";
    }
    // Validar el rol seleccionado
    $allowed_roles = ['Admin', 'Cobranza', 'Solo Lectura'];
    if (empty($rol) || !in_array($rol, $allowed_roles)) {
        $errors[] = "El campo 'Rol de Usuario' es requerido y debe ser uno válido.";
    }

    if (!empty($errors)) {
        $response["message"] = implode("\n", $errors);
        echo json_encode($response);
        exit();
    }

    // --- Llamar al Procedimiento Almacenado para registrar usuario ---
    $stmt = $conn->prepare("CALL sp_registrar_usuario(?, ?, ?, ?, ?)");
    
    // Bind de parámetros
    $stmt->bindParam(1, $nombre_usuario, PDO::PARAM_STR);
    $stmt->bindParam(2, $email, PDO::PARAM_STR);
    $stmt->bindParam(3, $password_hash, PDO::PARAM_STR); // Pasar el hash, no la contraseña original
    $stmt->bindParam(4, $nombre_completo, PDO::PARAM_STR);
    $stmt->bindParam(5, $rol, PDO::PARAM_STR);

    $stmt->execute();

    // El SP devuelve el LAST_INSERT_ID()
    $result_sp = $stmt->fetch(PDO::FETCH_ASSOC);
    $last_id = $result_sp ? $result_sp['usuario_id'] : null;

    $response["success"] = true;
    $response["message"] = "Usuario registrado exitosamente. ID: " . $last_id;

} catch (PDOException $e) {
    // Capturar errores de PDO, incluyendo los lanzados por SIGNAL SQLSTATE del SP
    $error_message = $e->getMessage();

    if (strpos($error_message, 'El nombre de usuario ya está en uso') !== false ||
        strpos($error_message, 'El correo electrónico ya está registrado') !== false) {
        // Es un error de negocio de duplicado capturado por el SP
        $response["message"] = "Error de registro: " . $error_message;
    } else {
        // Otros errores de base de datos
        $response["message"] = "Error de base de datos al registrar usuario: " . $error_message;
        // Considera loggear $e para más detalles en producción
    }

} catch (Exception $e) {
    // Capturar cualquier otra excepción
    $response["message"] = "Error inesperado en el servidor: " . $e->getMessage();
} finally {
    if ($conn) {
        $database->closeConnection();
    }
    echo json_encode($response);
}