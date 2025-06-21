// js/cancelar_comprobante.js

document.addEventListener('DOMContentLoaded', () => {
    const buscarComprobanteForm = document.getElementById('buscarComprobanteForm');
    const buscarIdLoteInput = document.getElementById('buscar_id_lote');
    const buscarPagoIdInput = document.getElementById('buscar_pago_id');
    const comprobanteDetalleDiv = document.getElementById('comprobanteDetalle');
    const noResultadosDiv = document.getElementById('noResultados');
    const btnConfirmarCancelacion = document.getElementById('btnConfirmarCancelacion');
    const btnCancelarBusqueda = document.getElementById('btnCancelarBusqueda');

    const motivoCancelacionModal = document.getElementById('motivoCancelacionModal');
    const motivoTextarea = document.getElementById('motivo_textarea');
    const btnFinalizarCancelacion = document.getElementById('btnFinalizarCancelacion');

    // Referencias para el modal de respuesta general (éxito/error de API)
    const responseModal = document.getElementById('responseModal');
    const responseModalTitle = document.getElementById('responseModalTitle');
    const responseModalMessage = document.getElementById('responseModalMessage');

    // Variable global para almacenar los datos del pago encontrado
    let pagoEncontradoData = null;

    // --- Funciones del Modal de Respuesta General (Éxito/Error) ---
    const showResponseModal = (title, message, isSuccess) => {
        responseModalTitle.innerText = title;
        responseModalMessage.innerText = message;
        if (isSuccess) {
            responseModalTitle.classList.remove('text-red-800');
            responseModalTitle.classList.add('text-green-800');
        } else {
            responseModalTitle.classList.remove('text-green-800');
            responseModalTitle.classList.add('text-red-800');
        }
        responseModal.style.display = 'flex';
    };

    // --- Funciones de Validación de Campos de Búsqueda ---
    const validateInputField = (input) => {
        const messageSpan = input.nextElementSibling;
        const value = parseInt(input.value);
        let isValid = true;

        if (input.value.trim() === '') {
            input.setCustomValidity('');
            isValid = true;
        } else if (isNaN(value) || value <= 0 || !Number.isInteger(value)) {
            input.setCustomValidity('Debe ser un número entero positivo.');
            isValid = false;
        } else {
            input.setCustomValidity('');
        }

        if (messageSpan && messageSpan.classList.contains('validation-message')) {
            if (isValid) {
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300');
                messageSpan.style.display = 'none';
            } else {
                input.classList.add('border-red-500');
                input.classList.remove('border-gray-300');
                messageSpan.style.display = 'block';
                messageSpan.innerText = input.validationMessage;
            }
        }
        return isValid;
    };

    // Añadir listeners para validación activa en los campos de búsqueda
    buscarIdLoteInput.addEventListener('input', () => validateInputField(buscarIdLoteInput));
    buscarIdLoteInput.addEventListener('blur', () => validateInputField(buscarIdLoteInput));
    buscarPagoIdInput.addEventListener('input', () => validateInputField(buscarPagoIdInput));
    buscarPagoIdInput.addEventListener('blur', () => validateInputField(buscarPagoIdInput));

    // --- Manejar el Envío del Formulario de Búsqueda ---
    buscarComprobanteForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        comprobanteDetalleDiv.classList.add('hidden');
        noResultadosDiv.classList.add('hidden');
        pagoEncontradoData = null;

        const idLote = buscarIdLoteInput.value.trim() === '' ? 0 : parseInt(buscarIdLoteInput.value);
        const pagoId = buscarPagoIdInput.value.trim() === '' ? 0 : parseInt(buscarPagoIdInput.value);

        let formIsValid = validateInputField(buscarIdLoteInput);
        formIsValid = validateInputField(buscarPagoIdInput) && formIsValid;

        if (idLote === 0 && pagoId === 0) {
            showResponseModal('Error de Búsqueda', 'Debe proporcionar un ID de Lote o un ID de Pago para buscar.', false);
            return;
        }
        if (!formIsValid) {
             showResponseModal('Error de Búsqueda', 'Por favor, corrija los campos de búsqueda.', false);
             return;
        }

        try {
            const response = await fetch('buscarPago.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_lote: idLote, pago_id: pagoId })
            });

            const result = await response.json();

            if (result.success && result.data) {
                pagoEncontradoData = result.data;
                renderComprobanteDetalle(pagoEncontradoData);
                comprobanteDetalleDiv.classList.remove('hidden');
            } else {
                noResultadosDiv.classList.add('hidden');
                showResponseModal('Comprobante No Encontrado', result.message || 'No se encontró ningún comprobante con los criterios de búsqueda o no está vigente.', false);
            }
        } catch (error) {
            console.error('Error al buscar comprobante:', error);
            showResponseModal('Error de Conexión', 'No se pudo conectar con el servidor para buscar el comprobante. Inténtalo de nuevo más tarde.', false);
        }
    });

    // --- Función para rellenar los detalles del comprobante ---
    const renderComprobanteDetalle = (pago) => {
        document.getElementById('detalle_pago_id_oculto').value = pago.pago_id;
        document.getElementById('detalle_id_lote_oculto').value = pago.id_lote;

        document.getElementById('detalle_pago_id').innerText = pago.pago_id;
        document.getElementById('detalle_id_lote').innerText = pago.id_lote;
        document.getElementById('detalle_cliente').innerText = `${pago.nombres} ${pago.apellido_paterno} ${pago.apellido_materno || ''}`;
        document.getElementById('detalle_fecha_esperada_pago').innerText = pago.fecha_esperada_pago;
        document.getElementById('detalle_fecha_pago').innerText = pago.fecha_pago;
        document.getElementById('detalle_categoria_pago').innerText = pago.categoria_pago;
        document.getElementById('detalle_monto_pagado').innerText = `$${parseFloat(pago.monto_pagado).toFixed(2)}`;
        document.getElementById('detalle_metodo_pago').innerText = pago.metodo_pago;
        document.getElementById('detalle_estado_pago').innerText = pago.estado_pago;
        document.getElementById('detalle_observaciones_pago').innerText = pago.observaciones_pago || 'N/A';

        if (pago.estado_pago === 'Cancelado') {
            btnConfirmarCancelacion.disabled = true;
            btnConfirmarCancelacion.innerText = 'Pago Ya Cancelado';
            btnConfirmarCancelacion.classList.remove('bg-red-600', 'hover:bg-red-700');
            btnConfirmarCancelacion.classList.add('bg-gray-400');
        } else {
            btnConfirmarCancelacion.disabled = false;
            btnConfirmarCancelacion.innerText = 'Confirmar Cancelación';
            btnConfirmarCancelacion.classList.add('bg-red-600', 'hover:bg-red-700');
            btnConfirmarCancelacion.classList.remove('bg-gray-400');
        }
    };

    // --- Manejar el Flujo de Cancelación ---
    btnConfirmarCancelacion.addEventListener('click', () => {
        if (!pagoEncontradoData || pagoEncontradoData.estado_pago === 'Cancelado') {
            showResponseModal('Error', 'No hay un comprobante válido seleccionado o ya está cancelado.', false);
            return;
        }
        motivoTextarea.value = ''; // Limpiar el campo del motivo
        motivoCancelacionModal.style.display = 'flex'; // Mostrar el modal del motivo
        validateMotivoTextarea(); // Valida el textarea al mostrarlo
    });

    btnFinalizarCancelacion.addEventListener('click', async () => {
        // Validación del motivo antes de enviar
        if (!validateMotivoTextarea()) {
            showResponseModal('Error de Validación', 'Por favor, especifica el motivo de la cancelación.', false);
            return;
        }

        // --- INICIO DEPURACIÓN JS ---
        const motivo = motivoTextarea.value.trim();
        console.log("DEBUG JS - rawValue:", motivoTextarea.value, " (Length:", motivoTextarea.value.length, ")");
        console.log("DEBUG JS - trimmedValue:", motivo, " (Length:", motivo.length, ")");
        // --- FIN DEPURACIÓN JS ---

        closeMotivoModal(); // Ocultar el modal del motivo

        const pagoIdToCancel = document.getElementById('detalle_pago_id_oculto').value;
        const idLote = document.getElementById('detalle_id_lote_oculto').value; 
        
        try {
            const response = await fetch('cancelar.php', { // URL al API de cancelación
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    pago_id: pagoIdToCancel,
                    motivo_cancelacion: motivo,
                    id_lote: idLote
                })
            });

            const result = await response.json();

            if (result.success) {
                showResponseModal('Cancelación Exitosa', result.message || 'El comprobante ha sido cancelado.', true);
                buscarComprobanteForm.reset();
                comprobanteDetalleDiv.classList.add('hidden');
                noResultadosDiv.classList.add('hidden');
                pagoEncontradoData = null;
                buscarIdLoteInput.classList.remove('border-red-500', 'border-gray-300');
                buscarIdLoteInput.nextElementSibling.style.display = 'none';
                buscarPagoIdInput.classList.remove('border-red-500', 'border-gray-300');
                buscarPagoIdInput.nextElementSibling.style.display = 'none';

            } else {
                showResponseModal('Error al Cancelar', result.message || 'Ocurrió un error al intentar cancelar el comprobante.', false);
            }
        } catch (error) {
            console.error('Error al enviar solicitud de cancelación:', error);
            showResponseModal('Error de Conexión', 'No se pudo conectar con el servidor para cancelar. Inténtalo de nuevo más tarde.', false);
        }
    });

    btnCancelarBusqueda.addEventListener('click', () => {
        buscarComprobanteForm.reset();
        comprobanteDetalleDiv.classList.add('hidden');
        noResultadosDiv.classList.add('hidden');
        pagoEncontradoData = null;
        buscarIdLoteInput.classList.remove('border-red-500', 'border-gray-300');
        buscarIdLoteInput.nextElementSibling.style.display = 'none';
        buscarPagoIdInput.classList.remove('border-red-500', 'border-gray-300');
        buscarPagoIdInput.nextElementSibling.style.display = 'none';
    });

    // Validar el textarea del motivo de cancelación (solo requerido, sin minlength explícito en JS)
    const validateMotivoTextarea = () => {
        const value = motivoTextarea.value.trim();
        const isRequiredValid = value !== ''; // Solo verifica si no está vacío

        const messageSpan = motivoTextarea.nextElementSibling;
        
        if (isRequiredValid) { // Solo si no está vacío
            motivoTextarea.classList.remove('border-red-500');
            motivoTextarea.classList.add('border-gray-300');
            if (messageSpan) messageSpan.style.display = 'none';
        } else {
            motivoTextarea.classList.add('border-red-500');
            motivoTextarea.classList.remove('border-gray-300');
            if (messageSpan) {
                messageSpan.innerText = 'El motivo de cancelación es requerido.';
                messageSpan.style.display = 'block';
            }
        }
        return isRequiredValid;
    };

    motivoTextarea.addEventListener('input', validateMotivoTextarea);
    motivoTextarea.addEventListener('blur', validateMotivoTextarea);

    // Las funciones globales closeResponseModal() y closeMotivoModal() están en el HTML para onClick
    // FUNCIÓN LOGOUT - Llama a la API de revocación de token (añadida aquí para este módulo)
    window.logout = async () => {
        try {
            const response = await fetch('logout.php', { // URL a tu API de logout
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({})
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message || 'Sesión cerrada exitosamente.');
            } else {
                alert('Error al cerrar sesión: ' + (result.message || 'Inténtalo de nuevo.'));
            }
        } catch (error) {
            console.error('Error al comunicarse con la API de logout:', error);
            alert('Error de conexión al cerrar sesión. Inténtalo de nuevo.');
        } finally {
            window.location.href = 'login.php';
        }
    };
});