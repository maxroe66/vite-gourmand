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
        });
    }

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        field.classList.add('error');
        const errordiv = document.createElement('div');
        errordiv.className = 'error-message';
        errordiv.textContent = message;
        field.parentNode.appendChild(errordiv);
    }

    function showGeneralError(message) {
        console.log('🚨 showGeneralError appelée avec:', message);
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
            firstName: document.getElementById('firstName').value,
            lastName: document.getElementById('lastName').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            city: document.getElementById('city').value,
            postalCode: document.getElementById('postalCode').value
        };
        console.log('Données du formulaire:', formData);
        fetch('/api/auth/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            console.log('📡 Status HTTP:', response.status);
            
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
            console.log('Réponse du serveur:', result);
            
            if(result.ok && result.data.success) {
                // Succès (201)
                // TODO: Créer la page connexion.html et rediriger vers /connexion
                showSuccessMessage('Inscription réussie ! Redirection en cours...');
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