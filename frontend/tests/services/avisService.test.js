import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';
import { setupGlobals } from '../helpers/setup-globals.js';
import { mockFetch, mockFetchError } from '../helpers/mock-fetch.js';

/**
 * Tests unitaires — avisService.js
 * Couvre : createAvis, getAvis, validateAvis, deleteAvis
 */
describe('avisService.js', () => {

    beforeEach(() => {
        setupGlobals();
        loadScript('js/services/avisService.js');
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    // ─── createAvis ──────────────────────────────────────────────────

    describe('createAvis()', () => {
        it('envoie un POST avec les données de l\'avis', async () => {
            const avisData = { commandeId: 3, note: 5, commentaire: 'Excellent !' };
            mockFetch(201, { id: 10, message: 'Avis enregistré' });

            const result = await AvisService.createAvis(avisData);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/avis');
            expect(opts.method).toBe('POST');
            expect(opts.credentials).toBe('include');
            expect(JSON.parse(opts.body)).toEqual(avisData);
            expect(result.id).toBe(10);
        });

        it('throw avec le message d\'erreur du serveur', async () => {
            mockFetch(400, { error: 'Avis déjà déposé pour cette commande' });
            await expect(AvisService.createAvis({ commandeId: 3 }))
                .rejects.toThrow('Avis déjà déposé');
        });
    });

    // ─── getAvis ─────────────────────────────────────────────────────

    describe('getAvis()', () => {
        it('récupère tous les avis sans filtre', async () => {
            const avis = [{ id: 1, note: 4 }, { id: 2, note: 5 }];
            mockFetch(200, avis);

            const result = await AvisService.getAvis();

            expect(fetch.mock.calls[0][0]).toBe('/api/avis');
            expect(result).toEqual(avis);
        });

        it('filtre par statut en query string', async () => {
            mockFetch(200, []);
            await AvisService.getAvis('EN_ATTENTE');

            expect(fetch.mock.calls[0][0]).toBe('/api/avis?status=EN_ATTENTE');
        });

        it('throw si la réponse est en erreur', async () => {
            mockFetch(500, {});
            await expect(AvisService.getAvis())
                .rejects.toThrow('Impossible de récupérer les avis');
        });
    });

    // ─── validateAvis ────────────────────────────────────────────────

    describe('validateAvis()', () => {
        it('envoie un PUT vers /api/avis/:id/validate', async () => {
            mockFetch(200, { success: true });
            await AvisService.validateAvis(5);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/avis/5/validate');
            expect(opts.method).toBe('PUT');
            expect(opts.credentials).toBe('include');
        });

        it('throw si la validation échoue', async () => {
            mockFetch(400, {});
            await expect(AvisService.validateAvis(5))
                .rejects.toThrow('Erreur validation avis');
        });
    });

    // ─── deleteAvis ──────────────────────────────────────────────────

    describe('deleteAvis()', () => {
        it('envoie un DELETE vers /api/avis/:id', async () => {
            mockFetch(200, { success: true });
            await AvisService.deleteAvis(7);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/avis/7');
            expect(opts.method).toBe('DELETE');
        });

        it('throw si la suppression échoue', async () => {
            mockFetch(500, {});
            await expect(AvisService.deleteAvis(7))
                .rejects.toThrow('Erreur suppression avis');
        });
    });
});
