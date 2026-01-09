document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');

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

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const formData = {
            email: document.getElementById('email').value.trim(),
            password: document.getElementById('password').value
        };
        fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData),
            credentials: 'include'  // Envoie et reçoit les cookies automatiquement
        })
        .then(response => {           
            // Vérifier si la réponse est bien du JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Le serveur n\'a pas renvoyé de JSON (HTTP ' + response.status + ')');
            }
            
            // Parser le JSON
            return response.json().then(data => ({
                status: response.status,
                ok: response.ok,
                data: data
            }));
        })
        .then(result => {
            if(result.ok && result.data.success) {
                // Succès (201)
                // Le token JWT est automatiquement stocké dans un cookie httpOnly
                // Pas besoin de manipulation JavaScript
                showSuccessMessage('Connexion réussie ! Redirection en cours...');
                setTimeout(() => {
                    window.location.href = '/home';
                }, 2000);
            } else {
                // Erreur de validation (400) ou autre
                clearErrors();
                if(result.data.errors) {
                    for(let [fieldName, errorMessage] of Object.entries(result.data.errors)) {
                        showError(fieldName, errorMessage);
                    }
                }
            }
        })
        .catch(error => {
            // Vraies erreurs réseau (pas de réponse du tout)
            console.error('❌ Erreur réseau:', error);
            showGeneralError('Impossible de contacter le serveur. Vérifiez votre connexion.');
        });
    });
});