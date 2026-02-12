document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');

    // --- Activation visuelle contrôlée des erreurs ---
    // On ajoute une classe `touched` sur chaque input au 'blur' pour n'afficher
    // l'état invalide que lorsque l'utilisateur quitte le champ. En cas de
    // soumission sans interaction, on active `show-validation` sur tout le form.
    if (form) {
        form.addEventListener('blur', function(e) {
            const target = e.target;
            if (!target) return;
            const tag = target.tagName && target.tagName.toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select') {
                if (!target.classList.contains('touched')) {
                    target.classList.add('touched');
                }
            }
        }, true);

        form.addEventListener('submit', function() {
            form.classList.add('show-validation');
        }, true);
    }

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
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        try {
            const result = await AuthService.login(email, password);
            if(result.ok && result.data.success) {
                showSuccessMessage('Connexion réussie ! Redirection en cours...');
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

    // --- Modal Mot de passe oublié -------------------------------------------------
    const forgotLink = document.querySelector('.forgot-password-link');
    const modal = document.getElementById('forgotPasswordModal');
    const closeModal = document.getElementById('closeForgotModal');
    const forgotForm = document.getElementById('forgotPasswordForm');
    const forgotEmail = document.getElementById('forgotEmail');
    const forgotMsg = document.getElementById('forgotPasswordMessage');

    if (forgotLink && modal && closeModal && forgotForm && forgotEmail && forgotMsg) {
        forgotLink.addEventListener('click', function (e) {
            e.preventDefault();
            modal.setAttribute('aria-hidden', 'false');
            forgotMsg.textContent = '';
            forgotForm.reset();
            forgotEmail.focus();
        });
        closeModal.addEventListener('click', function () {
            modal.setAttribute('aria-hidden', 'true');
        });
        window.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') modal.setAttribute('aria-hidden', 'true');
        });
        window.addEventListener('click', function (e) {
            if (e.target === modal) modal.setAttribute('aria-hidden', 'true');
        });

        forgotForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            forgotMsg.textContent = '';
            const email = forgotEmail.value.trim();
            if (!email) {
                forgotMsg.textContent = 'Veuillez saisir votre email.';
                forgotMsg.classList.remove('u-text-success');
                forgotMsg.classList.add('u-text-error');
                return;
            }
            try {
                const response = await fetch('/api/auth/forgot-password', {
                    method: 'POST',
                    headers: AuthService.addCsrfHeader({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ email })
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    forgotMsg.textContent = 'Si cet email existe, un lien de réinitialisation a été envoyé.';
                    forgotMsg.classList.remove('u-text-error');
                    forgotMsg.classList.add('u-text-success');
                } else {
                    forgotMsg.textContent = data.message || 'Erreur lors de la demande.';
                    forgotMsg.classList.remove('u-text-success');
                    forgotMsg.classList.add('u-text-error');
                }
            } catch (err) {
                forgotMsg.textContent = 'Erreur réseau. Veuillez réessayer.';
                forgotMsg.classList.remove('u-text-success');
                forgotMsg.classList.add('u-text-error');
            }
        });
    }

    // Password show/hide toggles (login page)
    document.querySelectorAll('.password-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const input = btn.closest('.password-field').querySelector('input');
            if (!input) return;
            const isPwd = input.type === 'password';
            input.type = isPwd ? 'text' : 'password';
            btn.setAttribute('aria-pressed', isPwd ? 'true' : 'false');
        });
    });
});