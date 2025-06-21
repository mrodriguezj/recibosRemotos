<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Comprobante de Pago - CobranzaPro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos personalizados si son necesarios */
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
            <form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2">
                <div class="md:col-span-1">
                    <label for="lote_id" class="block text-sm font-medium text-gray-700 mb-1">ID Lote</label>
                    <input type="text" id="lote_id" name="lote_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: LOTE-001">
                </div>
                <div class="md:col-span-1">
                    <label for="cliente_nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Cliente</label>
                    <input type="text" id="cliente_nombre" name="cliente_nombre" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Nombre completo del cliente">
                </div>
                <div class="md:col-span-1">
                    <label for="numero_pago_registro" class="block text-sm font-medium text-gray-700 mb-1">Número de Pago</label>
                    <input type="number" id="numero_pago_registro" name="numero_pago_registro" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: 1, 2, 3">
                </div>
                <div class="md:col-span-1">
                    <label for="fecha_pago" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago</label>
                    <input type="date" id="fecha_pago" name="fecha_pago" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="md:col-span-1">
                    <label for="categoria_pago_registro" class="block text-sm font-medium text-gray-700 mb-1">Categoría de Pago</label>
                    <select id="categoria_pago_registro" name="categoria_pago_registro" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar categoría</option>
                        <option value="Enganche">Enganche</option>
                        <option value="Contado">Contado</option>
                        <option value="Mensualidad">Mensualidad</option>
                        <option value="Anualidad">Anualidad</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label for="metodo_pago_registro" class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                    <select id="metodo_pago_registro" name="metodo_pago_registro" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar método</option>
                        <option value="Deposito">Depósito</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Tarjeta">Tarjeta de Crédito/Débito</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label for="monto_pagado_registro" class="block text-sm font-medium text-gray-700 mb-1">Monto Pagado</label>
                    <input type="number" id="monto_pagado_registro" name="monto_pagado_registro" step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: 1500.00">
                </div>
                <div class="md:col-span-1">
                    <label for="referencia_pago" class="block text-sm font-medium text-gray-700 mb-1">Referencia/Concepto (Opcional)</label>
                    <input type="text" id="referencia_pago" name="referencia_pago" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Referencia bancaria, etc.">
                </div>
                <div class="md:col-span-2">
                    <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones (Opcional)</label>
                    <textarea id="observaciones" name="observaciones" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Cualquier nota adicional sobre el pago"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end space-x-4">
                    <button type="button" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-save mr-2"></i> Registrar Comprobante
                    </button>
                </div>
            </form>
        </section>
    </main>

    <script>
        // Simple logout function for demonstration
        function logout() {
            alert('Has cerrado sesión. Redirigiendo al login...');
            window.location.href = 'login.html'; // Redirect to login page
        }
    </script>
</body>
</html>