<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Nuevo Cliente - CobranzaPro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos personalizados para el modal */
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
        input:invalid:not(:placeholder-shown), textarea:invalid:not(:placeholder-shown) {
            border-color: #ef4444; /* red-500 */
        }
        input:invalid:not(:placeholder-shown) + .validation-message,
        textarea:invalid:not(:placeholder-shown) + .validation-message {
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
<body class="bg-gray-100 font-sans antialiased">

    <header class="bg-white shadow-sm p-4 flex justify-between items-center">
        <div class="text-xl font-bold text-gray-800">
            <a href="index.php" class="hover:text-blue-600">CobranzaPro</a>
        </div>
        <div class="flex items-center space-x-4">
            <button class="text-gray-600 hover:text-blue-500">
                <i class="fas fa-bell"></i>
            </button>
            <div class="relative">
                <img src="https://via.placeholder.com/32" alt="User Avatar" class="w-8 h-8 rounded-full cursor-pointer" id="userMenuBtn">
            </div>
            <button onclick="logout()" class="text-gray-600 hover:text-red-500 flex items-center">
                <i class="fas fa-sign-out-alt mr-1"></i> Cerrar Sesión
            </button>
        </div>
    </header>

    <main class="container mx-auto p-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-semibold text-gray-800 mb-6">Agregar Nuevo Cliente</h1>

        <section class="bg-white p-6 rounded-lg shadow-md mb-6 max-w-3xl mx-auto">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Datos del Cliente</h2>
            <form id="clienteForm" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2">
                <div class="md:col-span-1">
                    <label for="nombres" class="block text-sm font-medium text-gray-700 mb-1">Nombre(s) <span class="text-red-500">*</span></label>
                    <input type="text" id="nombres" name="nombres" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: Juan" required>
                    <span class="validation-message">Este campo es requerido.</span>
                </div>
                <div class="md:col-span-1">
                    <label for="apellido_paterno" class="block text-sm font-medium text-gray-700 mb-1">Apellido Paterno <span class="text-red-500">*</span></label>
                    <input type="text" id="apellido_paterno" name="apellido_paterno" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: Pérez" required>
                    <span class="validation-message">Este campo es requerido.</span>
                </div>
                <div class="md:col-span-1">
                    <label for="apellido_materno" class="block text-sm font-medium text-gray-700 mb-1">Apellido Materno (Opcional)</label>
                    <input type="text" id="apellido_materno" name="apellido_materno" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: García">
                </div>
                <div class="md:col-span-1">
                    <label for="correo_electronico" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                    <input type="email" id="correo_electronico" name="correo_electronico" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="cliente@ejemplo.com" required>
                    <span class="validation-message">Debe ser un correo electrónico válido.</span>
                </div>
                <div class="md:col-span-1">
                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono (10 dígitos) <span class="text-red-500">*</span></label>
                    <input type="tel" id="telefono" name="telefono" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: 5512345678" pattern="[0-9]{10}" maxlength="10" required>
                    <span class="validation-message">Debe ser un número de teléfono de 10 dígitos.</span>
                </div>
                <div class="md:col-span-1">
                    <label for="curp" class="block text-sm font-medium text-gray-700 mb-1">CURP (18 caracteres alfanum.) <span class="text-red-500">*</span></label>
                    <input type="text" id="curp" name="curp" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: GOCA900101HOCALC01" pattern="[A-Z0-9]{18}" maxlength="18" required>
                    <span class="validation-message">CURP inválida (18 caracteres alfanuméricos).</span>
                </div>
                <div class="md:col-span-1">
                    <label for="rfc" class="block text-sm font-medium text-gray-700 mb-1">RFC (13 caracteres alfanum., Opcional)</label>
                    <input type="text" id="rfc" name="rfc" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: GOCJ900101ABC" pattern="[A-Z0-9]{12,13}" maxlength="13">
                    <span class="validation-message">RFC inválido (12 o 13 caracteres alfanuméricos).</span>
                </div>
                <div class="md:col-span-1">
                    <label for="ine" class="block text-sm font-medium text-gray-700 mb-1">INE (15 caracteres alfanum.) <span class="text-red-500">*</span></label>
                    <input type="text" id="ine" name="ine" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: ABC123456789012" pattern="[A-Z0-9]{15}" maxlength="15" required>
                    <span class="validation-message">INE inválido (15 caracteres alfanuméricos).</span>
                </div>
                <div class="md:col-span-2">
                    <label for="direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección Completa (Opcional)</label>
                    <textarea id="direccion" name="direccion" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Calle, Número, Colonia, Ciudad, Estado, C.P."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end space-x-4">
                    <button type="button" onclick="window.location.href='index.php'" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-user-plus mr-2"></i> Registrar Cliente
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

    <script src="js/agregar_cliente.js"></script>
    <script>
        // Funciones globales para HTML
        function logout() {
            alert('Has cerrado sesión. Redirigiendo al login...');
            window.location.href = 'login.html';
        }
        function closeModal() {
            document.getElementById('responseModal').style.display = 'none';
        }
    </script>
</body>
</html>