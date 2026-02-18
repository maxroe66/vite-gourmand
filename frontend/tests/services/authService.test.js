import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';
import { mockFetch, mockFetchError } from '../helpers/mock-fetch.js';

/**
 * Tests unitaires — authService.js
 * Couvre : getCookieValue, getCsrfToken, addCsrfHeader, getFetchOptions,
 *          login, register, logout, resetPassword, isAuthenticated, getUser
 */
describe('authService.js', () => {

    beforeEach(() => {
        // Logger doit exister avant de charger authService
        globalThis.Logger = { error: vi.fn(), warn: vi.fn(), log: vi.fn() };
        loadScript('js/services/authService.js');
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.cookie = 'csrfToken=; Max-Age=0';
    });

    // ─── getCookieValue (fonction interne, testée via getCsrfToken) ──

    describe('getCsrfToken()', () => {
        it('retourne le token CSRF depuis le cookie', () => {
            document.cookie = 'csrfToken=abc123';
            expect(AuthService.getCsrfToken()).toBe('abc123');
        });

        it('retourne null si le cookie n\'existe pas', () => {
            document.cookie = 'autrecookie=xyz';
            expect(AuthService.getCsrfToken()).toBeNull();
        });

        it('décode les caractères URL dans le cookie', () => {
            document.cookie = 'csrfToken=abc%3D%3D123';
            expect(AuthService.getCsrfToken()).toBe('abc==123');
        });
    });

    // ─── addCsrfHeader ──────────────────────────────────────────────

    describe('addCsrfHeader()', () => {
        it('ajoute le header X-CSRF-Token quand le cookie existe', () => {
            document.cookie = 'csrfToken=tok42';
            const headers = AuthService.addCsrfHeader({ 'Content-Type': 'application/json' });
            expect(headers['X-CSRF-Token']).toBe('tok42');
            expect(headers['Content-Type']).toBe('application/json');
        });

        it('retourne les headers inchangés sans cookie CSRF', () => {
            document.cookie = 'csrfToken=; Max-Age=0';
            const headers = AuthService.addCsrfHeader({ 'Content-Type': 'application/json' });
            expect(headers['X-CSRF-Token']).toBeUndefined();
        });

        it('fonctionne sans argument (headers vides)', () => {
            document.cookie = 'csrfToken=tok42';
            const headers = AuthService.addCsrfHeader();
            expect(headers['X-CSRF-Token']).toBe('tok42');
        });
    });

    // ─── getFetchOptions ─────────────────────────────────────────────

    describe('getFetchOptions()', () => {
        it('ajoute credentials include et Content-Type', () => {
            document.cookie = 'csrfToken=tok42';
            const opts = AuthService.getFetchOptions({ method: 'POST' });
            expect(opts.credentials).toBe('include');
            expect(opts.headers['Content-Type']).toBe('application/json');
            expect(opts.headers['X-CSRF-Token']).toBe('tok42');
            expect(opts.method).toBe('POST');
        });

        it('merge les options fournies', () => {
            const opts = AuthService.getFetchOptions({
                method: 'PUT',
                body: '{"a":1}'
            });
            expect(opts.method).toBe('PUT');
            expect(opts.body).toBe('{"a":1}');
        });
    });

    // ─── login ───────────────────────────────────────────────────────

    describe('login()', () => {
        it('envoie un POST avec email et mot de passe', async () => {
            const mock = mockFetch(200, { ok: true, user: { id: 1 } });
            const result = await AuthService.login('test@mail.com', 'Pass1234');

            expect(mock).toHaveBeenCalledOnce();
            const [url, opts] = mock.mock.calls[0];
            expect(url).toBe('/api/auth/login');
            expect(opts.method).toBe('POST');
            expect(JSON.parse(opts.body)).toEqual({ email: 'test@mail.com', password: 'Pass1234' });
        });

        it('retourne { ok, status, data } en succès', async () => {
            mockFetch(200, { user: { id: 1, email: 'a@b.c' } });
            const result = await AuthService.login('a@b.c', 'pass');

            expect(result.ok).toBe(true);
            expect(result.status).toBe(200);
            expect(result.data.user.id).toBe(1);
        });

        it('retourne ok=false pour un 401', async () => {
            mockFetch(401, { error: 'Identifiants invalides' });
            const result = await AuthService.login('bad@mail.com', 'wrong');

            expect(result.ok).toBe(false);
            expect(result.status).toBe(401);
            expect(result.data.error).toBe('Identifiants invalides');
        });

        it('propage l\'erreur réseau', async () => {
            mockFetchError('Network error');
            await expect(AuthService.login('a@b.c', 'p')).rejects.toThrow('Network error');
        });
    });

    // ─── register ────────────────────────────────────────────────────

    describe('register()', () => {
        it('envoie les données utilisateur en POST', async () => {
            const userData = { firstName: 'Jean', email: 'j@m.com', password: 'Pass1234' };
            const mock = mockFetch(201, { message: 'Compte créé' });

            await AuthService.register(userData);

            const [url, opts] = mock.mock.calls[0];
            expect(url).toBe('/api/auth/register');
            expect(JSON.parse(opts.body)).toEqual(userData);
        });

        it('retourne ok=false pour une erreur validation 422', async () => {
            mockFetch(422, { errors: { email: 'Email déjà utilisé' } });
            const result = await AuthService.register({ email: 'dup@m.com' });

            expect(result.ok).toBe(false);
            expect(result.status).toBe(422);
        });
    });

    // ─── logout ──────────────────────────────────────────────────────

    describe('logout()', () => {
        it('envoie un POST vers /api/auth/logout', async () => {
            const mock = mockFetch(200, { message: 'Déconnecté' });
            const result = await AuthService.logout();

            expect(result).toBe(true);
            const [url, opts] = mock.mock.calls[0];
            expect(url).toBe('/api/auth/logout');
            expect(opts.method).toBe('POST');
        });

        it('throw si le serveur répond une erreur', async () => {
            mockFetch(500, { error: 'Server error' });
            await expect(AuthService.logout()).rejects.toThrow();
        });

        it('log l\'erreur réseau et la propage', async () => {
            mockFetchError('Offline');
            await expect(AuthService.logout()).rejects.toThrow('Offline');
            expect(Logger.error).toHaveBeenCalled();
        });
    });

    // ─── resetPassword ───────────────────────────────────────────────

    describe('resetPassword()', () => {
        it('envoie le token et le nouveau mot de passe', async () => {
            const mock = mockFetch(200, { message: 'Mot de passe réinitialisé' });
            const result = await AuthService.resetPassword('tok123', 'NewPass1234');

            expect(result.ok).toBe(true);
            const body = JSON.parse(mock.mock.calls[0][1].body);
            expect(body.token).toBe('tok123');
            expect(body.password).toBe('NewPass1234');
        });
    });

    // ─── isAuthenticated ─────────────────────────────────────────────

    describe('isAuthenticated()', () => {
        it('retourne les données auth si connecté', async () => {
            mockFetch(200, { isAuthenticated: true, user: { id: 1 } });
            const result = await AuthService.isAuthenticated();

            expect(result.isAuthenticated).toBe(true);
            expect(result.user.id).toBe(1);
        });

        it('retourne null si non connecté (401)', async () => {
            mockFetch(401, {});
            const result = await AuthService.isAuthenticated();
            expect(result).toBeNull();
        });

        it('retourne null en cas d\'erreur réseau', async () => {
            mockFetchError('Offline');
            const result = await AuthService.isAuthenticated();
            expect(result).toBeNull();
        });

        it('envoie credentials include', async () => {
            const mock = mockFetch(200, { isAuthenticated: true });
            await AuthService.isAuthenticated();

            expect(mock.mock.calls[0][1].credentials).toBe('include');
        });
    });

    // ─── getUser ─────────────────────────────────────────────────────

    describe('getUser()', () => {
        it('retourne l\'utilisateur si authentifié', async () => {
            mockFetch(200, { isAuthenticated: true, user: { id: 5, email: 'u@m.com' } });
            const user = await AuthService.getUser();
            expect(user.id).toBe(5);
        });

        it('retourne null si non authentifié', async () => {
            mockFetch(200, { isAuthenticated: false });
            const user = await AuthService.getUser();
            expect(user).toBeNull();
        });

        it('retourne null en cas d\'erreur', async () => {
            mockFetchError('Offline');
            const user = await AuthService.getUser();
            expect(user).toBeNull();
        });
    });

    // ─── updateProfile ───────────────────────────────────────────────

    describe('updateProfile()', () => {
        it('envoie un PUT avec les données du profil', async () => {
            const profileData = { firstName: 'Marie', lastName: 'Curie', city: 'Paris' };
            mockFetch(200, { success: true, user: { id: 1, prenom: 'Marie' } });

            const result = await AuthService.updateProfile(profileData);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/auth/profile');
            expect(opts.method).toBe('PUT');
            expect(opts.credentials).toBe('include');
            expect(JSON.parse(opts.body)).toEqual(profileData);
        });

        it('retourne ok=true et les données utilisateur en succès', async () => {
            mockFetch(200, { success: true, user: { id: 5, prenom: 'Jean' } });
            const result = await AuthService.updateProfile({ firstName: 'Jean' });

            expect(result.ok).toBe(true);
            expect(result.status).toBe(200);
            expect(result.data.success).toBe(true);
            expect(result.data.user.prenom).toBe('Jean');
        });

        it('retourne ok=false pour une erreur de validation 422', async () => {
            mockFetch(422, { success: false, errors: { postalCode: 'Format invalide' } });
            const result = await AuthService.updateProfile({ postalCode: 'abc' });

            expect(result.ok).toBe(false);
            expect(result.status).toBe(422);
            expect(result.data.errors.postalCode).toBe('Format invalide');
        });

        it('retourne ok=false pour un 401 non authentifié', async () => {
            mockFetch(401, { success: false, message: 'Non autorisé.' });
            const result = await AuthService.updateProfile({ firstName: 'Test' });

            expect(result.ok).toBe(false);
            expect(result.status).toBe(401);
        });

        it('propage l\'erreur réseau', async () => {
            mockFetchError('Network error');
            await expect(AuthService.updateProfile({ firstName: 'Test' }))
                .rejects.toThrow('Network error');
        });
    });
});
