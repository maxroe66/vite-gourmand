import { fireEvent } from '@testing-library/dom'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import fs from 'fs'
import path from 'path'

/**
 * Tests de validation côté client — Formulaire de connexion.
 * Couvre : validation email/mot de passe, anti-double-clic, feedback visuel.
 */
describe('connexion form (client validation)', () => {

    /** @type {HTMLFormElement} */
    let form;

    beforeEach(() => {
        // Charger le HTML de la page
        const htmlPath = path.resolve(process.cwd(), 'pages/connexion.html');
        const html = fs.readFileSync(htmlPath, 'utf-8');
        document.body.innerHTML = html;

        // Stub des fonctions globales chargées via des scripts externes
        globalThis.initPasswordToggles = () => {};
        globalThis.Logger = { error: vi.fn(), warn: vi.fn(), info: vi.fn() };
        globalThis.AuthService = {
            login: vi.fn(),
            addCsrfHeader: (h) => ({ ...h, 'X-CSRF-Token': 'fake-token' })
        };

        form = document.getElementById('loginForm');
    });

    /**
     * Initialise le script connexion.js en simulant DOMContentLoaded.
     */
    async function initScript() {
        vi.resetModules();
        await import('../../js/pages/connexion.js');
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

    // ═══════════════════════════════════════════════════
    //  Validation des champs requis
    // ═══════════════════════════════════════════════════

    describe('champs requis', () => {
        it('affiche une erreur si l\'email est vide', async () => {
            await initScript();
            fillField('password', 'MonPass1');
            fireEvent.submit(form);

            const error = document.getElementById('email-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toMatch(/email/i);
        });

        it('affiche une erreur si le mot de passe est vide', async () => {
            await initScript();
            fillField('email', 'test@example.com');
            fireEvent.submit(form);

            const error = document.getElementById('password-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toMatch(/mot de passe/i);
        });

        it('affiche des erreurs pour les deux champs si tout est vide', async () => {
            await initScript();
            fireEvent.submit(form);

            const emailError = document.getElementById('email-error');
            const passwordError = document.getElementById('password-error');
            expect(emailError).not.toBeNull();
            expect(passwordError).not.toBeNull();
        });

        it('ne soumet pas le formulaire au backend si la validation échoue', async () => {
            await initScript();
            fireEvent.submit(form);

            expect(AuthService.login).not.toHaveBeenCalled();
        });
    });

    // ═══════════════════════════════════════════════════
    //  Validation du format email
    // ═══════════════════════════════════════════════════

    describe('format email', () => {
        it('accepte une adresse email valide', async () => {
            await initScript();
            fillField('email', 'test@example.com');
            fillField('password', 'MonPass1');
            fireEvent.submit(form);

            const error = document.getElementById('email-error');
            expect(error).toBeNull();
        });

        it('refuse une adresse email sans @', async () => {
            await initScript();
            fillField('email', 'testexample.com');
            fillField('password', 'MonPass1');
            fireEvent.submit(form);

            const error = document.getElementById('email-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toMatch(/email valide/i);
        });

        it('refuse une adresse email sans domaine', async () => {
            await initScript();
            fillField('email', 'test@');
            fillField('password', 'MonPass1');
            fireEvent.submit(form);

            const error = document.getElementById('email-error');
            expect(error).not.toBeNull();
        });
    });

    // ═══════════════════════════════════════════════════
    //  Anti-double-clic et spinner
    // ═══════════════════════════════════════════════════

    describe('anti-double-clic', () => {
        it('désactive le bouton pendant la requête', async () => {
            // Simuler une requête lente
            AuthService.login.mockImplementation(() => new Promise(() => {}));

            await initScript();
            fillField('email', 'test@example.com');
            fillField('password', 'MonPass1');

            const submitBtn = form.querySelector('button[type="submit"]');
            expect(submitBtn.disabled).toBe(false);

            fireEvent.submit(form);

            // Le bouton doit être désactivé pendant la requête
            await vi.waitFor(() => {
                expect(submitBtn.disabled).toBe(true);
            });
        });

        it('affiche un spinner dans le bouton pendant la requête', async () => {
            AuthService.login.mockImplementation(() => new Promise(() => {}));

            await initScript();
            fillField('email', 'test@example.com');
            fillField('password', 'MonPass1');
            fireEvent.submit(form);

            await vi.waitFor(() => {
                const submitBtn = form.querySelector('button[type="submit"]');
                expect(submitBtn.querySelector('.spinner')).not.toBeNull();
                expect(submitBtn.textContent).toMatch(/Connexion en cours/i);
            });
        });

        it('réactive le bouton après la réponse', async () => {
            AuthService.login.mockResolvedValue({
                ok: false,
                data: { errors: { email: 'Identifiants incorrects' } }
            });

            await initScript();
            fillField('email', 'test@example.com');
            fillField('password', 'MonPass1');
            fireEvent.submit(form);

            const submitBtn = form.querySelector('button[type="submit"]');
            await vi.waitFor(() => {
                expect(submitBtn.disabled).toBe(false);
            });
        });
    });

    // ═══════════════════════════════════════════════════
    //  Accessibilité
    // ═══════════════════════════════════════════════════

    describe('accessibilité', () => {
        it('ajoute aria-invalid sur les champs en erreur', async () => {
            await initScript();
            fireEvent.submit(form);

            const emailInput = document.getElementById('email');
            expect(emailInput.getAttribute('aria-invalid')).toBe('true');
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

            expect(document.activeElement.id).toBe('email');
        });
    });

    // ═══════════════════════════════════════════════════
    //  Soumission valide → appel backend
    // ═══════════════════════════════════════════════════

    describe('soumission valide', () => {
        it('appelle AuthService.login avec les bons paramètres', async () => {
            AuthService.login.mockResolvedValue({
                ok: true,
                data: { success: true }
            });

            await initScript();
            // Réinitialiser le compteur après l'init pour isoler ce test
            AuthService.login.mockClear();

            fillField('email', 'test@example.com');
            fillField('password', 'MonPass1');
            fireEvent.submit(form);

            await vi.waitFor(() => {
                expect(AuthService.login).toHaveBeenCalled();
            });

            // Vérifier les arguments du dernier appel
            const lastCall = AuthService.login.mock.calls;
            expect(lastCall[lastCall.length - 1]).toEqual(['test@example.com', 'MonPass1']);
        });

        it('affiche un message de succès après connexion réussie', async () => {
            AuthService.login.mockResolvedValue({
                ok: true,
                data: { success: true }
            });

            await initScript();
            fillField('email', 'test@example.com');
            fillField('password', 'MonPass1');
            fireEvent.submit(form);

            await vi.waitFor(() => {
                const success = document.querySelector('.success-message');
                expect(success).not.toBeNull();
                expect(success.textContent).toMatch(/réussie/i);
            });
        });
    });

    // ═══════════════════════════════════════════════════
    //  Gestion des erreurs réseau
    // ═══════════════════════════════════════════════════

    describe('erreur réseau', () => {
        it('affiche un bandeau d\'erreur générale en cas d\'échec réseau', async () => {
            AuthService.login.mockRejectedValue(new Error('Network error'));

            await initScript();
            fillField('email', 'test@example.com');
            fillField('password', 'MonPass1');
            fireEvent.submit(form);

            await vi.waitFor(() => {
                const banner = document.querySelector('.general-error');
                expect(banner).not.toBeNull();
                expect(banner.textContent).toMatch(/serveur/i);
            });
        });
    });
});
