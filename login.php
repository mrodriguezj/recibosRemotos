<?php
// login.php

// AJUSTA ESTAS RUTAS SEGÚN LA UBICACIÓN REAL DE TUS ARCHIVOS
// Asume que env_loader.php, Database.php y vendor/autoload.php están en el mismo directorio que login.php
require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Definir URL de destino tras login exitoso
const DASHBOARD_URL = 'index.php';

// Obtener el secreto de JWT del .env
// Es CRÍTICO que JWT_SECRET en tu .env tenga un valor FUERTE y que el env_loader.php funcione correctamente.
// Si getenv() falla, esta aplicación no funcionará correctamente o usará un secreto vacío si no se controla.
define('JWT_SECRET', getenv('JWT_SECRET'));
define('JWT_ALGO', 'HS256');

// Tiempo de validez del token: 5 minutos (300 segundos)
const JWT_EXPIRATION_TIME = 300; // 5 * 60 segundos

$database = new Database();
$conn = null;
$error_message = ''; // Para mostrar errores de login en el HTML

// Lógica para procesar el formulario de login cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // La respuesta de POST será JSON

    $response = ["success" => false, "message" => ""];

    try {
        $conn = $database->getConnection();

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $identificador = isset($data['nombre_usuario']) ? trim($data['nombre_usuario']) : '';
        $password = isset($data['password']) ? $data['password'] : '';

        // Validaciones básicas de entrada
        if (empty($identificador) || empty($password)) {
            $response["message"] = "Usuario/Correo y contraseña son requeridos.";
            echo json_encode($response);
            exit();
        }

        // Llamar al procedimiento almacenado para verificar credenciales
        $stmt = $conn->prepare("CALL sp_verificar_credenciales_usuario(?)");
        $stmt->bindParam(1, $identificador, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // Cerrar cursor es CRÍTICO para permitir futuras operaciones PDO

        // 1. Verificar si el usuario existe
        if (!$user) {
            $response["message"] = "Credenciales inválidas.";
            echo json_encode($response);
            exit();
        }

        // 2. Verificar si la cuenta está activa
        if (!$user['activo']) {
            $response["message"] = "Tu cuenta está inactiva. Contacta al administrador.";
            echo json_encode($response);
            exit();
        }

        // 3. Verificar la contraseña (password_verify para comparar texto plano con hash)
        if (password_verify($password, $user['password_hash'])) {
            // Contraseña válida

            // Generar Token JWT
            $issueTime = time(); // Hora de emisión (Issued at)
            $expirationTime = $issueTime + JWT_EXPIRATION_TIME; // Hora de expiración (Expiration time)

            $payload = array(
                "iat" => $issueTime,             // Issued at: momento en que el token fue emitido
                "exp" => $expirationTime,        // Expiration time: momento en que el token expira
                "nbf" => $issueTime,             // Not before: momento antes del cual el token no puede ser aceptado
                "data" => array(                 // Datos del usuario (no sensibles)
                    "user_id" => $user['usuario_id'], // Cambiado de 'usuario_id' a 'user_id' para consistencia con auth_middleware
                    "nombre_usuario" => $user['nombre_usuario'],
                    "nombre_completo" => $user['nombre_completo'],
                    "rol" => $user['rol']
                )
            );

            $jwt = JWT::encode($payload, JWT_SECRET, JWT_ALGO);

            // 4. Establecer el Token JWT en una cookie HTTP-Only (más seguro para navegadores)
            setcookie('jwt_token', $jwt, [
                'expires' => $expirationTime, // Fecha de expiración de la cookie
                'path' => '/', // Disponible en todo el sitio
                'httponly' => true, // La cookie no es accesible vía JavaScript
                // 'secure' => true, // Habilitar en producción con HTTPS (solo se envía sobre HTTPS)
                'samesite' => 'Lax' // Protección CSRF: 'Lax' para la mayoría de los casos, 'Strict' para mayor seguridad
            ]);

            // 5. Actualizar ultima_sesion en la BD
            $stmt_update_session = $conn->prepare("UPDATE usuarios SET ultima_sesion = NOW() WHERE usuario_id = ?");
            $stmt_update_session->bindParam(1, $user['usuario_id'], PDO::PARAM_INT);
            $stmt_update_session->execute();
            $stmt_update_session->closeCursor();

            // 6. Verificar primer_login_requiere_cambio
            if ($user['primer_login_requiere_cambio']) {
                $response["success"] = true;
                $response["message"] = "Bienvenido. Debes cambiar tu contraseña en el primer inicio de sesión.";
                $response["redirect_to"] = "cambiar_contrasena_forzado.php"; // Redirigir a la página de cambio de contraseña
            } else {
                // Login exitoso, redirigir al panel principal
                $response["success"] = true;
                $response["message"] = "Inicio de sesión exitoso.";
                $response["redirect_to"] = DASHBOARD_URL;
            }

        } else {
            // Contraseña inválida
            $response["message"] = "Credenciales inválidas.";
        }

    } catch (Exception $e) {
        $response["message"] = "Error en el servidor: " . $e->getMessage();
        // En un entorno de producción, loggear $e->getMessage() y mostrar un mensaje genérico.
    } finally {
        if ($conn) {
            $database->closeConnection();
        }
        echo json_encode($response);
        exit(); // Terminar el script PHP después de enviar la respuesta JSON
    }
}
// Fin de la lógica POST
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CobranzaPro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
        }
        .login-container {
            max-width: 400px;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="login-container bg-white p-8 rounded-lg shadow-md w-full mx-4">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">CobranzaPro</h1>
            <p class="text-gray-600 mt-2">Inicia sesión en tu cuenta</p>
        </div>
        <form id="loginForm" method="POST">
            <div class="mb-4">
                <label for="nombre_usuario" class="block text-sm font-medium text-gray-700 mb-1">Usuario / Correo Electrónico</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="tu_usuario o tu@ejemplo.com" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="********" required>
            </div>

            <div id="login-error-message" class="text-red-600 text-sm mb-4" style="display: none;"></div>

            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Iniciar Sesión
            </button>
            <div class="text-center mt-4">
                <a href="#" class="text-sm text-blue-600 hover:underline">¿Olvidaste tu contraseña?</a>
            </div>
            <div class="text-center mt-2">
                <a href="registro_usuario.php" class="text-sm text-gray-600 hover:underline">¿No tienes cuenta? Regístrate aquí</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('loginForm');
            const errorMessageDiv = document.getElementById('login-error-message');

            loginForm.addEventListener('submit', async (event) => {
                event.preventDefault(); // Prevenir el envío tradicional del formulario

                errorMessageDiv.style.display = 'none'; // Ocultar mensajes de error anteriores
                errorMessageDiv.innerText = '';

                const formData = new FormData(loginForm);
                const data = Object.fromEntries(formData.entries());

                try {
                    const response = await fetch('login.php', { // Enviar la solicitud al mismo script
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Redirigir si el login es exitoso
                        if (result.redirect_to) {
                            window.location.href = result.redirect_to;
                        } else {
                            // Fallback, debería redirigir a dashboard si no hay redirect_to explícito
                            window.location.href = 'index.php';
                        }
                    } else {
                        // Mostrar mensaje de error
                        errorMessageDiv.innerText = result.message || 'Error desconocido al iniciar sesión.';
                        errorMessageDiv.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error al iniciar sesión:', error);
                    errorMessageDiv.innerText = 'Error de conexión con el servidor. Inténtalo de nuevo.';
                    errorMessageDiv.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>