import { fireEvent } from '@testing-library/dom'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import fs from 'fs'
import path from 'path'

/**
 * Tests de validation côté client — Formulaire d'inscription.
 * Couvre : validation par champ, validation globale, indicateur force mdp,
 *          formatage téléphone, feedback visuel.
 */
describe('inscription form (client validation)', () => {

    /** @type {HTMLFormElement} */
    let form;

    beforeEach(() => {
        // Charger le HTML de la page
        const htmlPath = path.resolve(process.cwd(), 'pages/inscription.html');
        const html = fs.readFileSync(htmlPath, 'utf-8');
        document.body.innerHTML = html;

        // Stub des fonctions globales chargées via des scripts externes
        globalThis.initPasswordToggles = () => {};
        globalThis.Logger = { error: vi.fn(), warn: vi.fn(), info: vi.fn() };
        globalThis.AuthService = {
            register: vi.fn(),
            addCsrfHeader: (h) => h
        };

        form = document.getElementById('inscriptionForm');
    });

    /**
     * Initialise le script inscription.js en simulant DOMContentLoaded.
     */
    async function initScript() {
        // Vider le cache du module pour réinitialiser à chaque test
        vi.resetModules();
        await import('../../js/pages/inscription.js');
        document.dispatchEvent(new Event('DOMContentLoaded'));
    }

    /**
     * Remplit un champ du formulaire par son ID.
     * @param {string} id - ID du champ
     * @param {string} value - Valeur à remplir
     */
    function fillField(id, value) {
        const input = document.getElementById(id);
        input.value = value;
        fireEvent.input(input);
    }

    /**
     * Remplit tous les champs avec des valeurs valides.
     */
    function fillAllValid() {
        fillField('firstName', 'Jean');
        fillField('lastName', 'Dupont');
        fillField('email', 'jean@email.fr');
        fillField('password', 'Password1!!');
        fillField('phone', '06 12 34 56 78');
        fillField('address', '123 Rue de la Liberté');
        fillField('city', 'Bordeaux');
        fillField('postalCode', '33000');
    }

    // ═══════════════════════════════════════════════════
    //  Validation des champs requis
    // ═══════════════════════════════════════════════════

    describe('champs requis', () => {
        it('affiche des erreurs si le formulaire est soumis vide', async () => {
            await initScript();
            fireEvent.submit(form);

            const errors = document.querySelectorAll('.error-message');
            expect(errors.length).toBeGreaterThanOrEqual(8);
        });

        it('ne soumet pas le formulaire au backend si la validation échoue', async () => {
            await initScript();
            fireEvent.submit(form);

            expect(AuthService.register).not.toHaveBeenCalled();
        });
    });

    // ═══════════════════════════════════════════════════
    //  Validation du prénom
    // ═══════════════════════════════════════════════════

    describe('prénom', () => {
        it('accepte un prénom avec lettres et accents', async () => {
            await initScript();
            fillAllValid();
            fillField('firstName', 'Jean-Éric');
            fireEvent.submit(form);

            const error = document.getElementById('firstName-error');
            expect(error).toBeNull();
        });

        it('refuse un prénom avec des chiffres', async () => {
            await initScript();
            fillAllValid();
            fillField('firstName', 'Jean123');
            fireEvent.submit(form);

            const error = document.getElementById('firstName-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toMatch(/lettres/i);
        });

        it('refuse un prénom avec des caractères spéciaux', async () => {
            await initScript();
            fillAllValid();
            fillField('firstName', 'Jean@!');
            fireEvent.submit(form);

            const error = document.getElementById('firstName-error');
            expect(error).not.toBeNull();
        });
    });

    // ═══════════════════════════════════════════════════
    //  Validation de l'email
    // ═══════════════════════════════════════════════════

    describe('email', () => {
        it('accepte une adresse email valide', async () => {
            await initScript();
            fillAllValid();
            fillField('email', 'test@example.com');
            fireEvent.submit(form);

            const error = document.getElementById('email-error');
            expect(error).toBeNull();
        });

        it('refuse une adresse email sans @', async () => {
            await initScript();
            fillAllValid();
            fillField('email', 'testemail.com');
            fireEvent.submit(form);

            const error = document.getElementById('email-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toMatch(/email valide/i);
        });

        it('refuse une adresse email sans domaine', async () => {
            await initScript();
            fillAllValid();
            fillField('email', 'test@');
            fireEvent.submit(form);

            const error = document.getElementById('email-error');
            expect(error).not.toBeNull();
        });
    });

    // ═══════════════════════════════════════════════════
    //  Validation du mot de passe
    // ═══════════════════════════════════════════════════

    describe('mot de passe', () => {
        it('accepte un mot de passe valide (10+ chars, maj, min, chiffre, spécial)', async () => {
            await initScript();
            fillAllValid();
            fillField('password', 'MonPass12!!');
            fireEvent.submit(form);

            const error = document.getElementById('password-error');
            expect(error).toBeNull();
        });

        it('refuse un mot de passe trop court (< 10 caractères)', async () => {
            await initScript();
            fillAllValid();
            fillField('password', 'Ab1!');
            fireEvent.submit(form);

            const error = document.getElementById('password-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toMatch(/10 caractères/i);
        });

        it('refuse un mot de passe sans majuscule', async () => {
            await initScript();
            fillAllValid();
            fillField('password', 'password12!!');
            fireEvent.submit(form);

            const error = document.getElementById('password-error');
            expect(error).not.toBeNull();
        });

        it('refuse un mot de passe sans chiffre', async () => {
            await initScript();
            fillAllValid();
            fillField('password', 'PasswordAbc!!');
            fireEvent.submit(form);

            const error = document.getElementById('password-error');
            expect(error).not.toBeNull();
        });

        it('refuse un mot de passe sans minuscule', async () => {
            await initScript();
            fillAllValid();
            fillField('password', 'PASSWORD12!!');
            fireEvent.submit(form);

            const error = document.getElementById('password-error');
            expect(error).not.toBeNull();
        });

        it('refuse un mot de passe sans caractère spécial', async () => {
            await initScript();
            fillAllValid();
            fillField('password', 'Password123');
            fireEvent.submit(form);

            const error = document.getElementById('password-error');
            expect(error).not.toBeNull();
        });
    });

    // ═══════════════════════════════════════════════════
    //  Validation du téléphone
    // ═══════════════════════════════════════════════════

    describe('téléphone', () => {
        it('accepte un numéro FR valide (10 chiffres avec espaces)', async () => {
            await initScript();
            fillAllValid();
            fillField('phone', '06 12 34 56 78');
            fireEvent.submit(form);

            const error = document.getElementById('phone-error');
            expect(error).toBeNull();
        });

        it('accepte un numéro international avec +', async () => {
            await initScript();
            fillAllValid();
            fillField('phone', '+33 6 12 34 56 78');
            fireEvent.submit(form);

            const error = document.getElementById('phone-error');
            expect(error).toBeNull();
        });

        it('refuse un numéro trop court (< 10 caractères)', async () => {
            await initScript();
            fillAllValid();
            fillField('phone', '0612');
            fireEvent.submit(form);

            const error = document.getElementById('phone-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toMatch(/10/);
        });
    });

    // ═══════════════════════════════════════════════════
    //  Validation de l'adresse
    // ═══════════════════════════════════════════════════

    describe('adresse', () => {
        it('accepte une adresse de 5+ caractères', async () => {
            await initScript();
            fillAllValid();
            fillField('address', '12 Avenue');
            fireEvent.submit(form);

            const error = document.getElementById('address-error');
            expect(error).toBeNull();
        });

        it('refuse une adresse trop courte (< 5 caractères)', async () => {
            await initScript();
            fillAllValid();
            fillField('address', '12');
            fireEvent.submit(form);

            const error = document.getElementById('address-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toMatch(/5 caractères/i);
        });
    });

    // ═══════════════════════════════════════════════════
    //  Validation du code postal
    // ═══════════════════════════════════════════════════

    describe('code postal', () => {
        it('accepte un code postal FR valide (5 chiffres)', async () => {
            await initScript();
            fillAllValid();
            fillField('postalCode', '33000');
            fireEvent.submit(form);

            const error = document.getElementById('postalCode-error');
            expect(error).toBeNull();
        });

        it('refuse un code postal avec des lettres', async () => {
            await initScript();
            fillAllValid();
            fillField('postalCode', '33ABC');
            fireEvent.submit(form);

            const error = document.getElementById('postalCode-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toMatch(/5 chiffres/i);
        });

        it('refuse un code postal trop court', async () => {
            await initScript();
            fillAllValid();
            fillField('postalCode', '330');
            fireEvent.submit(form);

            const error = document.getElementById('postalCode-error');
            expect(error).not.toBeNull();
        });

        it('refuse un code postal trop long', async () => {
            await initScript();
            fillAllValid();
            fillField('postalCode', '330001');
            fireEvent.submit(form);

            const error = document.getElementById('postalCode-error');
            expect(error).not.toBeNull();
        });
    });

    // ═══════════════════════════════════════════════════
    //  Indicateur de force du mot de passe
    // ═══════════════════════════════════════════════════

    describe('indicateur force mot de passe', () => {
        it('crée l\'indicateur au chargement de la page', async () => {
            await initScript();

            const indicator = document.querySelector('.password-strength');
            expect(indicator).not.toBeNull();
        });

        it('affiche "Faible" pour un mot de passe court', async () => {
            await initScript();
            fillField('password', 'ab');

            const label = document.querySelector('.password-strength__label');
            expect(label.textContent).toBe('Faible');
        });

        it('affiche "Fort" pour un bon mot de passe', async () => {
            await initScript();
            fillField('password', 'MonPassword1');

            const label = document.querySelector('.password-strength__label');
            expect(label.textContent).toBe('Fort');
        });

        it('affiche "Très fort" pour un mot de passe complexe', async () => {
            await initScript();
            fillField('password', 'MonPass1!@extra');

            const label = document.querySelector('.password-strength__label');
            expect(label.textContent).toBe('Très fort');
        });
    });

    // ═══════════════════════════════════════════════════
    //  Accessibilité
    // ═══════════════════════════════════════════════════

    describe('accessibilité', () => {
        it('ajoute aria-invalid sur les champs en erreur', async () => {
            await initScript();
            fireEvent.submit(form);

            const firstNameInput = document.getElementById('firstName');
            expect(firstNameInput.getAttribute('aria-invalid')).toBe('true');
        });

        it('ajoute aria-describedby lié au message d\'erreur', async () => {
            await initScript();
            fireEvent.submit(form);

            const firstNameInput = document.getElementById('firstName');
            expect(firstNameInput.getAttribute('aria-describedby')).toBe('firstName-error');
            expect(document.getElementById('firstName-error')).not.toBeNull();
        });

        it('les messages d\'erreur ont role=alert', async () => {
            await initScript();
            fireEvent.submit(form);

            const errors = document.querySelectorAll('.error-message');
            errors.forEach(error => {
                expect(error.getAttribute('role')).toBe('alert');
            });
        });

        it('le focus est mis sur le premier champ en erreur', async () => {
            await initScript();
            fireEvent.submit(form);

            expect(document.activeElement.id).toBe('firstName');
        });
    });

    // ═══════════════════════════════════════════════════
    //  Formulaire valide → appel backend
    // ═══════════════════════════════════════════════════

    describe('soumission valide', () => {
        it('appelle AuthService.register avec les bonnes données si tout est valide', async () => {
            AuthService.register.mockResolvedValue({
                ok: true,
                data: { success: true }
            });

            await initScript();
            // Réinitialiser le compteur après l'init pour isoler ce test
            AuthService.register.mockClear();

            fillAllValid();
            fireEvent.submit(form);

            // Attendre l'exécution de la promesse
            await vi.waitFor(() => {
                expect(AuthService.register).toHaveBeenCalled();
            });

            // Vérifier les arguments du dernier appel
            const lastCall = AuthService.register.mock.calls;
            const callArgs = lastCall[lastCall.length - 1][0];
            expect(callArgs.firstName).toBe('Jean');
            expect(callArgs.lastName).toBe('Dupont');
            expect(callArgs.email).toBe('jean@email.fr');
            expect(callArgs.postalCode).toBe('33000');
        });
    });
});
