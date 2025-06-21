// js/agregar_cliente.js

document.addEventListener('DOMContentLoaded', () => {
    const clienteForm = document.getElementById('clienteForm');
    const responseModal = document.getElementById('responseModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');

    // Mapeo de campos a sus patrones de limpieza y mensajes
    const fieldValidationRules = {
        nombres: { pattern: /^[A-Za-zÁáÉéÍíÓóÚúÜüÑñ\s]+$/, message: 'Solo letras y espacios.' },
        apellido_paterno: { pattern: /^[A-Za-zÁáÉéÍíÓóÚúÜüÑñ\s]+$/, message: 'Solo letras y espacios.' },
        apellido_materno: { pattern: /^[A-Za-zÁáÉéÍíÓóÚúÜüÑñ\s]*$/, message: 'Solo letras y espacios.' }, // No requerido
        correo_electronico: { pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: 'Debe ser un correo electrónico válido.' },
        telefono: { pattern: /^[0-9]{10}$/, message: 'Debe ser un número de 10 dígitos.' },
        curp: { pattern: /^[A-Z0-9]{18}$/, message: 'CURP inválida (18 caracteres alfanuméricos).' }, // Patrón actualizado
        rfc: { pattern: /^[A-Z0-9]{12,13}$/, message: 'RFC inválido (12 o 13 caracteres alfanuméricos).' }, // Patrón actualizado (sin Ñ y & para mayor simplicidad en el regex, solo letras y números)
        ine: { pattern: /^[A-Z0-9]{15}$/, message: 'INE inválido (15 caracteres alfanuméricos).' } // Patrón actualizado
    };

    // Función para mostrar el modal de respuesta
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
        responseModal.style.display = 'flex'; // Usar 'flex' para centrar
    };

    // Función de validación y limpieza para los campos
    const validateAndCleanField = (input) => {
        const fieldName = input.name;
        const messageSpan = input.nextElementSibling;
        const rules = fieldValidationRules[fieldName];

        let originalValue = input.value;
        let cleanedValue = originalValue;

        // --- Limpieza de caracteres no deseados mientras el usuario escribe ---
        if (fieldName === 'nombres' || fieldName === 'apellido_paterno' || fieldName === 'apellido_materno') {
            cleanedValue = originalValue.replace(/[^A-Za-zÁáÉéÍíÓóÚúÜüÑñ\s]/g, '');
        } else if (fieldName === 'telefono') {
            cleanedValue = originalValue.replace(/[^0-9]/g, '');
        } else if (fieldName === 'curp' || fieldName === 'rfc' || fieldName === 'ine') { // CURP, RFC, INE ahora manejan aquí
            cleanedValue = originalValue.replace(/[^A-Za-z0-9]/g, '');
        }
        // Correo electrónico y dirección se manejan de forma diferente.

        // --- Conversión a MAYÚSCULAS para campos específicos ---
        if (fieldName === 'nombres' || 
            fieldName === 'apellido_paterno' || 
            fieldName === 'apellido_materno' || 
            fieldName === 'curp' || 
            fieldName === 'rfc' || 
            fieldName === 'ine') 
        {
            cleanedValue = cleanedValue.toUpperCase();
        }
        
        // Actualizar el valor del input solo si ha habido cambios
        if (cleanedValue !== originalValue) {
            input.value = cleanedValue;
        }
        
        // Ejecutar la validación del HTML5 (required, pattern, type)
        const isValid = input.validity.valid;

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
                } else if (input.validity.typeMismatch && input.type === 'email') {
                    messageSpan.innerText = fieldValidationRules.correo_electronico.message;
                } else if (input.validity.patternMismatch && rules) {
                    messageSpan.innerText = rules.message;
                } else {
                    messageSpan.innerText = 'Formato inválido.';
                }
            }
        }
        return isValid;
    };

    // Añadir listeners para validación activa y limpieza al escribir y al perder el foco
    clienteForm.querySelectorAll('input, textarea').forEach(input => {
        input.addEventListener('input', () => validateAndCleanField(input));
        input.addEventListener('blur', () => validateAndCleanField(input));
    });


    // Manejar el envío del formulario
    clienteForm.addEventListener('submit', async (event) => {
        event.preventDefault(); // Evitar el envío por defecto del formulario

        // Realizar validación final de todos los campos
        let formIsValid = true;
        clienteForm.querySelectorAll('input, textarea').forEach(input => {
            // Validar todos los campos que tienen atributos de validación o son tipo email
            if (input.hasAttribute('required') || input.hasAttribute('pattern') || input.type === 'email' || input.name === 'apellido_materno' || input.name === 'rfc' || input.name === 'direccion') {
                if (!validateAndCleanField(input)) {
                    formIsValid = false;
                }
            }
        });

        if (!formIsValid) {
            showModal('Error de Validación', 'Por favor, corrige los campos marcados antes de continuar.', false);
            return;
        }

        const formData = new FormData(clienteForm);
        const data = Object.fromEntries(formData.entries());

        // Manejar campos opcionales que podrían ser vacíos (enviar null en lugar de string vacío)
        if (data.apellido_materno === '') data.apellido_materno = null;
        if (data.rfc === '') data.rfc = null;
        if (data.direccion === '') data.direccion = null;
        
        try {
            const response = await fetch('clientes.php', { // Asegúrate de que esta URL sea correcta
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showModal('Registro Exitoso', result.message || 'Cliente registrado correctamente.', true);
                clienteForm.reset(); // Limpiar el formulario
                // Limpiar los estilos de validación después de resetear
                clienteForm.querySelectorAll('input, textarea').forEach(input => {
                    input.classList.remove('border-red-500');
                    input.classList.add('border-gray-300');
                    const messageSpan = input.nextElementSibling;
                    if (messageSpan && messageSpan.classList.contains('validation-message')) {
                        messageSpan.style.display = 'none';
                    }
                });
            } else {
                showModal('Error en el Registro', result.message || 'Ocurrió un error al registrar el cliente.', false);
            }
        } catch (error) {
            console.error('Error al enviar formulario:', error);
            showModal('Error de Conexión', 'No se pudo conectar con el servidor. Inténtalo de nuevo más tarde.', false);
        }
    });
});

// AÑADIDO: FUNCIÓN LOGOUT para ser accesible desde el HTML
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

// closeResponseModal se define globalmente en el HTML de agregar_cliente.php