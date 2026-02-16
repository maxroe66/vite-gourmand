/**
 * Script de page — Contact
 * Gère la soumission du formulaire de contact
 * et la validation côté client.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('contactForm');
    if (!form) return;

    // ── Activation visuelle contrôlée des erreurs ──
    // Classe `touched` ajoutée au blur pour montrer l'état invalide
    // uniquement après interaction de l'utilisateur.
    form.addEventListener('blur', (e) => {
        const target = e.target;
        if (!target) return;
        const tag = target.tagName && target.tagName.toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') {
            if (!target.classList.contains('touched')) {
                target.classList.add('touched');
            }
        }
    }, true);

    form.addEventListener('submit', () => {
        form.classList.add('show-validation');
    }, true);

    /**
     * Supprime tous les messages d'erreur affichés.
     */
    function clearErrors() {
        const errorElements = document.querySelectorAll('.error-message');
        errorElements.forEach((el) => el.remove());

        const errorFields = document.querySelectorAll('.error');
        errorFields.forEach((field) => {
            field.classList.remove('error');
            field.removeAttribute('aria-invalid');
        });

        const generalError = document.querySelector('.general-error');
        if (generalError) generalError.remove();
    }

    /**
     * Affiche une erreur sous un champ spécifique.
     * @param {string} fieldId — ID du champ en erreur
     * @param {string} message — Message d'erreur
     */
    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return;
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

    /**
     * Affiche un message d'erreur général au-dessus du formulaire.
     * @param {string} message
     */
    function showGeneralError(message) {
        const oldError = document.querySelector('.general-error');
        if (oldError) oldError.remove();

        const errorBanner = document.createElement('div');
        errorBanner.className = 'general-error';
        errorBanner.textContent = message;
        form.parentNode.insertBefore(errorBanner, form);
    }

    /**
     * Validation côté client des champs du formulaire.
     * @returns {Object} { isValid: boolean, errors: Object }
     */
    function validateForm() {
        const errors = {};

        const titre = document.getElementById('contactTitle').value.trim();
        const email = document.getElementById('contactEmail').value.trim();
        const description = document.getElementById('contactDescription').value.trim();

        if (!titre) {
            errors.contactTitle = 'Le titre est requis.';
        } else if (titre.length > 150) {
            errors.contactTitle = 'Le titre ne doit pas dépasser 150 caractères.';
        }

        if (!email) {
            errors.contactEmail = "L'adresse email est requise.";
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.contactEmail = "Le format de l'adresse email est invalide.";
        }

        if (!description) {
            errors.contactDescription = 'Le message est requis.';
        } else if (description.length < 10) {
            errors.contactDescription = 'Le message doit contenir au moins 10 caractères.';
        }

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    }

    /**
     * Gestion de la soumission du formulaire.
     */
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();

        // Validation côté client
        const validation = validateForm();
        if (!validation.isValid) {
            for (const [fieldId, message] of Object.entries(validation.errors)) {
                showError(fieldId, message);
            }
            return;
        }

        const titre = document.getElementById('contactTitle').value.trim();
        const email = document.getElementById('contactEmail').value.trim();
        const description = document.getElementById('contactDescription').value.trim();

        const submitBtn = form.querySelector('.contact-form__submit');
        const submitText = form.querySelector('.contact-form__submit-text');
        const originalText = submitText.textContent;

        // État de chargement
        submitBtn.disabled = true;
        submitText.textContent = 'Envoi en cours…';

        try {
            const response = await fetch('/api/contact', AuthService.getFetchOptions({
                method: 'POST',
                body: JSON.stringify({ titre, email, description })
            }));

            const data = await response.json();

            if (response.ok && data.success) {
                showToast('Votre message a bien été envoyé ! Nous vous répondrons dans les plus brefs délais.', 'success');
                form.reset();
                // Retirer les classes de validation
                form.classList.remove('show-validation');
                form.querySelectorAll('.touched').forEach((el) => el.classList.remove('touched'));
            } else {
                // Erreurs de validation serveur
                if (data.errors) {
                    // Mapping des noms backend → IDs frontend
                    const fieldMapping = {
                        titre: 'contactTitle',
                        email: 'contactEmail',
                        description: 'contactDescription'
                    };
                    for (const [fieldName, errorMessage] of Object.entries(data.errors)) {
                        const fieldId = fieldMapping[fieldName] || fieldName;
                        showError(fieldId, errorMessage);
                    }
                } else {
                    showGeneralError(data.message || 'Une erreur est survenue. Veuillez réessayer.');
                }
            }
        } catch (error) {
            Logger.error('Erreur réseau (contact):', error);
            showGeneralError('Impossible de contacter le serveur. Vérifiez votre connexion.');
        } finally {
            submitBtn.disabled = false;
            submitText.textContent = originalText;
        }
    });
});
