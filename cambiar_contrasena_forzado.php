<?php
// cambiar_contrasena_forzado.php

// Este script DEBE ser protegido y recibir el usuario_id del JWT
// Si ya has pasado por auth_middleware.php, los datos del usuario ya están disponibles

// AJUSTA ESTAS RUTAS SEGÚN LA UBICACIÓN REAL DE TUS ARCHIVOS
require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/vendor/autoload.php'; // Para JWT
require_once __DIR__ . '/auth_middleware.php'; // Incluir el middleware de autenticación

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// auth_middleware.php ya redirigió si no hay token o es inválido/expirado
// Si llegamos aquí, $user_data (del middleware) contiene la información del usuario del token
if (!isset($user_data['user_id'])) {
    // Esto no debería pasar si auth_middleware.php funciona bien, pero es una salvaguarda
    header('Location: login.php?error=' . urlencode('Acceso denegado.'));
    exit();
}

// Lógica para procesar el formulario de cambio de contraseña cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // La respuesta de POST será JSON

    $response = ["success" => false, "message" => ""];
    $conn = null;

    // CORRECCIÓN AQUÍ: Instanciar Database dentro del bloque POST
    $database = new Database(); // <--- AGREGAR ESTA LÍNEA O DESCOMENTARLA

    try {
        $conn = $database->getConnection();

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $current_password = isset($data['current_password']) ? $data['current_password'] : '';
        $new_password = isset($data['new_password']) ? $data['new_password'] : '';
        $confirm_new_password = isset($data['confirm_new_password']) ? $data['confirm_new_password'] : '';

        // Obtener el ID del usuario del token JWT (del middleware)
        $usuario_id_from_token = $user_data['user_id'];
        $user_rol_from_token = $user_data['rol']; // Puedes usar el rol para lógica específica

        // 1. Validaciones básicas de entrada
        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
            $response["message"] = "Todos los campos de contraseña son requeridos.";
            echo json_encode($response);
            exit();
        }
        if (strlen($new_password) < 8) {
            $response["message"] = "La nueva contraseña debe tener al menos 8 caracteres.";
            echo json_encode($response);
            exit();
        }
        if ($new_password !== $confirm_new_password) {
            $response["message"] = "La nueva contraseña y su confirmación no coinciden.";
            echo json_encode($response);
            exit();
        }

        // 2. Obtener el hash de la contraseña actual del usuario desde la BD
        $stmt_get_user = $conn->prepare("SELECT password_hash FROM usuarios WHERE usuario_id = ? AND activo = TRUE");
        $stmt_get_user->bindParam(1, $usuario_id_from_token, PDO::PARAM_INT);
        $stmt_get_user->execute();
        $user_db = $stmt_get_user->fetch(PDO::FETCH_ASSOC);
        $stmt_get_user->closeCursor();

        if (!$user_db) {
            // Usuario no encontrado o inactivo (aunque el token sea válido, por si acaso)
            $response["message"] = "Error de usuario. Intenta iniciar sesión de nuevo.";
            echo json_encode($response);
            exit();
        }

        // 3. Verificar la contraseña actual
        if (!password_verify($current_password, $user_db['password_hash'])) {
            $response["message"] = "La contraseña actual es incorrecta.";
            echo json_encode($response);
            exit();
        }

        // 4. Hashear la nueva contraseña
        $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

        // 5. Llamar al procedimiento almacenado para actualizar la contraseña
        $stmt_update_pass = $conn->prepare("CALL sp_actualizar_contrasena_usuario(?, ?)");
        $stmt_update_pass->bindParam(1, $usuario_id_from_token, PDO::PARAM_INT);
        $stmt_update_pass->bindParam(2, $new_password_hash, PDO::PARAM_STR);
        $stmt_update_pass->execute();
        $rows_affected_result = $stmt_update_pass->fetch(PDO::FETCH_ASSOC); // Obtener el ROW_COUNT
        $stmt_update_pass->closeCursor();

        if ($rows_affected_result && $rows_affected_result['rows_affected'] > 0) {
            $response["success"] = true;
            $response["message"] = "Contraseña actualizada exitosamente. Ahora puedes iniciar sesión con tu nueva contraseña.";
            $response["redirect_to"] = "login.php?success=" . urlencode("Contraseña cambiada. Por favor inicia sesión.");

        } else {
            $response["message"] = "No se pudo actualizar la contraseña. Inténtalo de nuevo.";
        }

    } catch (Exception $e) {
        $response["message"] = "Error en el servidor al cambiar contraseña: " . $e->getMessage();
    } finally {
        if ($conn) {
            $database->closeConnection();
        }
        echo json_encode($response);
        exit();
    }
}
// Fin de la lógica POST
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - CobranzaPro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
        }
        .form-container {
            max-width: 400px;
        }
        /* Estilos para el modal */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
        }
        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        /* Estilos para validación */
        input:invalid:not(:placeholder-shown) {
            border-color: #ef4444; /* red-500 */
        }
        input:invalid:not(:placeholder-shown) + .validation-message {
            display: block;
            color: #ef4444; /* red-500 */
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .validation-message {
            display: none;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100 font-sans antialiased">
    <div class="form-container bg-white p-8 rounded-lg shadow-md w-full mx-4">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Cambiar Contraseña</h1>
            <p class="text-gray-600 mt-2">Es necesario que cambies tu contraseña para continuar.</p>
        </div>
        <form id="changePasswordForm" method="POST">
            <div class="mb-4">
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña Actual</label>
                <input type="password" id="current_password" name="current_password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="********" required>
                <span class="validation-message">Este campo es requerido.</span>
            </div>
            <div class="mb-4">
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña</label>
                <input type="password" id="new_password" name="new_password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="********" required minlength="8">
                <span class="validation-message">La contraseña debe tener al menos 8 caracteres.</span>
            </div>
            <div class="mb-6">
                <label for="confirm_new_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nueva Contraseña</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="********" required minlength="8">
                <span class="validation-message">Las contraseñas no coinciden.</span>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Cambiar Contraseña
            </button>
        </form>
    </div>

    <div id="responseModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-800 mb-4"></h3>
            <p id="modalMessage" class="text-gray-700 mb-4"></p>
            <div class="flex justify-end">
                <button onclick="closeModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Aceptar
                </button>
            </div>
        </div>
    </div>

    <script src="js/cambiar_contrasena_forzado.js"></script>
    <script>
        // Funciones globales para HTML
        function closeModal() {
            document.getElementById('responseModal').style.display = 'none';
            // Si la operación fue exitosa y redirige, podemos hacerlo aquí
            if (responseModal.dataset.redirect) {
                window.location.href = responseModal.dataset.redirect;
            }
        }
    </script>
</body>
</html>