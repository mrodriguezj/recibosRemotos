<?php
// auth_middleware.php

// Cargar variables de entorno
require_once __DIR__ . '/env_loader.php';

// Cargar clase de base de datos
require_once __DIR__ . '/Database.php';

// Cargar la librería JWT (Composer)
require_once __DIR__ . '/vendor/autoload.php'; // Asume que vendor está en la raíz del proyecto

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException; // Para manejar tokens expirados

// Definir la URL del login
const LOGIN_URL = 'login.php';
const JWT_SECRET = 'YOUR_DEFAULT_JWT_SECRET'; // Default o valor de fallback si .env falla
const JWT_ALGO = 'HS256'; // Algoritmo de hashing para JWT

// Intenta obtener el secreto de JWT del .env si está disponible
if (getenv('JWT_SECRET')) {
    define('JWT_SECRET', getenv('JWT_SECRET'));
} else {
    // Si no se encuentra en .env, muere o usa un valor muy inseguro para desarrollo.
    // En producción, esto debería ser un error fatal.
    error_log("JWT_SECRET no configurado en .env o no accesible. Usando valor por defecto inseguro.");
}


// Función para redirigir al login
function redirectToLogin($message = '') {
    // Puedes pasar un mensaje de error o razón de redirección
    header('Location: ' . LOGIN_URL . ($message ? '?error=' . urlencode($message) : ''));
    exit();
}

// Función para verificar el token JWT
function verifyJwtToken() {
    // 1. Intentar obtener el token de las cookies (más seguro para JWT en navegadores)
    $jwt = null;
    if (isset($_COOKIE['jwt_token'])) {
        $jwt = $_COOKIE['jwt_token'];
    }
    // 2. Si no está en cookies, intentar de la cabecera Authorization (común para APIs o postman)
    if (!$jwt && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];
        }
    }

    if (!$jwt) {
        redirectToLogin('No hay sesión iniciada.');
    }

    try {
        // Decodificar y verificar el token
        // Usar Key::class para JWT v6+
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET, JWT_ALGO));

        // Puedes añadir más verificaciones aquí, como:
        // - Si el usuario_id del token existe en tu BD
        // - Si el token está en tu lista de tokens revocados (si implementas esa tabla)

        // Devolver los datos del token si es válido
        return (array) $decoded;

    } catch (ExpiredException $e) {
        // Token expirado
        redirectToLogin('Sesión expirada. Por favor, inicia sesión de nuevo.');
    } catch (Exception $e) {
        // Otro error de verificación (firma inválida, token corrupto, etc.)
        error_log("Error de verificación JWT: " . $e->getMessage()); // Loggear el error para depuración
        redirectToLogin('Token de sesión inválido. Por favor, inicia sesión de nuevo.');
    }
}

// Ejecutar la verificación si no es la página de login
if (basename($_SERVER['PHP_SELF']) !== LOGIN_URL) {
    $user_data = verifyJwtToken();
    // $user_data ahora contiene los datos del usuario del token decodificado
    // Puedes acceder a $user_data['user_id'], $user_data['nombre_usuario'], etc.
}