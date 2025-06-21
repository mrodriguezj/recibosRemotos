// js/registro_usuario.js

document.addEventListener('DOMContentLoaded', () => {
    const registroUsuarioForm = document.getElementById('registroUsuarioForm');
    const responseModal = document.getElementById('responseModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');

    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    // Mapeo de campos a sus patrones de limpieza y mensajes
    const fieldValidationRules = {
        nombre_completo: { pattern: /^[A-Za-zÁáÉéÍíÓóÚúÜüÑñ\s]+$/, message: 'Solo letras y espacios.' },
        nombre_usuario: { pattern: /^[A-Za-z0-9_]+$/, message: 'Solo letras, números y guiones bajos.' },
        email: { pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: 'Debe ser un correo electrónico válido.' },
        password: { minlength: 8, message: 'La contraseña debe tener al menos 8 caracteres.' },
        confirm_password: { message: 'Las contraseñas no coinciden.' }, // Mensaje específico manejado en JS
        rol: { message: 'Debe seleccionar un rol.' }
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
        const messageSpan = input.nextElementSibling; // Asume que el span .validation-message es el siguiente hermano
        const rules = fieldValidationRules[fieldName];

        let originalValue = input.value;
        let cleanedValue = originalValue;

        // Limpieza de caracteres y conversión a mayúsculas
        if (fieldName === 'nombre_completo') {
            cleanedValue = originalValue.replace(/[^A-Za-zÁáÉéÍíÓóÚúÜüÑñ\s]/g, '').toUpperCase();
        } else if (fieldName === 'nombre_usuario') {
            cleanedValue = originalValue.replace(/[^A-Za-z0-9_]/g, ''); // Sin uppercase, ya que los nombres de usuario pueden ser sensibles a mayúsculas
        } else if (fieldName === 'email') {
            // No se limpia aquí para permitir formato de email, validación por pattern/type
        } else if (fieldName === 'password' || fieldName === 'confirm_password') {
            // Las contraseñas no se limpian de caracteres ni se convierten a mayúsculas
        } else if (fieldName === 'rol') {
            // No aplica limpieza para select
        }
        
        if (cleanedValue !== originalValue) {
            input.value = cleanedValue;
        }
        
        // Ejecutar la validación del HTML5 (required, pattern, type, minlength)
        let isValid = input.validity.valid;

        // Validación específica para la confirmación de contraseña
        if (fieldName === 'confirm_password') {
            if (passwordInput.value !== confirmPasswordInput.value) {
                isValid = false;
                input.setCustomValidity(fieldValidationRules.confirm_password.message); // Establecer mensaje de error custom
            } else {
                input.setCustomValidity(''); // Limpiar mensaje de error custom
            }
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

                // Mensajes específicos de error
                if (input.validity.valueMissing) {
                    messageSpan.innerText = 'Este campo es requerido.';
                } else if (input.validity.typeMismatch && input.type === 'email') {
                    messageSpan.innerText = fieldValidationRules.email.message;
                } else if (input.validity.tooShort && rules && rules.minlength) {
                    messageSpan.innerText = `Debe tener al menos ${rules.minlength} caracteres.`;
                } else if (input.validity.customError && fieldName === 'confirm_password') {
                    messageSpan.innerText = fieldValidationRules.confirm_password.message;
                } else if (input.validity.patternMismatch && rules && rules.pattern) {
                    messageSpan.innerText = rules.message;
                } else {
                    messageSpan.innerText = 'Formato inválido.';
                }
            }
        }
        return isValid;
    };

    // Añadir listeners para validación activa y limpieza al escribir y al perder el foco
    registroUsuarioForm.querySelectorAll('input, select').forEach(input => {
        input.addEventListener('input', () => validateAndCleanField(input));
        input.addEventListener('blur', () => validateAndCleanField(input));
    });

    // Listener especial para confirmar contraseña en tiempo real
    passwordInput.addEventListener('input', () => validateAndCleanField(confirmPasswordInput));
    confirmPasswordInput.addEventListener('input', () => validateAndCleanField(confirmPasswordInput));


    // Manejar el envío del formulario
    registroUsuarioForm.addEventListener('submit', async (event) => {
        event.preventDefault(); // Evitar el envío por defecto del formulario

        // Realizar validación final de todos los campos
        let formIsValid = true;
        registroUsuarioForm.querySelectorAll('input[required], select[required], input[type="email"]').forEach(input => {
            if (!validateAndCleanField(input)) { // Valida los campos requeridos y email
                formIsValid = false;
            }
        });
        // Validar campos de contraseña incluso si no tienen 'required' (siempre deben validarse)
        if (!validateAndCleanField(passwordInput)) formIsValid = false;
        if (!validateAndCleanField(confirmPasswordInput)) formIsValid = false;


        if (!formIsValid) {
            showModal('Error de Validación', 'Por favor, corrige los campos marcados antes de continuar.', false);
            return;
        }

        const formData = new FormData(registroUsuarioForm);
        const data = Object.fromEntries(formData.entries());

        // Eliminar la confirmación de contraseña antes de enviar
        delete data.confirm_password; 
        
        try {
            const response = await fetch('usuarios.php', { // Asegúrate de que esta URL sea correcta
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showModal('Registro Exitoso', result.message || 'Usuario registrado correctamente.', true);
                registroUsuarioForm.reset(); // Limpiar el formulario
                // Limpiar los estilos de validación después de resetear
                registroUsuarioForm.querySelectorAll('input, select').forEach(input => {
                    input.classList.remove('border-red-500');
                    input.classList.add('border-gray-300');
                    const messageSpan = input.nextElementSibling;
                    if (messageSpan && messageSpan.classList.contains('validation-message')) {
                        messageSpan.style.display = 'none';
                    }
                });
            } else {
                showModal('Error en el Registro', result.message || 'Ocurrió un error al registrar el usuario.', false);
            }
        } catch (error) {
            console.error('Error al enviar formulario:', error);
            showModal('Error de Conexión', 'No se pudo conectar con el servidor. Inténtalo de nuevo más tarde.', false);
        }
    });
});