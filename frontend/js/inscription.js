document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('inscriptionForm');

    function clearErrors() {
        const errorElements = document.querySelectorAll('.error-message');
        errorElements.forEach(function(element) {
            element.remove();
        });
        const errorFields = document.querySelectorAll('.error');
        errorFields.forEach(function(field) {
            field.classList.remove('error');
            field.removeAttribute('aria-invalid');
        });
    }

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        field.classList.add('error');
        field.setAttribute('aria-invalid', 'true');
        const errorId = fieldId + '-error';
        field.setAttribute('aria-describedby', errorId);
        const errorDiv = document.createElement('div');
        errorDiv.id = errorId;
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    function showGeneralError(message) {
        // Supprimer les anciens messages généraux
        const oldError = document.querySelector('.general-error');
        if (oldError) oldError.remove();
        
        // Créer le bandeau
        const errorBanner = document.createElement('div');
        errorBanner.className = 'general-error';
        errorBanner.textContent = message;
        
        // Insérer en haut du formulaire
        form.parentNode.insertBefore(errorBanner, form);
    }

    function showSuccessMessage(message) {
        // Supprimer les anciens messages généraux
        const oldMessage = document.querySelector('.success-message');
        if (oldMessage) oldMessage.remove();
        
        // Créer le bandeau
        const successBanner = document.createElement('div');
        successBanner.className = 'success-message';
        successBanner.textContent = message;
        
        // Insérer en haut du formulaire
        form.parentNode.insertBefore(successBanner, form);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = {
            firstName: document.getElementById('firstName').value.trim(),
            lastName: document.getElementById('lastName').value.trim(),
            email: document.getElementById('email').value.trim(),
            password: document.getElementById('password').value,
            phone: document.getElementById('phone').value.trim(),
            address: document.getElementById('address').value.trim(),
            city: document.getElementById('city').value.trim(),
            postalCode: document.getElementById('postalCode').value.trim()
        };
        try {
            const result = await AuthService.register(formData);
            if(result.ok && result.data.success) {
                showSuccessMessage('Inscription réussie ! Redirection en cours...');
                setTimeout(() => {
                    window.location.href = '/home';
                }, 2000);
            } else {
                clearErrors();
                if(result.data.errors) {
                    for(let [fieldName, errorMessage] of Object.entries(result.data.errors)) {
                        showError(fieldName, errorMessage);
                    }
                }
            }
        } catch (error) {
            console.error('❌ Erreur réseau:', error);
            showGeneralError('Impossible de contacter le serveur. Vérifiez votre connexion.');
        }
    });
});