// js/cambiar_contrasena_forzado.js

document.addEventListener('DOMContentLoaded', () => {
    const changePasswordForm = document.getElementById('changePasswordForm');
    const responseModal = document.getElementById('responseModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');

    const currentPasswordInput = document.getElementById('current_password');
    const newPasswordInput = document.getElementById('new_password');
    const confirmNewPasswordInput = document.getElementById('confirm_new_password');

    // Función para mostrar el modal de respuesta
    const showModal = (title, message, isSuccess, redirectTo = null) => {
        modalTitle.innerText = title;
        modalMessage.innerText = message;
        if (isSuccess) {
            modalTitle.classList.remove('text-red-800');
            modalTitle.classList.add('text-green-800');
        } else {
            modalTitle.classList.remove('text-green-800');
            modalTitle.classList.add('text-red-800');
        }
        // Almacenar la URL de redirección en un atributo de datos del modal
        if (redirectTo) {
            responseModal.dataset.redirect = redirectTo;
        } else {
            delete responseModal.dataset.redirect;
        }
        responseModal.style.display = 'flex';
    };

    // Función de validación para los campos de contraseña
    const validatePasswordField = (input) => {
        const messageSpan = input.nextElementSibling;
        let isValid = input.validity.valid;

        // Validaciones específicas para nueva contraseña y confirmación
        if (input.id === 'new_password' && input.value.length < 8) {
            isValid = false;
            input.setCustomValidity('La nueva contraseña debe tener al menos 8 caracteres.');
        } else if (input.id === 'confirm_new_password') {
            if (newPasswordInput.value !== confirmNewPasswordInput.value) {
                isValid = false;
                input.setCustomValidity('Las nuevas contraseñas no coinciden.');
            } else {
                input.setCustomValidity(''); // Limpiar si coinciden
            }
        } else {
            input.setCustomValidity(''); // Limpiar para otros campos
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
                messageSpan.innerText = input.validationMessage; // Muestra el mensaje de validación del navegador
            }
        }
        return isValid;
    };

    // Añadir listeners para validación activa
    currentPasswordInput.addEventListener('input', () => validatePasswordField(currentPasswordInput));
    currentPasswordInput.addEventListener('blur', () => validatePasswordField(currentPasswordInput));

    newPasswordInput.addEventListener('input', () => {
        validatePasswordField(newPasswordInput);
        validatePasswordField(confirmNewPasswordInput); // Validar confirmación al escribir nueva
    });
    newPasswordInput.addEventListener('blur', () => {
        validatePasswordField(newPasswordInput);
        validatePasswordField(confirmNewPasswordInput);
    });

    confirmNewPasswordInput.addEventListener('input', () => validatePasswordField(confirmNewPasswordInput));
    confirmNewPasswordInput.addEventListener('blur', () => validatePasswordField(confirmNewPasswordInput));


    // Manejar el envío del formulario
    changePasswordForm.addEventListener('submit', async (event) => {
        event.preventDefault(); // Evitar el envío por defecto

        let formIsValid = true;
        // Validar todos los campos antes de enviar
        if (!validatePasswordField(currentPasswordInput)) formIsValid = false;
        if (!validatePasswordField(newPasswordInput)) formIsValid = false;
        if (!validatePasswordField(confirmNewPasswordInput)) formIsValid = false;

        if (!formIsValid) {
            showModal('Error de Validación', 'Por favor, corrige los campos marcados antes de continuar.', false);
            return;
        }

        const formData = new FormData(changePasswordForm);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const response = await fetch('cambiar_contrasena_forzado.php', { // Enviar la solicitud al mismo script
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showModal('Éxito', result.message, true, result.redirect_to); // Pasar redirect_to al modal
                changePasswordForm.reset();
            } else {
                showModal('Error', result.message, false);
            }
        } catch (error) {
            console.error('Error al enviar formulario:', error);
            showModal('Error de Conexión', 'No se pudo conectar con el servidor. Inténtalo de nuevo más tarde.', false);
        }
    });

    // La función closeModal se define globalmente en el HTML
});