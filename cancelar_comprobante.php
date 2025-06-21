<?php
// cancelar_comprobante.php

// ESTA ES LA PRIMERA LÍNEA DEL ARCHIVO
// AJUSTA LA RUTA SI auth_middleware.php NO ESTÁ EN EL MISMO DIRECTORIO
require_once __DIR__ . '/auth_middleware.php';

// Si el token es válido, $user_data estará disponible aquí con los datos del usuario.
// Necesitaremos $user_data['user_id'] para registrar quién cancela un pago.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar Comprobante - CobranzaPro</title>
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
        textarea:invalid:not(:placeholder-shown) {
            border-color: #ef4444; /* red-500 */
        }
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
        <h1 class="text-3xl font-semibold text-gray-800 mb-6">Cancelar Comprobante de Pago</h1>

        <section class="bg-white p-6 rounded-lg shadow-md mb-6 max-w-3xl mx-auto">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Buscar Comprobante</h2>
            <form id="buscarComprobanteForm" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2 mb-6">
                <div class="md:col-span-1">
                    <label for="buscar_id_lote" class="block text-sm font-medium text-gray-700 mb-1">ID de Lote</label>
                    <input type="number" id="buscar_id_lote" name="buscar_id_lote" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: 101" min="1">
                    <span class="validation-message">Debe ser un número entero positivo.</span>
                </div>
                <div class="md:col-span-1">
                    <label for="buscar_pago_id" class="block text-sm font-medium text-gray-700 mb-1">ID de Pago</label>
                    <input type="number" id="buscar_pago_id" name="buscar_pago_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: 1" min="1">
                    <span class="validation-message">Debe ser un número entero positivo.</span>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-search mr-2"></i> Buscar
                    </button>
                </div>
            </form>

            <div id="comprobanteDetalle" class="hidden border-t border-gray-200 pt-6 mt-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Detalles del Comprobante Encontrado</h2>
                <input type="hidden" id="detalle_pago_id_oculto"> 
                <input type="hidden" id="detalle_id_lote_oculto"> 
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2 mb-6 text-gray-700">
                    <div>
                        <p class="text-sm font-medium text-gray-500">ID Lote:</p>
                        <p id="detalle_id_lote" class="text-base font-semibold"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">ID Pago:</p>
                        <p id="detalle_pago_id" class="text-base font-semibold"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Cliente:</p>
                        <p id="detalle_cliente" class="text-base"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Fecha Esperada de Pago:</p>
                        <p id="detalle_fecha_esperada_pago" class="text-base"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Fecha de Pago Real:</p>
                        <p id="detalle_fecha_pago" class="text-base"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Categoría:</p>
                        <p id="detalle_categoria_pago" class="text-base"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Monto Pagado:</p>
                        <p id="detalle_monto_pagado" class="text-base"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Método de Pago:</p>
                        <p id="detalle_metodo_pago" class="text-base"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Estado Actual:</p>
                        <p id="detalle_estado_pago" class="text-base"></p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm font-medium text-gray-500">Observaciones:</p>
                        <p id="detalle_observaciones_pago" class="text-base"></p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4">
                    <button type="button" id="btnConfirmarCancelacion" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        <i class="fas fa-trash-alt mr-2"></i> Confirmar Cancelación
                    </button>
                    <button type="button" id="btnCancelarBusqueda" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                        Cancelar
                    </button>
                </div>
            </div>

            <div id="noResultados" class="hidden bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
                <p class="font-bold">Comprobante no encontrado</p>
                <p>No se encontró ningún comprobante con los criterios de búsqueda. Por favor, verifica la información e intenta de nuevo.</p>
            </div>

        </section>
    </main>

    <div id="motivoCancelacionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button" onclick="closeMotivoModal()">&times;</span>
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Motivo de Cancelación</h3>
            <div class="mb-4">
                <label for="motivo_textarea" class="block text-sm font-medium text-gray-700 mb-1">Por favor, escribe el motivo de la cancelación: <span class="text-red-500">*</span></label>
                <textarea id="motivo_textarea" rows="4" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Indica claramente la razón de la cancelación..." required></textarea>
                <span class="validation-message">Este campo es requerido y debe tener al menos 10 caracteres.</span>
                </div>
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="closeMotivoModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                    Cancelar
                </button>
                <button type="button" id="btnFinalizarCancelacion" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <div id="responseModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button" onclick="closeResponseModal()">&times;</span>
            <h3 id="responseModalTitle" class="text-xl font-semibold text-gray-800 mb-4"></h3>
            <p id="responseModalMessage" class="text-gray-700 mb-4"></p>
            <div class="flex justify-end">
                <button onclick="closeResponseModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Aceptar
                </button>
            </div>
        </div>
    </div>

    <script src="js/cancelar_comprobante.js"></script>
    <script>
        // Funciones globales para HTML
        function closeResponseModal() { // Renombrado para evitar conflicto
            document.getElementById('responseModal').style.display = 'none';
        }
        function closeMotivoModal() { // Para el modal de motivo específico
            document.getElementById('motivoCancelacionModal').style.display = 'none';
            document.getElementById('motivo_textarea').value = '';
            document.getElementById('motivo_textarea').classList.remove('border-red-500'); // Limpiar validación visual
            document.getElementById('motivo_textarea').nextElementSibling.style.display = 'none'; // Ocultar mensaje
        }
        // ATENCIÓN: La función logout ya NO se define aquí.
        // Se define en js/cancelar_comprobante.js y es accesible globalmente (window.logout).
    </script>
</body>
</html>