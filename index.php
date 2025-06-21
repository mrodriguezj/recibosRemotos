<?php
// index.php

// ESTA ES LA PRIMERA LÍNEA DEL ARCHIVO
// AJUSTA LA RUTA SI auth_middleware.php NO ESTÁ EN EL MISMO DIRECTORIO
require_once __DIR__ . '/auth_middleware.php';

// Si el token es válido, $user_data estará disponible aquí con los datos del usuario.
// Puedes usar $user_data['nombre_completo'] o $user_data['rol'] si lo necesitas en el HTML.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - CobranzaPro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom CSS for category/status colors (Tailwind doesn't have these by default for arbitrary values) */
        .status-vencido { @apply bg-red-100 text-red-800; }
        .status-pagado { @apply bg-green-100 text-green-800; }
        .status-pendiente { @apply bg-yellow-100 text-yellow-800; }
        .status-parcial { @apply bg-blue-100 text-blue-800; }
        .status-parcial-\(vencido\) { @apply bg-orange-100 text-orange-800; }

        .category-Enganche { color: #6f42c1; /* Morado */ }
        .category-Contado { color: #20c997; /* Verde agua */ }
        .category-Mensualidad { color: #007bff; /* Azul */ }
        .category-Anualidad { color: #fd7e14; /* Naranja */ }

        /* Estilo para los encabezados ordenables */
        th[data-sort-by] {
            cursor: pointer;
            user-select: none;
        }
        th[data-sort-by]:hover {
            background-color: #f3f4f6;
        }
        th[data-sort-by] .sort-icon {
            margin-left: 0.25rem;
        }
        .sort-up::after {
            content: "\f0d8"; /* Unicode for caret-up */
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-left: 5px;
        }
        .sort-down::after {
            content: "\f0d7"; /* Unicode for caret-down */
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-left: 5px;
        }


        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
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
        <h1 class="text-3xl font-semibold text-gray-800 mb-2">Panel Principal</h1>
        <p id="fecha-ejecucion" class="text-gray-600 text-sm mb-6">Actualizado el: </p>

        <section class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Acciones Rápidas</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="agregar_cliente.php" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center text-center">
                    <i class="fas fa-user-plus mr-2"></i> Agregar Nuevo Cliente
                </a>
                <a href="registro_pago.php" class="bg-green-600 text-white px-6 py-3 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 flex items-center justify-center text-center">
                    <i class="fas fa-hand-holding-usd mr-2"></i> Registrar Nuevo Pago
                </a>
                <a href="cancelar_comprobante.php" class="bg-red-600 text-white px-6 py-3 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 flex items-center justify-center text-center">
                    <i class="fas fa-times-circle mr-2"></i> Cancelar Comprobante
                </a>
            </div>
        </section>

        <section class="bg-white p-6 rounded-lg shadow-md mb-6">
            <div class="mb-4 flex flex-col md:flex-row md:justify-between md:items-center">
                <h2 class="text-xl font-semibold text-gray-800 mb-2 md:mb-0">Últimos 10 Registros de Pagos</h2>
                <div class="relative w-full md:w-1/3">
                    <input type="text" id="searchInput" placeholder="Buscar pagos por Lote, Cliente, Monto, Categoría, Estado..." class="p-2 border border-gray-300 rounded-md pl-10 w-full focus:ring-blue-500 focus:border-blue-500">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <div class="table-responsive">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" data-sort-by="id_lote">
                                ID Lote <span class="sort-icon fas fa-sort text-gray-400"></span>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" data-sort-by="cliente_id">
                                Cliente <span class="sort-icon fas fa-sort text-gray-400"></span>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" data-sort-by="monto_pagado">
                                Monto Pagado <span class="sort-icon fas fa-sort text-gray-400"></span>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" data-sort-by="fecha_pago">
                                Fecha Pago <span class="sort-icon fas fa-sort text-gray-400"></span>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" data-sort-by="categoria_pago">
                                Categoría <span class="sort-icon fas fa-sort text-gray-400"></span>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" data-sort-by="estado_pago">
                                Estado <span class="sort-icon fas fa-sort text-gray-400"></span>
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="ultimos-pagos-body" class="bg-white divide-y divide-gray-200">
                        <tr><td colspan="7" class="text-center py-4 text-gray-500">Cargando datos...</td></tr>
                    </tbody>
                </table>
            </div>

            <nav class="mt-4 flex items-center justify-between">
                <div id="pagination-info" class="text-sm text-gray-700">
                    Mostrando <span id="current-page-start">0</span> a <span id="current-page-end">0</span> de <span id="total-results">0</span> resultados
                </div>
                <div class="flex-1 flex justify-end">
                    <button id="prevPageBtn" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Anterior</button>
                    <button id="nextPageBtn" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Siguiente</button>
                </div>
            </nav>
        </section>

    </main>

    <script src="js/main_panel.js"></script>
</body>
</html>