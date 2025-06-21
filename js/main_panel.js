// js/main_panel.js

document.addEventListener('DOMContentLoaded', () => {
    // Funciones de utilidad para la fecha de ejecución
    const updateExecutionDate = () => {
        const now = new Date();
        const options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            second: 'numeric',
            timeZoneName: 'short',
            timeZone: 'America/Cancun' //
        };
        document.getElementById("fecha-ejecucion").innerText = "Actualizado el: " +
            now.toLocaleString("es-MX", options);
    };

    updateExecutionDate(); // Llama a la función al cargar

    // --- Variables de estado para la paginación y ordenación ---
    let currentPage = 1;
    const itemsPerPage = 10;
    let currentSortColumn = 'fecha_pago'; // Columna por defecto para ordenar
    let currentSortDirection = 'desc'; // Dirección por defecto

    // Referencias a elementos del DOM
    const ultimosPagosBody = document.getElementById('ultimos-pagos-body');
    const searchInput = document.getElementById('searchInput');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const paginationInfo = document.getElementById('pagination-info');

    // --- Función principal para cargar y mostrar pagos ---
    const loadPayments = async () => {
        ultimosPagosBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-gray-500">Cargando datos...</td></tr>'; // Colspan 7 para la nueva columna

        const searchTerm = searchInput.value.trim();
        const offset = (currentPage - 1) * itemsPerPage;

        // Construir URL de la API
        // Asegúrate de que la URL apunte correctamente a tu script PHP (api/pagos.php)
        const apiUrl = `pagos.php?limit=${itemsPerPage}&offset=${offset}&page=${currentPage}&search=${searchTerm}&sort_column=${currentSortColumn}&sort_direction=${currentSortDirection}`;

        try {
            const response = await fetch(apiUrl);
            const result = await response.json();

            if (result.success) {
                renderPayments(result.data);
                updatePaginationControls(result.total_records, result.total_pages);
            } else {
                ultimosPagosBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-500">Error al cargar datos: ${result.message}</td></tr>`;
                updatePaginationControls(0, 0);
            }
        } catch (error) {
            console.error('Error fetching payments:', error);
            ultimosPagosBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-500">Error de red o servidor: ${error.message}</td></tr>`;
            updatePaginationControls(0, 0);
        }
    };

    // --- Función para renderizar los pagos en la tabla ---
    const renderPayments = (pagos) => {
        ultimosPagosBody.innerHTML = ''; // Limpiar filas existentes

        if (pagos.length === 0) {
            ultimosPagosBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-gray-500">No se encontraron resultados.</td></tr>';
            return;
        }

        pagos.forEach(pago => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${pago.id_lote}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${pago.nombres} ${pago.apellido_paterno || ''}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$${parseFloat(pago.monto_pagado).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${pago.fecha_pago}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium category-${pago.categoria_pago}">${pago.categoria_pago}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 ${pago.estado_pago === 'Cancelado' ? 'text-red-600 font-semibold' : 'text-green-600'}">${pago.estado_pago}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="downloadPaymentPDF('${pago.id_lote}', '${pago.nombres} ${pago.apellido_paterno}', '${pago.monto_pagado}')" class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-file-pdf mr-1"></i> Descargar PDF
                    </button>
                </td>
            `;
            ultimosPagosBody.appendChild(row);
        });
    };

    // --- Funciones para la paginación ---
    const updatePaginationControls = (totalRecords, totalPages) => {
        const start = (itemsPerPage * (currentPage - 1)) + 1;
        const end = Math.min(itemsPerPage * currentPage, totalRecords);

        paginationInfo.innerHTML = `Mostrando ${start} a ${end} de ${totalRecords} resultados`;

        prevPageBtn.disabled = currentPage <= 1;
        nextPageBtn.disabled = currentPage >= totalPages;

        // Si no hay resultados, deshabilitar ambos botones
        if (totalRecords === 0) {
            prevPageBtn.disabled = true;
            nextPageBtn.disabled = true;
        }
    };

    prevPageBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadPayments();
        }
    });

    nextPageBtn.addEventListener('click', () => {
        // La condición para deshabilitar nextPageBtn ya se maneja en updatePaginationControls
        // Simplemente llamamos a loadPayments si no está deshabilitado
        if (!nextPageBtn.disabled) {
            currentPage++;
            loadPayments();
        }
    });

    // --- Funciones para búsqueda y ordenación ---
    searchInput.addEventListener('keyup', () => {
        // Reiniciar a la primera página al buscar
        currentPage = 1;
        loadPayments();
    });

    document.querySelectorAll('th[data-sort-by]').forEach(header => {
        header.addEventListener('click', () => {
            const column = header.getAttribute('data-sort-by');
            if (currentSortColumn === column) {
                currentSortDirection = (currentSortDirection === 'asc') ? 'desc' : 'asc';
            } else {
                currentSortColumn = column;
                currentSortDirection = 'asc'; // Por defecto ASC cuando se cambia de columna
            }
            currentPage = 1; // Reiniciar a la primera página al ordenar
            updateSortIcons();
            loadPayments();
        });
    });

    // --- Función para actualizar los iconos de ordenación ---
    const updateSortIcons = () => {
        document.querySelectorAll('th[data-sort-by]').forEach(header => {
            const icon = header.querySelector('.sort-icon');
            if (icon) {
                icon.classList.remove('fa-sort-up', 'fa-sort-down');
                icon.classList.add('fa-sort'); // Reset a icono por defecto
            }
            if (header.getAttribute('data-sort-by') === currentSortColumn) {
                if (icon) {
                    icon.classList.remove('fa-sort');
                    icon.classList.add(currentSortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
                }
            }
        });
    };

    // --- Funciones externas (accesibles globalmente desde el HTML) ---
    window.downloadPaymentPDF = (loteId, cliente, monto) => {
        alert(`Simulando descarga de PDF para:
ID Lote: ${loteId}
Cliente: ${cliente}
Monto: $${monto}
(En una implementación real, esto generaría un PDF.)`);
    };

    window.logout = () => {
        alert('Has cerrado sesión. Redirigiendo al login...');
        window.location.href = 'login.html';
    };

    // Cargar pagos al inicio y actualizar íconos de ordenación
    loadPayments();
    updateSortIcons();
});