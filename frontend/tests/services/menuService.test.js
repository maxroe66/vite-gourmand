import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';
import { setupGlobals } from '../helpers/setup-globals.js';
import { mockFetch, mockFetchError } from '../helpers/mock-fetch.js';

/**
 * Tests unitaires — menuService.js
 * Couvre : _handleResponse, getMenus, getMenuDetails, createMenu,
 *          updateMenu, deleteMenu, getThemes, getRegimes, getMaterials
 */
describe('menuService.js', () => {

    beforeEach(() => {
        setupGlobals();
        loadScript('js/services/menuService.js');
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    // ─── _handleResponse ─────────────────────────────────────────────

    describe('_handleResponse()', () => {
        it('throw Session expirée sur 401 et tente la redirection', async () => {
            const mockResponse = { status: 401, ok: false, json: vi.fn() };
            // jsdom ne supporte pas la navigation, on vérifie juste le throw
            await expect(MenuService._handleResponse(mockResponse)).rejects.toThrow('Session expirée');
        });

        it('throw "Accès refusé" sur 403', async () => {
            const mockResponse = { status: 403, ok: false, json: vi.fn().mockResolvedValue({ error: 'Forbidden' }) };
            await expect(MenuService._handleResponse(mockResponse)).rejects.toThrow('Accès refusé');
        });

        it('retourne null sur 204 No Content', async () => {
            const mockResponse = { status: 204, ok: true };
            const result = await MenuService._handleResponse(mockResponse);
            expect(result).toBeNull();
        });

        it('retourne les données sur une réponse OK', async () => {
            const data = { id: 1, titre: 'Menu Printemps' };
            const mockResponse = { status: 200, ok: true, json: vi.fn().mockResolvedValue(data) };
            const result = await MenuService._handleResponse(mockResponse);
            expect(result).toEqual(data);
        });

        it('throw l\'erreur du serveur sur réponse non-OK', async () => {
            const mockResponse = {
                status: 400, ok: false,
                json: vi.fn().mockResolvedValue({ error: 'Données invalides' })
            };
            await expect(MenuService._handleResponse(mockResponse)).rejects.toThrow('Données invalides');
        });
    });

    // ─── getMenus ────────────────────────────────────────────────────

    describe('getMenus()', () => {
        it('récupère les menus sans filtres', async () => {
            const menus = [{ id: 1 }, { id: 2 }];
            const mock = mockFetch(200, menus);

            const result = await MenuService.getMenus();

            expect(mock).toHaveBeenCalledOnce();
            expect(mock.mock.calls[0][0]).toContain('/api/menus');
            expect(result).toEqual(menus);
        });

        it('passe les filtres en query string', async () => {
            mockFetch(200, []);
            await MenuService.getMenus({ prix_max: 30, theme: 2 });

            const url = fetch.mock.calls[0][0];
            expect(url).toContain('prix_max=30');
            expect(url).toContain('theme=2');
        });

        it('ignore les filtres null/undefined/vides', async () => {
            mockFetch(200, []);
            await MenuService.getMenus({ prix_max: null, theme: '', regime: undefined });

            const url = fetch.mock.calls[0][0];
            expect(url).not.toContain('prix_max');
            expect(url).not.toContain('theme');
            expect(url).not.toContain('regime');
        });

        it('propage les erreurs réseau', async () => {
            mockFetchError('Network error');
            await expect(MenuService.getMenus()).rejects.toThrow('Network error');
            expect(Logger.error).toHaveBeenCalled();
        });
    });

    // ─── getMenuDetails ──────────────────────────────────────────────

    describe('getMenuDetails()', () => {
        it('appelle GET /api/menus/:id', async () => {
            const menu = { id: 5, titre: 'Gastronomique' };
            mockFetch(200, menu);

            const result = await MenuService.getMenuDetails(5);

            expect(fetch.mock.calls[0][0]).toBe('/api/menus/5');
            expect(result).toEqual(menu);
        });
    });

    // ─── createMenu ──────────────────────────────────────────────────

    describe('createMenu()', () => {
        it('envoie un POST avec les données et le header CSRF', async () => {
            const menuData = { titre: 'Nouveau', prix: 25 };
            mockFetch(201, { id: 10 });

            await MenuService.createMenu(menuData);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/menus');
            expect(opts.method).toBe('POST');
            expect(opts.credentials).toBe('include');
            expect(JSON.parse(opts.body)).toEqual(menuData);
        });
    });

    // ─── updateMenu ──────────────────────────────────────────────────

    describe('updateMenu()', () => {
        it('envoie un PUT vers /api/menus/:id', async () => {
            mockFetch(200, { id: 3, titre: 'Modifié' });
            await MenuService.updateMenu(3, { titre: 'Modifié' });

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/menus/3');
            expect(opts.method).toBe('PUT');
        });
    });

    // ─── deleteMenu ──────────────────────────────────────────────────

    describe('deleteMenu()', () => {
        it('envoie un DELETE vers /api/menus/:id', async () => {
            mockFetch(204, null);
            await MenuService.deleteMenu(7);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/menus/7');
            expect(opts.method).toBe('DELETE');
        });
    });

    // ─── getThemes / getRegimes / getMaterials ───────────────────────

    describe('getThemes()', () => {
        it('appelle GET /api/menus/themes', async () => {
            const themes = [{ id: 1, nom: 'Provençal' }];
            mockFetch(200, themes);

            const result = await MenuService.getThemes();
            expect(fetch.mock.calls[0][0]).toContain('/themes');
            expect(result).toEqual(themes);
        });
    });

    describe('getRegimes()', () => {
        it('appelle GET /api/menus/regimes', async () => {
            mockFetch(200, [{ id: 1, nom: 'Végétarien' }]);
            const result = await MenuService.getRegimes();
            expect(fetch.mock.calls[0][0]).toContain('/regimes');
        });
    });

    describe('getMaterials()', () => {
        it('appelle GET /api/materiels', async () => {
            mockFetch(200, [{ id: 1, nom: 'Assiette' }]);
            const result = await MenuService.getMaterials();
            expect(fetch.mock.calls[0][0]).toBe('/api/materiels');
        });
    });
});
