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
        if (!field) return;
        field.classList.add('error');
        field.setAttribute('aria-invalid', 'true');
        const errorId = fieldId + '-error';
        field.setAttribute('aria-describedby', errorId);

        // Éviter les doublons
        const existing = document.getElementById(errorId);
        if (existing) existing.remove();

        const errorDiv = document.createElement('div');
        errorDiv.id = errorId;
        errorDiv.className = 'error-message';
        errorDiv.setAttribute('role', 'alert');
        errorDiv.textContent = message;
        field.closest('.form-group').appendChild(errorDiv);
    }

    function showGeneralError(message) {
        const oldError = document.querySelector('.general-error');
        if (oldError) oldError.remove();
        const errorBanner = document.createElement('div');
        errorBanner.className = 'general-error';
        errorBanner.setAttribute('role', 'alert');
        errorBanner.textContent = message;
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

        // Validation côté client avant envoi
        clearErrors();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        let isValid = true;
        let firstInvalidField = null;

        if (!email) {
            showError('email', 'Veuillez saisir votre adresse email.');
            isValid = false;
            firstInvalidField = document.getElementById('email');
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('email', 'Veuillez saisir une adresse email valide.');
            isValid = false;
            firstInvalidField = document.getElementById('email');
        }

        if (!password) {
            showError('password', 'Veuillez saisir votre mot de passe.');
            isValid = false;
            if (!firstInvalidField) firstInvalidField = document.getElementById('password');
        }

        if (!isValid) {
            if (firstInvalidField) firstInvalidField.focus();
            return;
        }

        // Anti-double-clic + spinner
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Connexion en cours…';

        try {
            const result = await AuthService.login(email, password);
            if (result.ok && result.data.success) {
                showSuccessMessage('Connexion réussie ! Redirection en cours...');
                setTimeout(() => {
                    window.location.href = '/home';
                }, 2000);
            } else {
                clearErrors();
                if (result.data.errors) {
                    let firstField = null;
                    for (const [fieldName, errorMessage] of Object.entries(result.data.errors)) {
                        showError(fieldName, errorMessage);
                        if (!firstField) firstField = document.getElementById(fieldName);
                    }
                    if (firstField) firstField.focus();
                }
            }
        } catch (error) {
            Logger.error('Erreur réseau connexion:', error);
            showGeneralError('Impossible de contacter le serveur. Vérifiez votre connexion.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
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
            modal.classList.add('is-visible');
            modal.setAttribute('aria-hidden', 'false');
            forgotMsg.textContent = '';
            forgotForm.reset();
            forgotEmail.focus();
        });
        closeModal.addEventListener('click', function () {
            modal.classList.remove('is-visible');
            modal.setAttribute('aria-hidden', 'true');
        });
        window.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
            }
        });
        window.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
            }
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

    // Password show/hide toggles
    initPasswordToggles();
});