// js/registro_pago.js

document.addEventListener('DOMContentLoaded', () => {
    const registroPagoForm = document.getElementById('registroPagoForm');
    const clienteIdSelect = document.getElementById('cliente_id');
    const responseModal = document.getElementById('responseModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');

    // Mapeo de campos a sus reglas de validación y mensajes
    const fieldValidationRules = {
        id_lote: { message: 'Debe ser un número entero positivo para el ID de Lote.' },
        cliente_id: { message: 'Debe seleccionar un cliente.' },
        fecha_esperada_pago: { message: 'Este campo es requerido y debe ser una fecha válida.' },
        fecha_pago: { message: 'Este campo es requerido y debe ser una fecha válida.' },
        categoria_pago: { message: 'Debe seleccionar una categoría de pago.' },
        monto_pagado: { message: 'Debe ser un monto positivo (ej. 1500.00).' },
        metodo_pago: { message: 'Debe seleccionar un método de pago.' }, // Aunque es solo EFECTIVO, se valida que esté seleccionado
        observaciones_pago: { message: 'Observaciones inválidas.' } // Para un futuro, si se añade patrón
    };

    // --- Funciones del Modal ---
    const showModal = (title, message, isSuccess) => {
        modalTitle.innerText = title;
        modalMessage.innerText = message;
        if (isSuccess) {
            modalTitle.classList.remove('text-red-800');
            modalTitle.classList.add('text-green-800');
        } else {
            modalTitle.classList.remove('text-green-800');
            modalTitle.classList.add('text-red-800');
        }
        responseModal.style.display = 'flex';
    };

    // --- Validación y Limpieza de Campos (para campos numéricos o texto libre) ---
    const validateAndCleanField = (input) => {
        const fieldName = input.name;
        const messageSpan = input.nextElementSibling;
        const rules = fieldValidationRules[fieldName];

        // Validar validez básica de HTML5 (required, type, min, etc.)
        let isValid = input.validity.valid;
        
        // Validación específica para el campo id_lote (solo números enteros positivos)
        if (fieldName === 'id_lote') {
            const value = parseInt(input.value);
            if (isNaN(value) || value <= 0 || !Number.isInteger(value)) {
                isValid = false;
                input.setCustomValidity(rules.message);
            } else {
                input.setCustomValidity('');
            }
        }
        // Validación para montos (type="number" con step="0.01" ya lo maneja bien)
        // Validación para selects (required ya lo maneja bien)


        if (messageSpan && messageSpan.classList.contains('validation-message')) {
            if (isValid) {
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300');
                messageSpan.style.display = 'none';
            } else {
                input.classList.add('border-red-500');
                input.classList.remove('border-gray-300');
                messageSpan.style.display = 'block';

                // Mensajes específicos de error
                if (input.validity.valueMissing) {
                    messageSpan.innerText = 'Este campo es requerido.';
                } else if (input.validity.typeMismatch) {
                    messageSpan.innerText = 'El formato de este campo es inválido.';
                } else if (input.validity.badInput) {
                    messageSpan.innerText = 'Valor no válido.';
                } else if (input.validity.rangeUnderflow) { // Para min en números
                    messageSpan.innerText = `El valor debe ser al menos ${input.min}.`;
                } else if (input.validity.customError && rules) {
                    messageSpan.innerText = rules.message; // Mensaje de validación custom (ej. para id_lote)
                }
                 else {
                    messageSpan.innerText = rules.message || 'Campo inválido.';
                }
            }
        }
        return isValid;
    };


    // --- Llenar el Select de Clientes ---
    const fetchAndPopulateClients = async () => {
        try {
            // Asumiendo que api/clientes/listar.php está en la misma carpeta que api/pagos/registrar.php,
            // y que este js está en js/
            const response = await fetch('listar.php'); // Ajusta esta URL si tu API está en otra parte
            const result = await response.json();

            clienteIdSelect.innerHTML = ''; // Limpiar opciones existentes
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.innerText = 'Seleccionar Cliente';
            clienteIdSelect.appendChild(defaultOption);

            if (result.success && result.data.length > 0) {
                result.data.forEach(cliente => {
                    const option = document.createElement('option');
                    option.value = cliente.id_cliente;
                    option.innerText = `${cliente.nombres} ${cliente.apellido_paterno}`;
                    if (cliente.apellido_materno) {
                        option.innerText += ` ${cliente.apellido_materno}`;
                    }
                    clienteIdSelect.appendChild(option);
                });
            } else {
                const noClientsOption = document.createElement('option');
                noClientsOption.value = '';
                noClientsOption.innerText = 'No se encontraron clientes';
                clienteIdSelect.appendChild(noClientsOption);
                clienteIdSelect.disabled = true;
            }
            validateAndCleanField(clienteIdSelect); // Valida el select al llenarlo

        } catch (error) {
            console.error('Error al obtener clientes:', error);
            clienteIdSelect.innerHTML = '<option value="">Error al cargar clientes</option>';
            clienteIdSelect.disabled = true;
            showModal('Error de Carga', 'No se pudieron cargar los clientes. Inténtalo de nuevo más tarde.', false);
        }
    };

    // --- Añadir Listeners de Eventos ---
    registroPagoForm.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('input', () => validateAndCleanField(input));
        input.addEventListener('blur', () => validateAndCleanField(input));
        // Listener especial para los selects al cambiar
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', () => validateAndCleanField(input));
        }
    });

    // --- Manejar el Envío del Formulario ---
    registroPagoForm.addEventListener('submit', async (event) => {
        event.preventDefault(); // Evitar el envío por defecto

        let formIsValid = true;
        // Validar todos los campos antes de enviar
        registroPagoForm.querySelectorAll('input[required], select[required], textarea').forEach(input => {
            if (!validateAndCleanField(input)) {
                formIsValid = false;
            }
        });

        if (!formIsValid) {
            showModal('Error de Validación', 'Por favor, corrige los campos marcados antes de continuar.', false);
            return;
        }

        const formData = new FormData(registroPagoForm);
        const data = Object.fromEntries(formData.entries());

        // Manejar campos opcionales que podrían ser vacíos (enviar null en lugar de string vacío)
        if (data.observaciones_pago === '') data.observaciones_pago = null;
        
        // No enviamos estado_pago, fecha_creacion, ultima_actualizacion, usuario_realizador_id
        // porque se manejan en el backend (SP)
        // Eliminar numero_pago_registro si aún existe del HTML previo
        delete data.numero_pago_registro; 

        try {
            const response = await fetch('registrar.php', { // URL al API de registro de pagos
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showModal('Registro Exitoso', result.message || 'Comprobante registrado correctamente.', true);
                registroPagoForm.reset(); // Limpiar el formulario
                // Limpiar estilos de validación y mensajes
                registroPagoForm.querySelectorAll('input, select, textarea').forEach(input => {
                    input.classList.remove('border-red-500');
                    input.classList.add('border-gray-300');
                    const messageSpan = input.nextElementSibling;
                    if (messageSpan && messageSpan.classList.contains('validation-message')) {
                        messageSpan.style.display = 'none';
                    }
                });
                // Volver a cargar clientes si fuera necesario (ej. para un nuevo registro rápido)
                // fetchAndPopulateClients();
            } else {
                showModal('Error en el Registro', result.message || 'Ocurrió un error al registrar el comprobante.', false);
            }
        } catch (error) {
            console.error('Error al enviar formulario:', error);
            showModal('Error de Conexión', 'No se pudo conectar con el servidor. Inténtalo de nuevo más tarde.', false);
        }
    });

    // --- Carga inicial de datos ---
    fetchAndPopulateClients(); // Cargar clientes al cargar la página


    // FUNCIÓN LOGOUT - Llama a la API de revocación de token
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