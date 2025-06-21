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
                    <input type="text" id="buscar_id_lote" name="buscar_id_lote" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: LOTE-001">
                </div>
                <div class="md:col-span-1">
                    <label for="buscar_numero_pago" class="block text-sm font-medium text-gray-700 mb-1">Número de Pago</label>
                    <input type="text" id="buscar_numero_pago" name="buscar_numero_pago" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: 1, 5, Mensualidad 3">
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="button" onclick="buscarComprobante()" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-search mr-2"></i> Buscar
                    </button>
                </div>
            </form>

            <div id="comprobanteDetalle" class="hidden border-t border-gray-200 pt-6 mt-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Detalles del Comprobante Encontrado</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2 mb-6 text-gray-700">
                    <div>
                        <p class="text-sm font-medium text-gray-500">ID Lote:</p>
                        <p id="detalle_id_lote" class="text-base font-semibold"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Cliente:</p>
                        <p id="detalle_cliente" class="text-base"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Número de Pago:</p>
                        <p id="detalle_numero_pago" class="text-base"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Fecha de Pago:</p>
                        <p id="detalle_fecha_pago" class="text-base"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Categoría:</p>
                        <p id="detalle_categoria" class="text-base"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Monto Pagado:</p>
                        <p id="detalle_monto_pagado" class="text-base"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Método de Pago:</p>
                        <p id="detalle_metodo_pago" class="text-base"></p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm font-medium text-gray-500">Referencia/Concepto:</p>
                        <p id="detalle_referencia_pago" class="text-base"></p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm font-medium text-gray-500">Observaciones:</p>
                        <p id="detalle_observaciones" class="text-base"></p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="mostrarModalConfirmacion()" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        <i class="fas fa-trash-alt mr-2"></i> Confirmar Cancelación
                    </button>
                    <button type="button" onclick="ocultarDetalle()" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
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
            <span class="close-button" onclick="cerrarModal()">&times;</span>
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Motivo de Cancelación</h3>
            <div class="mb-4">
                <label for="motivo_textarea" class="block text-sm font-medium text-gray-700 mb-1">Por favor, escribe el motivo de la cancelación:</label>
                <textarea id="motivo_textarea" rows="4" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Indica claramente la razón de la cancelación..."></textarea>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="cerrarModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                    Cancelar
                </button>
                <button type="button" onclick="finalizarCancelacion()" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Data de ejemplo (simulando una base de datos)
        const pagosRegistrados = [
            { id_lote: 'LOTE-001', numero_pago: '1', cliente: 'Juan Pérez', fecha_pago: '2025-06-15', categoria: 'Enganche', monto_pagado: 15000.00, metodo_pago: 'Transferencia', referencia_pago: 'TRF-12345', observaciones: 'Pago inicial completo.' },
            { id_lote: 'LOTE-002', numero_pago: '3', cliente: 'María López', fecha_pago: '2025-06-20', categoria: 'Mensualidad', monto_pagado: 2500.00, metodo_pago: 'Deposito', referencia_pago: 'DEP-67890', observaciones: 'Mensualidad de Junio.' },
            { id_lote: 'LOTE-001', numero_pago: '2', cliente: 'Juan Pérez', fecha_pago: '2025-07-01', categoria: 'Mensualidad', monto_pagado: 2500.00, metodo_pago: 'Efectivo', referencia_pago: '', observaciones: 'Pago de Julio.' },
            { id_lote: 'LOTE-003', numero_pago: '1', cliente: 'Carlos Gómez', fecha_pago: '2025-06-10', categoria: 'Contado', monto_pagado: 50000.00, metodo_pago: 'Transferencia', referencia_pago: 'TRF-ABCDE', observaciones: 'Pago de contado por propiedad X.' },
            { id_lote: 'LOTE-002', numero_pago: '2', cliente: 'María López', fecha_pago: '2025-05-20', categoria: 'Mensualidad', monto_pagado: 2500.00, metodo_pago: 'Transferencia', referencia_pago: 'TRF-09876', observaciones: 'Mensualidad de Mayo.' },
        ];

        let pagoSeleccionadoParaCancelar = null; // Variable para almacenar el pago actual

        function buscarComprobante() {
            const buscarIdLote = document.getElementById('buscar_id_lote').value.trim();
            const buscarNumeroPago = document.getElementById('buscar_numero_pago').value.trim();
            const comprobanteDetalleDiv = document.getElementById('comprobanteDetalle');
            const noResultadosDiv = document.getElementById('noResultados');

            // Ocultar resultados anteriores y limpiar
            comprobanteDetalleDiv.classList.add('hidden');
            noResultadosDiv.classList.add('hidden');
            pagoSeleccionadoParaCancelar = null; // Resetear el pago seleccionado

            const encontrado = pagosRegistrados.find(pago =>
                (buscarIdLote === '' || pago.id_lote.toLowerCase() === buscarIdLote.toLowerCase()) &&
                (buscarNumeroPago === '' || pago.numero_pago.toLowerCase() === buscarNumeroPago.toLowerCase())
            );

            if (encontrado) {
                pagoSeleccionadoParaCancelar = encontrado; // Almacenar el pago encontrado

                document.getElementById('detalle_id_lote').innerText = encontrado.id_lote;
                document.getElementById('detalle_cliente').innerText = encontrado.cliente;
                document.getElementById('detalle_numero_pago').innerText = encontrado.numero_pago;
                document.getElementById('detalle_fecha_pago').innerText = encontrado.fecha_pago;
                document.getElementById('detalle_categoria').innerText = encontrado.categoria;
                document.getElementById('detalle_monto_pagado').innerText = `$${encontrado.monto_pagado.toFixed(2)}`;
                document.getElementById('detalle_metodo_pago').innerText = encontrado.metodo_pago;
                document.getElementById('detalle_referencia_pago').innerText = encontrado.referencia_pago || 'N/A';
                document.getElementById('detalle_observaciones').innerText = encontrado.observaciones || 'Sin observaciones';

                comprobanteDetalleDiv.classList.remove('hidden');
            } else {
                noResultadosDiv.classList.remove('hidden');
            }
        }

        function mostrarModalConfirmacion() {
            if (!pagoSeleccionadoParaCancelar) {
                alert('Por favor, busca y selecciona un comprobante primero.');
                return;
            }
            document.getElementById('motivo_textarea').value = ''; // Limpiar el textarea al abrir
            document.getElementById('motivoCancelacionModal').style.display = 'flex'; // Mostrar el modal
        }

        function cerrarModal() {
            document.getElementById('motivoCancelacionModal').style.display = 'none'; // Ocultar el modal
            document.getElementById('motivo_textarea').value = ''; // Limpiar el textarea
        }

        function finalizarCancelacion() {
            const motivo = document.getElementById('motivo_textarea').value.trim();

            if (motivo === '') {
                alert('El motivo de cancelación no puede estar vacío. Por favor, especifica la razón.');
                return;
            }

            // Aquí se usaría pagoSeleccionadoParaCancelar para enviar al backend
            // Forzamos el tipo de dato de ID Lote y Numero de Pago para el alert
            const idLote = pagoSeleccionadoParaCancelar.id_lote;
            const numeroPago = pagoSeleccionadoParaCancelar.numero_pago;


            alert(`Comprobante para ID Lote: ${idLote}, Número de Pago: ${numeroPago} CANCELADO exitosamente.
Motivo: "${motivo}"
(Simulación: En una implementación real, se enviaría al backend y se actualizaría el estado en la base de datos.)`);

            cerrarModal(); // Ocultar el modal
            ocultarDetalle(); // Ocultar los detalles del comprobante
            // Opcional: limpiar los campos de búsqueda
            document.getElementById('buscar_id_lote').value = '';
            document.getElementById('buscar_numero_pago').value = '';
        }

        function ocultarDetalle() {
            document.getElementById('comprobanteDetalle').classList.add('hidden');
            document.getElementById('noResultados').classList.add('hidden');
            pagoSeleccionadoParaCancelar = null; // Asegurarse de que no haya pago seleccionado
        }

        // Simple logout function for demonstration
        function logout() {
            alert('Has cerrado sesión. Redirigiendo al login...');
            window.location.href = 'login.html'; // Redirect to login page
        }
    </script>
</body>
</html>