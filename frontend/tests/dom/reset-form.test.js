import { fireEvent } from '@testing-library/dom'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import fs from 'fs'
import path from 'path'

/**
 * Tests de validation côté client — Formulaire de réinitialisation mot de passe.
 * Couvre : validation mots de passe, token manquant, soumission réussie,
 *          erreurs serveur, erreurs réseau.
 */
describe('reset password form (client validation)', () => {

    beforeEach(() => {
        const htmlPath = path.resolve(process.cwd(), 'pages/motdepasse-oublie.html');
        const html = fs.readFileSync(htmlPath, 'utf-8');
        document.body.innerHTML = html;

        // Stubs globaux
        globalThis.initPasswordToggles = vi.fn();
        globalThis.AuthService = {
            resetPassword: vi.fn()
        };
    });

    /**
     * Configure l'URL avec un token et initialise le script.
     * @param {string|null} token
     */
    async function initWithToken(token) {
        const url = token
            ? `http://localhost/reset-password?token=${token}`
            : 'http://localhost/reset-password';
        Object.defineProperty(window, 'location', {
            value: new URL(url),
            writable: true,
            configurable: true
        });
        vi.resetModules();
        await import('../../js/pages/motdepasse-oublie.js');
        document.dispatchEvent(new Event('DOMContentLoaded'));
    }

    // ─── Token manquant ──────────────────────────────────────────────

    it('affiche une erreur et désactive le bouton si pas de token', async () => {
        await initWithToken(null);

        const msg = document.querySelector('.general-error');
        expect(msg).not.toBeNull();
        expect(msg.textContent).toMatch(/invalide|expiré/i);

        const btn = document.querySelector('button[type="submit"]');
        expect(btn.disabled).toBe(true);
    });

    // ─── Validation : mot de passe trop court ────────────────────────

    it('affiche une erreur si le mot de passe fait moins de 8 caractères', async () => {
        await initWithToken('abc');
        const form = document.getElementById('forgotPasswordForm');

        document.getElementById('newPassword').value = 'Short1';
        document.getElementById('confirmPassword').value = 'Short1';
        fireEvent.submit(form);

        const msg = document.querySelector('.general-error');
        expect(msg).not.toBeNull();
        expect(msg.textContent).toMatch(/8 caractères/);
    });

    // ─── Validation : mots de passe différents ───────────────────────

    it('affiche une erreur si les mots de passe ne correspondent pas', async () => {
        await initWithToken('abc');
        const form = document.getElementById('forgotPasswordForm');

        document.getElementById('newPassword').value = 'password123';
        document.getElementById('confirmPassword').value = 'different123';
        fireEvent.submit(form);

        const banner = document.querySelector('.general-error');
        expect(banner).not.toBeNull();
        expect(banner.textContent).toMatch(/ne correspondent pas/i);
    });

    // ─── Soumission réussie ──────────────────────────────────────────

    it('appelle AuthService.resetPassword avec le token et le mot de passe', async () => {
        await initWithToken('tok456');
        const form = document.getElementById('forgotPasswordForm');

        AuthService.resetPassword.mockResolvedValue({
            ok: true, data: { success: true }
        });

        document.getElementById('newPassword').value = 'NewPass1234';
        document.getElementById('confirmPassword').value = 'NewPass1234';
        fireEvent.submit(form);

        await vi.waitFor(() => {
            expect(AuthService.resetPassword).toHaveBeenCalledWith('tok456', 'NewPass1234');
        });
    });

    it('affiche un message de succès quand la réinitialisation réussit', async () => {
        await initWithToken('tok456');
        const form = document.getElementById('forgotPasswordForm');

        AuthService.resetPassword.mockResolvedValue({
            ok: true, data: { success: true }
        });

        document.getElementById('newPassword').value = 'ValidPass1234';
        document.getElementById('confirmPassword').value = 'ValidPass1234';
        fireEvent.submit(form);

        await vi.waitFor(() => {
            const msg = document.querySelector('.success-message');
            expect(msg).not.toBeNull();
            expect(msg.textContent).toMatch(/succès/i);
        });
    });

    // ─── Erreur serveur ──────────────────────────────────────────────

    it('affiche le message d\'erreur du serveur', async () => {
        await initWithToken('tok456');
        const form = document.getElementById('forgotPasswordForm');

        AuthService.resetPassword.mockResolvedValue({
            ok: false, data: { message: 'Token expiré' }
        });

        document.getElementById('newPassword').value = 'ValidPass1234';
        document.getElementById('confirmPassword').value = 'ValidPass1234';
        fireEvent.submit(form);

        await vi.waitFor(() => {
            const msg = document.querySelector('.general-error');
            expect(msg).not.toBeNull();
            expect(msg.textContent).toBe('Token expiré');
        });
    });

    // ─── Erreur réseau ───────────────────────────────────────────────

    it('affiche un message d\'erreur réseau', async () => {
        await initWithToken('tok456');
        const form = document.getElementById('forgotPasswordForm');

        AuthService.resetPassword.mockRejectedValue(new Error('Offline'));

        document.getElementById('newPassword').value = 'ValidPass1234';
        document.getElementById('confirmPassword').value = 'ValidPass1234';
        fireEvent.submit(form);

        await vi.waitFor(() => {
            const msg = document.querySelector('.general-error');
            expect(msg).not.toBeNull();
            expect(msg.textContent).toMatch(/réseau/i);
        });
    });

    // ─── Initialisation ──────────────────────────────────────────────

    it('appelle initPasswordToggles au chargement', async () => {
        await initWithToken('abc');
        expect(initPasswordToggles).toHaveBeenCalled();
    });
})
