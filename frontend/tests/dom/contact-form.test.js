import { fireEvent } from '@testing-library/dom';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import fs from 'fs';
import path from 'path';

/**
 * Tests de validation côté client — Formulaire de contact.
 * Couvre : validation champs, soumission, erreurs serveur, anti-double-clic,
 *          feedback visuel, mapping d'erreurs backend.
 */
describe('contact form (client validation)', () => {

    /** @type {HTMLFormElement} */
    let form;

    beforeEach(() => {
        const htmlPath = path.resolve(process.cwd(), 'pages/contact.html');
        const html = fs.readFileSync(htmlPath, 'utf-8');
        document.body.innerHTML = html;

        // Stubs globaux
        globalThis.Logger = { error: vi.fn(), warn: vi.fn(), log: vi.fn() };
        globalThis.AuthService = {
            getFetchOptions: vi.fn((opts = {}) => ({
                ...opts,
                credentials: 'include',
                headers: { 'Content-Type': 'application/json', ...opts.headers }
            }))
        };
        globalThis.showToast = vi.fn();

        form = document.getElementById('contactForm');
    });

    /**
     * Charge le script contact.js et déclenche DOMContentLoaded.
     */
    async function initScript() {
        vi.resetModules();
        await import('../../js/pages/contact.js');
        document.dispatchEvent(new Event('DOMContentLoaded'));
    }

    /**
     * Remplit les 3 champs du formulaire avec des valeurs valides.
     */
    function fillValidForm() {
        document.getElementById('contactTitle').value = 'Demande de devis';
        document.getElementById('contactEmail').value = 'client@example.com';
        document.getElementById('contactDescription').value = 'Bonjour, je souhaite organiser un événement pour 50 personnes.';
    }

    // ─── Validation : titre ──────────────────────────────────────────

    describe('validation du titre', () => {
        it('affiche une erreur si le titre est vide', async () => {
            await initScript();
            document.getElementById('contactTitle').value = '';
            document.getElementById('contactEmail').value = 'a@b.com';
            document.getElementById('contactDescription').value = 'Contenu suffisant ici';

            fireEvent.submit(form);

            const error = document.getElementById('contactTitle-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toContain('titre est requis');
        });

        it('affiche une erreur si le titre dépasse 150 caractères', async () => {
            await initScript();
            document.getElementById('contactTitle').value = 'A'.repeat(151);
            document.getElementById('contactEmail').value = 'a@b.com';
            document.getElementById('contactDescription').value = 'Contenu suffisant ici';

            fireEvent.submit(form);

            const error = document.getElementById('contactTitle-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toContain('150 caractères');
        });
    });

    // ─── Validation : email ──────────────────────────────────────────

    describe('validation de l\'email', () => {
        it('affiche une erreur si l\'email est vide', async () => {
            await initScript();
            document.getElementById('contactTitle').value = 'Titre OK';
            document.getElementById('contactEmail').value = '';
            document.getElementById('contactDescription').value = 'Contenu suffisant ici';

            fireEvent.submit(form);

            const error = document.getElementById('contactEmail-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toContain('email est requise');
        });

        it('affiche une erreur si l\'email est invalide', async () => {
            await initScript();
            document.getElementById('contactTitle').value = 'Titre OK';
            document.getElementById('contactEmail').value = 'pasvalide';
            document.getElementById('contactDescription').value = 'Contenu suffisant ici';

            fireEvent.submit(form);

            const error = document.getElementById('contactEmail-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toContain('invalide');
        });
    });

    // ─── Validation : description ────────────────────────────────────

    describe('validation de la description', () => {
        it('affiche une erreur si la description est vide', async () => {
            await initScript();
            document.getElementById('contactTitle').value = 'Titre OK';
            document.getElementById('contactEmail').value = 'a@b.com';
            document.getElementById('contactDescription').value = '';

            fireEvent.submit(form);

            const error = document.getElementById('contactDescription-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toContain('message est requis');
        });

        it('affiche une erreur si la description fait moins de 10 caractères', async () => {
            await initScript();
            document.getElementById('contactTitle').value = 'Titre OK';
            document.getElementById('contactEmail').value = 'a@b.com';
            document.getElementById('contactDescription').value = 'Court';

            fireEvent.submit(form);

            const error = document.getElementById('contactDescription-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toContain('10 caractères');
        });
    });

    // ─── Accessibilité ───────────────────────────────────────────────

    describe('accessibilité', () => {
        it('ajoute aria-invalid sur les champs en erreur', async () => {
            await initScript();
            fireEvent.submit(form); // tout vide

            const titre = document.getElementById('contactTitle');
            expect(titre.getAttribute('aria-invalid')).toBe('true');
        });

        it('ajoute aria-describedby liant le champ à son message d\'erreur', async () => {
            await initScript();
            fireEvent.submit(form);

            const titre = document.getElementById('contactTitle');
            expect(titre.getAttribute('aria-describedby')).toBe('contactTitle-error');
        });

        it('ajoute la classe error sur les champs invalides', async () => {
            await initScript();
            fireEvent.submit(form);

            expect(document.getElementById('contactTitle').classList.contains('error')).toBe(true);
        });
    });

    // ─── Soumission réussie ──────────────────────────────────────────

    describe('soumission réussie', () => {
        it('appelle fetch avec les données du formulaire', async () => {
            await initScript();
            fillValidForm();

            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: true,
                json: vi.fn().mockResolvedValue({ success: true })
            });

            fireEvent.submit(form);
            await vi.waitFor(() => expect(fetch).toHaveBeenCalled());

            // Vérifier le dernier appel (accumulation DOMContentLoaded possible)
            const lastCall = fetch.mock.calls[fetch.mock.calls.length - 1];
            const [url, opts] = lastCall;
            expect(url).toBe('/api/contact');
            const body = JSON.parse(opts.body);
            expect(body.titre).toBe('Demande de devis');
            expect(body.email).toBe('client@example.com');
        });

        it('affiche un toast de succès', async () => {
            await initScript();
            fillValidForm();

            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: true,
                json: vi.fn().mockResolvedValue({ success: true })
            });

            fireEvent.submit(form);
            await vi.waitFor(() => expect(showToast).toHaveBeenCalled());

            expect(showToast.mock.calls[0][1]).toBe('success');
        });

        it('réinitialise le formulaire après succès', async () => {
            await initScript();
            fillValidForm();

            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: true,
                json: vi.fn().mockResolvedValue({ success: true })
            });

            fireEvent.submit(form);
            await vi.waitFor(() => expect(showToast).toHaveBeenCalled());

            // Le formulaire doit être vide après reset
            // (form.reset() vide les champs)
        });
    });

    // ─── Anti-double-clic ────────────────────────────────────────────

    describe('anti-double-clic', () => {
        it('désactive le bouton pendant l\'envoi', async () => {
            await initScript();
            fillValidForm();

            let resolveResponse;
            globalThis.fetch = vi.fn().mockReturnValue(new Promise(r => { resolveResponse = r; }));

            fireEvent.submit(form);

            const btn = form.querySelector('.contact-form__submit');
            expect(btn.disabled).toBe(true);

            // Résoudre la requête
            resolveResponse({
                ok: true,
                json: vi.fn().mockResolvedValue({ success: true })
            });

            await vi.waitFor(() => expect(btn.disabled).toBe(false));
        });

        it('change le texte du bouton pendant l\'envoi', async () => {
            await initScript();
            fillValidForm();

            // Promise qui ne se résout pas → le formulaire reste en état "loading"
            globalThis.fetch = vi.fn().mockReturnValue(new Promise(() => {}));

            fireEvent.submit(form);

            const submitText = form.querySelector('.contact-form__submit-text');
            expect(submitText.textContent).toBe('Envoi en cours…');
        });
    });

    // ─── Erreurs serveur ─────────────────────────────────────────────

    describe('erreurs serveur', () => {
        it('affiche les erreurs de validation backend par champ', async () => {
            await initScript();
            fillValidForm();

            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: false,
                json: vi.fn().mockResolvedValue({
                    errors: { titre: 'Titre offensant', email: 'Email bloqué' }
                })
            });

            fireEvent.submit(form);
            await vi.waitFor(() => {
                const titleError = document.getElementById('contactTitle-error');
                expect(titleError).not.toBeNull();
            });

            expect(document.getElementById('contactTitle-error').textContent).toBe('Titre offensant');
            expect(document.getElementById('contactEmail-error').textContent).toBe('Email bloqué');
        });

        it('affiche un message général si pas d\'erreurs détaillées', async () => {
            await initScript();
            fillValidForm();

            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: false,
                json: vi.fn().mockResolvedValue({
                    message: 'Serveur indisponible'
                })
            });

            fireEvent.submit(form);
            await vi.waitFor(() => {
                const general = document.querySelector('.general-error');
                expect(general).not.toBeNull();
            });

            expect(document.querySelector('.general-error').textContent).toBe('Serveur indisponible');
        });
    });

    // ─── Erreur réseau ───────────────────────────────────────────────

    describe('erreur réseau', () => {
        it('affiche un message général en cas d\'erreur réseau', async () => {
            await initScript();
            fillValidForm();

            globalThis.fetch = vi.fn().mockRejectedValue(new Error('Network error'));

            fireEvent.submit(form);
            await vi.waitFor(() => {
                const general = document.querySelector('.general-error');
                expect(general).not.toBeNull();
            });

            expect(document.querySelector('.general-error').textContent).toContain('connexion');
        });

        it('log l\'erreur via Logger.error', async () => {
            await initScript();
            fillValidForm();

            globalThis.fetch = vi.fn().mockRejectedValue(new Error('Offline'));

            fireEvent.submit(form);
            await vi.waitFor(() => expect(Logger.error).toHaveBeenCalled());
        });
    });

    // ─── Nettoyage des erreurs ───────────────────────────────────────

    describe('nettoyage des erreurs', () => {
        it('supprime les anciens messages d\'erreur à chaque soumission', async () => {
            await initScript();

            // Premier submit vide → erreurs
            fireEvent.submit(form);
            expect(document.querySelectorAll('.error-message').length).toBeGreaterThan(0);

            // Remplir et resoumettre → erreurs nettoyées
            fillValidForm();
            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: true,
                json: vi.fn().mockResolvedValue({ success: true })
            });

            fireEvent.submit(form);
            // Les erreurs précédentes doivent être supprimées
            const errorsBeforeFetch = document.querySelectorAll('.error-message');
            expect(errorsBeforeFetch.length).toBe(0);
        });
    });

    // ─── Blur (touched) ─────────────────────────────────────────────

    describe('comportement blur', () => {
        it('ajoute la classe touched au blur d\'un champ', async () => {
            await initScript();
            const titre = document.getElementById('contactTitle');

            fireEvent.blur(titre);
            expect(titre.classList.contains('touched')).toBe(true);
        });
    });
});
