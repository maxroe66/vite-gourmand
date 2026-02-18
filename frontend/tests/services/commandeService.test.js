import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';
import { setupGlobals } from '../helpers/setup-globals.js';
import { mockFetch, mockFetchError } from '../helpers/mock-fetch.js';

/**
 * Tests unitaires — commandeService.js
 * Couvre : calculatePrice, createOrder, getMyOrders, getOrder,
 *          cancelOrder, updateOrder, getAllOrders, updateStatus, returnMaterial
 */
describe('commandeService.js', () => {

    beforeEach(() => {
        setupGlobals();
        loadScript('js/services/commandeService.js');
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    // ─── calculatePrice ──────────────────────────────────────────────

    describe('calculatePrice()', () => {
        it('envoie un POST avec les données de calcul', async () => {
            const data = { menuId: 1, nombrePersonnes: 10, adresseLivraison: '33000 Bordeaux' };
            mockFetch(200, { prixTotal: 250.00, prixParPersonne: 25.00 });

            const result = await CommandeService.calculatePrice(data);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/commandes/calculate-price');
            expect(opts.method).toBe('POST');
            expect(opts.credentials).toBe('include');
            expect(result.prixTotal).toBe(250.00);
        });

        it('throw si la réponse est en erreur', async () => {
            mockFetch(400, { error: 'Menu introuvable' });
            await expect(CommandeService.calculatePrice({ menuId: 999 }))
                .rejects.toThrow('Menu introuvable');
        });
    });

    // ─── createOrder ─────────────────────────────────────────────────

    describe('createOrder()', () => {
        it('crée une commande et retourne le résultat', async () => {
            const orderData = { menuId: 1, nombrePersonnes: 8, date: '2026-03-01' };
            mockFetch(201, { success: true, id: 42 });

            const result = await CommandeService.createOrder(orderData);

            expect(result.success).toBe(true);
            expect(result.id).toBe(42);
            expect(fetch.mock.calls[0][1].method).toBe('POST');
        });

        it('throw avec les erreurs de validation concaténées', async () => {
            mockFetch(422, { errors: { date: 'Date invalide', menu: 'Menu requis' } });
            await expect(CommandeService.createOrder({}))
                .rejects.toThrow('Date invalide');
        });

        it('throw avec l\'erreur générique du serveur', async () => {
            mockFetch(500, { error: 'Erreur serveur' });
            await expect(CommandeService.createOrder({}))
                .rejects.toThrow('Erreur serveur');
        });
    });

    // ─── getMyOrders ─────────────────────────────────────────────────

    describe('getMyOrders()', () => {
        it('appelle GET /api/my-orders avec credentials', async () => {
            const orders = [{ id: 1, status: 'EN_COURS' }];
            mockFetch(200, orders);

            const result = await CommandeService.getMyOrders();

            expect(fetch.mock.calls[0][0]).toBe('/api/my-orders');
            expect(fetch.mock.calls[0][1].credentials).toBe('include');
            expect(result).toEqual(orders);
        });

        it('throw si la réponse est en erreur', async () => {
            mockFetch(500, {});
            await expect(CommandeService.getMyOrders())
                .rejects.toThrow('Impossible de récupérer les commandes');
        });
    });

    // ─── getOrder ────────────────────────────────────────────────────

    describe('getOrder()', () => {
        it('appelle GET /api/commandes/:id', async () => {
            mockFetch(200, { id: 5, status: 'CONFIRMEE', timeline: [] });
            const result = await CommandeService.getOrder(5);

            expect(fetch.mock.calls[0][0]).toBe('/api/commandes/5');
            expect(result.id).toBe(5);
        });
    });

    // ─── cancelOrder ─────────────────────────────────────────────────

    describe('cancelOrder()', () => {
        it('envoie un PATCH avec status ANNULEE', async () => {
            mockFetch(200, { success: true });
            await CommandeService.cancelOrder(3);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/commandes/3');
            expect(opts.method).toBe('PATCH');
            expect(JSON.parse(opts.body).status).toBe('ANNULEE');
        });

        it('throw avec le message d\'erreur du serveur', async () => {
            mockFetch(400, { error: 'Délai dépassé' });
            await expect(CommandeService.cancelOrder(3))
                .rejects.toThrow('Délai dépassé');
        });
    });

    // ─── updateOrder ─────────────────────────────────────────────────

    describe('updateOrder()', () => {
        it('envoie un PATCH avec les données de mise à jour', async () => {
            mockFetch(200, { success: true });
            await CommandeService.updateOrder(2, { nombrePersonnes: 12 });

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/commandes/2');
            expect(opts.method).toBe('PATCH');
            expect(JSON.parse(opts.body).nombrePersonnes).toBe(12);
        });
    });

    // ─── getAllOrders ─────────────────────────────────────────────────

    describe('getAllOrders()', () => {
        it('passe les filtres en query string', async () => {
            mockFetch(200, []);
            await CommandeService.getAllOrders({ status: 'EN_COURS' });

            expect(fetch.mock.calls[0][0]).toContain('status=EN_COURS');
        });
    });

    // ─── updateStatus ────────────────────────────────────────────────

    describe('updateStatus()', () => {
        it('envoie un PUT vers /api/commandes/:id/status', async () => {
            mockFetch(200, { success: true });
            await CommandeService.updateStatus(4, 'LIVREE');

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/commandes/4/status');
            expect(opts.method).toBe('PUT');
            expect(JSON.parse(opts.body).status).toBe('LIVREE');
        });

        it('inclut motif et modeContact quand fournis', async () => {
            mockFetch(200, { success: true });
            await CommandeService.updateStatus(4, 'ANNULEE', 'Stock insuffisant', 'EMAIL');

            const body = JSON.parse(fetch.mock.calls[0][1].body);
            expect(body.motif).toBe('Stock insuffisant');
            expect(body.modeContact).toBe('EMAIL');
        });
    });

    // ─── returnMaterial ──────────────────────────────────────────────

    describe('returnMaterial()', () => {
        it('envoie un POST vers /api/commandes/:id/return-material', async () => {
            mockFetch(200, { success: true });
            await CommandeService.returnMaterial(8);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/commandes/8/return-material');
            expect(opts.method).toBe('POST');
        });

        it('throw avec l\'erreur spécifique du serveur', async () => {
            mockFetch(400, { error: 'Matériel déjà retourné' });
            await expect(CommandeService.returnMaterial(8))
                .rejects.toThrow('Matériel déjà retourné');
        });
    });

    // ─── getOverdueMaterials ─────────────────────────────────────────

    describe('getOverdueMaterials()', () => {
        it('appelle GET /api/commandes/overdue-materials sans notify par défaut', async () => {
            mockFetch(200, { count: 0, overdueCommandes: [], emailsSent: false });
            const result = await CommandeService.getOverdueMaterials();

            const url = fetch.mock.calls[0][0];
            expect(url).toContain('/overdue-materials');
            expect(url).not.toContain('notify');
            expect(result.count).toBe(0);
        });

        it('ajoute ?notify=true quand demandé', async () => {
            mockFetch(200, { count: 2, overdueCommandes: [{}, {}], emailsSent: true });
            const result = await CommandeService.getOverdueMaterials(true);

            const url = fetch.mock.calls[0][0];
            expect(url).toContain('notify=true');
            expect(result.emailsSent).toBe(true);
            expect(result.count).toBe(2);
        });

        it('envoie credentials include', async () => {
            mockFetch(200, { count: 0, overdueCommandes: [] });
            await CommandeService.getOverdueMaterials();

            expect(fetch.mock.calls[0][1].credentials).toBe('include');
        });

        it('throw si la réponse est en erreur', async () => {
            mockFetch(403, { error: 'Accès interdit' });
            await expect(CommandeService.getOverdueMaterials())
                .rejects.toThrow('Accès interdit');
        });

        it('throw avec message par défaut si pas d\'erreur spécifique', async () => {
            mockFetch(500, {});
            await expect(CommandeService.getOverdueMaterials())
                .rejects.toThrow('Erreur vérification retards matériel');
        });
    });
});
