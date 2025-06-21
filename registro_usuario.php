<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - CobranzaPro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6; /* Fondo gris claro */
        }
        .form-container {
            max-width: 500px;
            margin: auto; /* Centrar el contenedor */
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
<body class="bg-gray-100 font-sans antialiased flex flex-col min-h-screen">

    <header class="bg-white shadow-sm p-4 flex justify-between items-center">
        <div class="text-xl font-bold text-gray-800">
            <a href="index.php" class="hover:text-blue-600">CobranzaPro</a>
        </div>
        <div class="flex items-center space-x-4">
            <a href="login.php" class="text-gray-600 hover:text-blue-500 flex items-center">
                <i class="fas fa-sign-in-alt mr-1"></i> Iniciar Sesión
            </a>
        </div>
    </header>

    <main class="flex-grow container mx-auto p-4 sm:px-6 lg:px-8 flex items-center justify-center">
        <section class="bg-white p-6 rounded-lg shadow-md w-full form-container">
            <h1 class="text-3xl font-semibold text-gray-800 mb-6 text-center">Registro de Nuevo Usuario</h1>
            
            <form id="registroUsuarioForm" class="grid grid-cols-1 gap-4">
                <div>
                    <label for="nombre_completo" class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                    <input type="text" id="nombre_completo" name="nombre_completo" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: Juan Pérez Gómez" required>
                    <span class="validation-message">Este campo es requerido.</span>
                </div>
                <div>
                    <label for="nombre_usuario" class="block text-sm font-medium text-gray-700 mb-1">Nombre de Usuario <span class="text-red-500">*</span></label>
                    <input type="text" id="nombre_usuario" name="nombre_usuario" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: juanperez" required>
                    <span class="validation-message">Este campo es requerido.</span>
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="usuario@ejemplo.com" required>
                    <span class="validation-message">Debe ser un correo electrónico válido.</span>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="********" required minlength="8">
                    <span class="validation-message">La contraseña debe tener al menos 8 caracteres.</span>
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña <span class="text-red-500">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="********" required minlength="8">
                    <span class="validation-message">Las contraseñas no coinciden.</span>
                </div>
                <div>
                    <label for="rol" class="block text-sm font-medium text-gray-700 mb-1">Rol de Usuario <span class="text-red-500">*</span></label>
                    <select id="rol" name="rol" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Seleccionar Rol</option>
                        <option value="Admin">Administrador</option>
                        <option value="Cobranza">Cobranza</option>
                        <option value="Solo Lectura">Solo Lectura</option>
                    </select>
                    <span class="validation-message">Este campo es requerido.</span>
                </div>
                
                <div class="flex justify-end space-x-4 mt-4">
                    <button type="button" onclick="window.location.href='login.php'" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                        Volver al Login
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-user-plus mr-2"></i> Registrar Usuario
                    </button>
                </div>
            </form>
        </section>
    </main>

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

    <script src="js/registro_usuario.js"></script>
    <script>
        // Funciones globales para HTML
        function closeModal() {
            document.getElementById('responseModal').style.display = 'none';
        }
    </script>
</body>
</html>