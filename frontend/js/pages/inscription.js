document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('inscriptionForm');
    if (!form) return;

    // ── Références des champs ──
    const fields = {
        firstName:  document.getElementById('firstName'),
        lastName:   document.getElementById('lastName'),
        email:      document.getElementById('email'),
        password:   document.getElementById('password'),
        phone:      document.getElementById('phone'),
        address:    document.getElementById('address'),
        city:       document.getElementById('city'),
        postalCode: document.getElementById('postalCode')
    };

    // ── Regex miroir des règles backend (UserValidator.php) ──
    const VALIDATION_RULES = {
        firstName:  { regex: /^[a-zA-ZÀ-ÖØ-öø-ÿ\-\s]+$/u,           message: 'Le prénom ne doit contenir que des lettres, espaces ou tirets.' },
        lastName:   { regex: /^.+$/,                                   message: 'Le nom est requis.' },
        email:      { regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,            message: 'Veuillez saisir une adresse email valide.' },
        password:   { regex: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{10,}$/, message: 'Min. 10 caractères, 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.' },
        phone:      { regex: /^[0-9\s\-+]{10,}$/,                     message: 'Min. 10 caractères (chiffres, espaces, tirets, +).' },
        address:    { minLength: 5,                                    message: 'L\'adresse doit contenir au moins 5 caractères.' },
        city:       { regex: /^.+$/,                                   message: 'La ville est requise.' },
        postalCode: { regex: /^\d{5}$/,                                message: 'Le code postal doit contenir exactement 5 chiffres.' }
    };

    // ═══════════════════════════════════════════════════
    //  Fonctions utilitaires UI
    // ═══════════════════════════════════════════════════

    /**
     * Supprime tous les messages d'erreur du formulaire.
     */
    function clearErrors() {
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(field => {
            field.classList.remove('error');
            field.removeAttribute('aria-invalid');
            field.removeAttribute('aria-describedby');
        });
    }

    /**
     * Supprime l'erreur d'un champ spécifique.
     * @param {string} fieldId - ID du champ
     */
    function clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        field.classList.remove('error');
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
        const existing = document.getElementById(fieldId + '-error');
        if (existing) existing.remove();
    }

    /**
     * Affiche un message d'erreur sous un champ.
     * @param {string} fieldId - ID du champ
     * @param {string} message - Message d'erreur
     */
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

    /**
     * Affiche un bandeau d'erreur général en haut du formulaire.
     * @param {string} message
     */
    function showGeneralError(message) {
        const oldError = document.querySelector('.general-error');
        if (oldError) oldError.remove();
        const errorBanner = document.createElement('div');
        errorBanner.className = 'general-error';
        errorBanner.setAttribute('role', 'alert');
        errorBanner.textContent = message;
        form.parentNode.insertBefore(errorBanner, form);
    }

    /**
     * Affiche le bandeau de succès avec icône et texte.
     */
    function showSuccessMessage() {
        const oldMessage = document.querySelector('.success-message');
        if (oldMessage) oldMessage.remove();
        const successBanner = document.createElement('div');
        successBanner.className = 'success-message signup-success-message';
        successBanner.innerHTML = `
            <svg class="signup-success-icon" width="32" height="32" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12"/><path d="M7 13l3 3 7-7"/></svg>
            <div class="signup-success-text">
                <strong>Inscription réussie !</strong><br>
                Vous allez être redirigé(e) vers l'accueil.<br>
                <span class="signup-success-secondary">Un email de bienvenue vient de vous être envoyé.</span>
            </div>
        `;
        form.parentNode.insertBefore(successBanner, form);
    }

    // ═══════════════════════════════════════════════════
    //  Validation d'un champ individuel
    // ═══════════════════════════════════════════════════

    /**
     * Valide un champ et renvoie le message d'erreur (ou null si valide).
     * @param {string} fieldId - ID du champ
     * @returns {string|null} Message d'erreur ou null
     */
    function validateField(fieldId) {
        const field = fields[fieldId];
        if (!field) return null;

        const value = field.value.trim();
        const rule = VALIDATION_RULES[fieldId];
        if (!rule) return null;

        // Champ vide → message générique (le `required` HTML gère l'affichage natif)
        if (!value) {
            return 'Ce champ est requis.';
        }

        // Longueur minimale (address)
        if (rule.minLength && value.length < rule.minLength) {
            return rule.message;
        }

        // Regex métier
        if (rule.regex && !rule.regex.test(value)) {
            return rule.message;
        }

        return null;
    }

    /**
     * Valide un champ et met à jour l'affichage (erreur ou valide).
     * @param {string} fieldId - ID du champ
     * @returns {boolean} true si valide
     */
    function validateAndDisplayField(fieldId) {
        clearFieldError(fieldId);
        const error = validateField(fieldId);
        if (error) {
            showError(fieldId, error);
            return false;
        }
        return true;
    }

    /**
     * Valide tout le formulaire côté client.
     * @returns {boolean} true si tous les champs sont valides
     */
    function validateForm() {
        clearErrors();
        let isValid = true;
        let firstInvalidField = null;

        for (const fieldId of Object.keys(VALIDATION_RULES)) {
            const error = validateField(fieldId);
            if (error) {
                showError(fieldId, error);
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = fields[fieldId];
                }
            }
        }

        // Focus sur le premier champ en erreur pour l'accessibilité
        if (firstInvalidField) {
            firstInvalidField.focus();
        }

        return isValid;
    }

    // ═══════════════════════════════════════════════════
    //  Validation temps réel au blur
    // ═══════════════════════════════════════════════════

    form.addEventListener('blur', function(e) {
        const target = e.target;
        if (!target || !target.id) return;
        const tag = target.tagName && target.tagName.toLowerCase();
        if (tag !== 'input') return;

        // Marquer le champ comme interagi
        target.classList.add('touched');

        // Valider seulement si le champ a une règle et a été touché
        if (VALIDATION_RULES[target.id]) {
            validateAndDisplayField(target.id);
        }
    }, true);

    // Validation en temps réel pour le mot de passe (mise à jour de l'indicateur)
    if (fields.password) {
        fields.password.addEventListener('input', function() {
            updatePasswordStrength(this.value);
            // Si le champ a déjà été touché, revalider en direct
            if (this.classList.contains('touched')) {
                validateAndDisplayField('password');
            }
        });
    }

    // ═══════════════════════════════════════════════════
    //  Indicateur de force du mot de passe
    // ═══════════════════════════════════════════════════

    /**
     * Crée l'indicateur de force du mot de passe dans le DOM.
     */
    function createPasswordStrengthIndicator() {
        const passwordGroup = fields.password?.closest('.form-group');
        if (!passwordGroup || passwordGroup.querySelector('.password-strength')) return;

        const indicator = document.createElement('div');
        indicator.className = 'password-strength';
        indicator.setAttribute('aria-live', 'polite');
        indicator.innerHTML = `
            <div class="password-strength__bar">
                <div class="password-strength__fill"></div>
            </div>
            <span class="password-strength__label"></span>
        `;
        passwordGroup.appendChild(indicator);
    }

    /**
     * Évalue la force du mot de passe et met à jour l'indicateur visuel.
     * @param {string} password - Valeur du mot de passe
     */
    function updatePasswordStrength(password) {
        const fill = document.querySelector('.password-strength__fill');
        const label = document.querySelector('.password-strength__label');
        if (!fill || !label) return;

        const strength = getPasswordStrength(password);

        fill.className = 'password-strength__fill';
        fill.classList.add('password-strength__fill--' + strength.level);
        fill.style.width = strength.percent + '%';
        label.textContent = strength.text;
        label.className = 'password-strength__label password-strength__label--' + strength.level;
    }

    /**
     * Calcule le niveau de force du mot de passe.
     * @param {string} password
     * @returns {{ level: string, percent: number, text: string }}
     */
    function getPasswordStrength(password) {
        if (!password) {
            return { level: 'none', percent: 0, text: '' };
        }

        let score = 0;
        if (password.length >= 10) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/\d/.test(password)) score++;
        if (/[^a-zA-Z\d]/.test(password)) score++;
        if (password.length >= 14) score++;

        if (score <= 2) return { level: 'weak',   percent: 25,  text: 'Faible' };
        if (score <= 3) return { level: 'medium', percent: 50,  text: 'Moyen' };
        if (score <= 4) return { level: 'strong', percent: 75,  text: 'Fort' };
        return                  { level: 'very-strong', percent: 100, text: 'Très fort' };
    }

    // ═══════════════════════════════════════════════════
    //  Formatage dynamique du numéro de téléphone
    // ═══════════════════════════════════════════════════

    if (fields.phone) {
        fields.phone.addEventListener('input', function() {
            const cursorPos = this.selectionStart;
            const rawBefore = this.value;

            // Ne garder que les chiffres et le + initial
            let digits = this.value.replace(/[^\d+]/g, '');

            // Si le + n'est pas en première position, le retirer
            if (digits.indexOf('+') > 0) {
                digits = digits.replace(/\+/g, '');
            }

            // Formater par groupes de 2 (format FR : 06 12 34 56 78)
            let formatted = '';
            const hasPlus = digits.startsWith('+');
            const cleanDigits = hasPlus ? digits.slice(1) : digits;

            if (hasPlus) {
                // Format international : +33 6 12 34 56 78
                formatted = '+' + cleanDigits.replace(/(\d{2})(?=\d)/g, '$1 ').trim();
            } else {
                // Format national : 06 12 34 56 78
                formatted = cleanDigits.replace(/(\d{2})(?=\d)/g, '$1 ').trim();
            }

            this.value = formatted;

            // Ajuster la position du curseur
            const diff = formatted.length - rawBefore.length;
            const newPos = cursorPos + diff;
            this.setSelectionRange(newPos, newPos);
        });
    }

    // ═══════════════════════════════════════════════════
    //  Soumission du formulaire
    // ═══════════════════════════════════════════════════

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        // Marquer tous les champs comme touchés pour le feedback visuel
        form.classList.add('show-validation');
        Object.values(fields).forEach(f => { if (f) f.classList.add('touched'); });

        // Validation côté client avant envoi
        if (!validateForm()) {
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Création en cours…';

        const formData = {
            firstName:  fields.firstName.value.trim(),
            lastName:   fields.lastName.value.trim(),
            email:      fields.email.value.trim(),
            password:   fields.password.value,
            phone:      fields.phone.value.trim(),
            address:    fields.address.value.trim(),
            city:       fields.city.value.trim(),
            postalCode: fields.postalCode.value.trim()
        };

        try {
            const result = await AuthService.register(formData);
            if (result.ok && result.data.success) {
                showSuccessMessage();
                setTimeout(() => {
                    window.location.href = '/home';
                }, 2500);
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
            Logger.error('Erreur réseau inscription:', error);
            showGeneralError('Impossible de contacter le serveur. Vérifiez votre connexion.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });

    // ═══════════════════════════════════════════════════
    //  Initialisation
    // ═══════════════════════════════════════════════════

    createPasswordStrengthIndicator();
    initPasswordToggles();
});