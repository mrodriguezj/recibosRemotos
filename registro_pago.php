<?php
// registro_pago.php

// ESTA ES LA PRIMERA LÍNEA DEL ARCHIVO
// AJUSTA LA RUTA SI auth_middleware.php NO ESTÁ EN EL MISMO DIRECTORIO
require_once __DIR__ . '/auth_middleware.php';

// Si el token es válido, $user_data estará disponible aquí con los datos del usuario.
// Puedes usar $user_data['user_id'] para el usuario_realizador_id en el kardex.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Comprobante de Pago - CobranzaPro</title>
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
        input:invalid:not(:placeholder-shown), select:invalid:not(:placeholder-shown), textarea:invalid:not(:placeholder-shown) {
            border-color: #ef4444; /* red-500 */
        }
        input:invalid:not(:placeholder-shown) + .validation-message,
        select:invalid:not(:placeholder-shown) + .validation-message,
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
        <h1 class="text-3xl font-semibold text-gray-800 mb-6">Registrar Nuevo Comprobante de Pago</h1>

        <section class="bg-white p-6 rounded-lg shadow-md mb-6 max-w-3xl mx-auto">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Detalles del Comprobante</h2>
            <form id="registroPagoForm" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2">
                <div class="md:col-span-1">
                    <label for="id_lote" class="block text-sm font-medium text-gray-700 mb-1">ID Lote <span class="text-red-500">*</span></label>
                    <input type="number" id="id_lote" name="id_lote" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: 101" required min="1">
                    <span class="validation-message">Debe ser un número entero positivo.</span>
                </div>
                <div class="md:col-span-1">
                    <label for="cliente_id" class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
                    <select id="cliente_id" name="cliente_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Cargando clientes...</option>
                    </select>
                    <span class="validation-message">Debe seleccionar un cliente.</span>
                </div>
                <div class="md:col-span-1">
                    <label for="fecha_esperada_pago" class="block text-sm font-medium text-gray-700 mb-1">Fecha Esperada de Pago <span class="text-red-500">*</span></label>
                    <input type="date" id="fecha_esperada_pago" name="fecha_esperada_pago" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <span class="validation-message">Este campo es requerido.</span>
                </div>
                <div class="md:col-span-1">
                    <label for="fecha_pago" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago Real <span class="text-red-500">*</span></label>
                    <input type="date" id="fecha_pago" name="fecha_pago" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <span class="validation-message">Este campo es requerido.</span>
                </div>
                <div class="md:col-span-1">
                    <label for="categoria_pago" class="block text-sm font-medium text-gray-700 mb-1">Categoría de Pago <span class="text-red-500">*</span></label>
                    <select id="categoria_pago" name="categoria_pago" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Seleccionar categoría</option>
                        <option value="Enganche">Enganche</option>
                        <option value="Contado">Contado</option>
                        <option value="Mensualidad">Mensualidad</option>
                        <option value="Anualidad">Anualidad</option>
                    </select>
                    <span class="validation-message">Debe seleccionar una categoría.</span>
                </div>
                <div class="md:col-span-1">
                    <label for="monto_pagado" class="block text-sm font-medium text-gray-700 mb-1">Monto Pagado <span class="text-red-500">*</span></label>
                    <input type="number" id="monto_pagado" name="monto_pagado" step="0.01" min="0.01" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: 1500.00" required>
                    <span class="validation-message">Debe ser un monto positivo.</span>
                </div>
                <div class="md:col-span-1">
                    <label for="metodo_pago" class="block text-sm font-medium text-gray-700 mb-1">Método de Pago <span class="text-red-500">*</span></label>
                    <select id="metodo_pago" name="metodo_pago" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="EFECTIVO">Efectivo</option>
                    </select>
                    <span class="validation-message">Debe seleccionar un método.</span>
                </div>
                <div class="md:col-span-2">
                    <label for="observaciones_pago" class="block text-sm font-medium text-gray-700 mb-1">Observaciones (Opcional)</label>
                    <textarea id="observaciones_pago" name="observaciones_pago" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Cualquier nota adicional sobre el pago"></textarea>
                </div>
                
                <div class="md:col-span-2 flex justify-end space-x-4 mt-4">
                    <button type="button" onclick="window.location.href='index.php'" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-save mr-2"></i> Registrar Comprobante
                    </button>
                </div>
            </form>
        </section>
    </main>

    <!-- Modal para Confirmación/Error -->
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

    <script src="js/registro_pago.js"></script>
    <script>
        // Funciones globales para HTML
        function logout() {
            alert('Has cerrado sesión. Redirigiendo al login...');
            window.location.href = 'login.php';
        }
        function closeModal() {
            document.getElementById('responseModal').style.display = 'none';
        }
    </script>
</body>
</html>