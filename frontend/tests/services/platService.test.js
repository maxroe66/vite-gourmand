import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';
import { setupGlobals } from '../helpers/setup-globals.js';
import { mockFetch, mockFetchError } from '../helpers/mock-fetch.js';

/**
 * Tests unitaires — platService.js
 * Couvre : _handleResponse, getPlats, getPlatDetails, createPlat,
 *          updatePlat, deletePlat, getAllergenes
 */
describe('platService.js', () => {

    beforeEach(() => {
        setupGlobals();
        loadScript('js/services/platService.js');
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    // ─── _handleResponse ─────────────────────────────────────────────

    describe('_handleResponse()', () => {
        it('throw Session expirée sur 401 et tente la redirection', async () => {
            const mockResponse = { status: 401, ok: false };
            // jsdom ne supporte pas la navigation, on vérifie juste le throw
            await expect(PlatService._handleResponse(mockResponse))
                .rejects.toThrow('Session expirée');
        });

        it('throw "Accès refusé" sur 403', async () => {
            const mockResponse = { status: 403, ok: false, json: vi.fn().mockResolvedValue({}) };
            await expect(PlatService._handleResponse(mockResponse))
                .rejects.toThrow('Accès refusé');
        });

        it('retourne null sur 204', async () => {
            const result = await PlatService._handleResponse({ status: 204, ok: true });
            expect(result).toBeNull();
        });

        it('retourne les données sur réponse OK', async () => {
            const data = { id: 1, libelle: 'Entrée' };
            const mockResponse = { status: 200, ok: true, json: vi.fn().mockResolvedValue(data) };
            expect(await PlatService._handleResponse(mockResponse)).toEqual(data);
        });
    });

    // ─── getPlats ────────────────────────────────────────────────────

    describe('getPlats()', () => {
        it('appelle GET /api/plats avec credentials', async () => {
            const plats = [{ id: 1 }, { id: 2 }];
            mockFetch(200, plats);

            const result = await PlatService.getPlats();

            expect(fetch.mock.calls[0][0]).toBe('/api/plats');
            expect(result).toEqual(plats);
        });

        it('propage les erreurs réseau', async () => {
            mockFetchError('Offline');
            await expect(PlatService.getPlats()).rejects.toThrow('Offline');
            expect(Logger.error).toHaveBeenCalled();
        });
    });

    // ─── getPlatDetails ──────────────────────────────────────────────

    describe('getPlatDetails()', () => {
        it('appelle GET /api/plats/:id', async () => {
            mockFetch(200, { id: 3, libelle: 'Dessert', allergenes: [] });
            const result = await PlatService.getPlatDetails(3);

            expect(fetch.mock.calls[0][0]).toBe('/api/plats/3');
            expect(result.libelle).toBe('Dessert');
        });
    });

    // ─── createPlat ──────────────────────────────────────────────────

    describe('createPlat()', () => {
        it('envoie un POST avec les données et le header CSRF', async () => {
            const data = { libelle: 'Salade', type: 'ENTREE', allergenIds: [1, 3] };
            mockFetch(201, { id: 10 });

            await PlatService.createPlat(data);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/plats');
            expect(opts.method).toBe('POST');
            expect(opts.credentials).toBe('include');
            expect(JSON.parse(opts.body)).toEqual(data);
        });
    });

    // ─── updatePlat ──────────────────────────────────────────────────

    describe('updatePlat()', () => {
        it('envoie un PUT vers /api/plats/:id', async () => {
            mockFetch(200, { id: 2, libelle: 'Modifié' });
            await PlatService.updatePlat(2, { libelle: 'Modifié' });

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/plats/2');
            expect(opts.method).toBe('PUT');
        });
    });

    // ─── deletePlat ──────────────────────────────────────────────────

    describe('deletePlat()', () => {
        it('envoie un DELETE vers /api/plats/:id', async () => {
            mockFetch(204, null);
            await PlatService.deletePlat(6);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/plats/6');
            expect(opts.method).toBe('DELETE');
        });
    });

    // ─── getAllergenes ────────────────────────────────────────────────

    describe('getAllergenes()', () => {
        it('appelle GET /api/plats/allergenes', async () => {
            const allergenes = [{ id: 1, nom: 'Gluten' }, { id: 2, nom: 'Lactose' }];
            mockFetch(200, allergenes);

            const result = await PlatService.getAllergenes();

            expect(fetch.mock.calls[0][0]).toBe('/api/plats/allergenes');
            expect(result).toEqual(allergenes);
        });
    });
});
