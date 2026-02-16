import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';

/**
 * Tests unitaires — adminGuard.js
 * Couvre : checkAccess (authentification, rôles, redirections)
 */
describe('adminGuard.js', () => {

    let mockAuthService;

    beforeEach(() => {
        globalThis.Logger = { error: vi.fn(), warn: vi.fn(), log: vi.fn() };
        mockAuthService = {
            isAuthenticated: vi.fn()
        };
        globalThis.AuthService = mockAuthService;
        loadScript('js/guards/adminGuard.js');
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    // ─── Accès autorisé ──────────────────────────────────────────────

    describe('accès autorisé', () => {
        it('retourne l\'utilisateur ADMINISTRATEUR', async () => {
            const user = { id: 1, role: 'ADMINISTRATEUR', email: 'admin@m.com' };
            mockAuthService.isAuthenticated.mockResolvedValue({
                isAuthenticated: true, user
            });

            const result = await AdminGuard.checkAccess();
            expect(result).toEqual(user);
        });

        it('retourne l\'utilisateur EMPLOYE', async () => {
            const user = { id: 2, role: 'EMPLOYE', email: 'emp@m.com' };
            mockAuthService.isAuthenticated.mockResolvedValue({
                isAuthenticated: true, user
            });

            const result = await AdminGuard.checkAccess();
            expect(result).toEqual(user);
        });
    });

    // ─── Non authentifié ─────────────────────────────────────────────

    describe('non authentifié', () => {
        it('throw "Non authentifié" si isAuthenticated retourne null', async () => {
            mockAuthService.isAuthenticated.mockResolvedValue(null);
            await expect(AdminGuard.checkAccess()).rejects.toThrow('Non authentifié');
        });

        it('throw si isAuthenticated retourne { isAuthenticated: false }', async () => {
            mockAuthService.isAuthenticated.mockResolvedValue({ isAuthenticated: false });
            await expect(AdminGuard.checkAccess()).rejects.toThrow('Non authentifié');
        });

        it('throw si l\'objet user est absent', async () => {
            mockAuthService.isAuthenticated.mockResolvedValue({ isAuthenticated: true });
            await expect(AdminGuard.checkAccess()).rejects.toThrow('Non authentifié');
        });

        it('log un warning quand l\'accès est bloqué', async () => {
            mockAuthService.isAuthenticated.mockResolvedValue(null);
            try { await AdminGuard.checkAccess(); } catch {}
            expect(Logger.warn).toHaveBeenCalled();
        });
    });

    // ─── Rôle insuffisant ────────────────────────────────────────────

    describe('rôle insuffisant', () => {
        it('throw "Accès interdit" pour un CLIENT', async () => {
            mockAuthService.isAuthenticated.mockResolvedValue({
                isAuthenticated: true,
                user: { id: 3, role: 'CLIENT' }
            });
            await expect(AdminGuard.checkAccess()).rejects.toThrow('Accès interdit');
        });

        it('throw "Accès interdit" pour un rôle inconnu', async () => {
            mockAuthService.isAuthenticated.mockResolvedValue({
                isAuthenticated: true,
                user: { id: 4, role: 'SUPER_ADMIN' }
            });
            await expect(AdminGuard.checkAccess()).rejects.toThrow('Accès interdit');
        });
    });

    // ─── Erreur réseau ───────────────────────────────────────────────

    describe('erreur réseau', () => {
        it('propage l\'erreur réseau d\'AuthService', async () => {
            mockAuthService.isAuthenticated.mockRejectedValue(new Error('Offline'));
            await expect(AdminGuard.checkAccess()).rejects.toThrow('Offline');
        });
    });
});
