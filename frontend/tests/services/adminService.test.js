import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';
import { setupGlobals } from '../helpers/setup-globals.js';
import { mockFetch, mockFetchError } from '../helpers/mock-fetch.js';

/**
 * Tests unitaires — adminService.js
 * Couvre : getEmployees, createEmployee, disableUser, getStats
 */
describe('adminService.js', () => {

    beforeEach(() => {
        setupGlobals();
        loadScript('js/services/adminService.js');
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    // ─── getEmployees ────────────────────────────────────────────────

    describe('getEmployees()', () => {
        it('appelle GET /api/admin/employees', async () => {
            const employees = [{ id: 1, email: 'emp@m.com' }];
            mockFetch(200, employees);

            const result = await AdminService.getEmployees();

            expect(fetch.mock.calls[0][0]).toBe('/api/admin/employees');
            expect(result).toEqual(employees);
        });

        it('throw si la réponse est en erreur', async () => {
            mockFetch(500, {});
            await expect(AdminService.getEmployees())
                .rejects.toThrow('Erreur lors de la récupération des employés');
        });
    });

    // ─── createEmployee ──────────────────────────────────────────────

    describe('createEmployee()', () => {
        it('envoie un POST avec les données de l\'employé', async () => {
            const empData = { email: 'new@m.com', password: 'Pass1234', firstName: 'Jean' };
            mockFetch(201, { id: 5, message: 'Employé créé' });

            const result = await AdminService.createEmployee(empData);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/admin/employees');
            expect(opts.method).toBe('POST');
            expect(JSON.parse(opts.body)).toEqual(empData);
            expect(result.id).toBe(5);
        });

        it('throw avec le message d\'erreur du serveur', async () => {
            mockFetch(400, { message: 'Email déjà utilisé' });
            await expect(AdminService.createEmployee({ email: 'dup@m.com' }))
                .rejects.toThrow('Email déjà utilisé');
        });
    });

    // ─── disableUser ─────────────────────────────────────────────────

    describe('disableUser()', () => {
        it('envoie un PATCH vers /api/admin/users/:id/disable', async () => {
            mockFetch(200, { success: true });
            await AdminService.disableUser(12);

            const [url, opts] = fetch.mock.calls[0];
            expect(url).toBe('/api/admin/users/12/disable');
            expect(opts.method).toBe('PATCH');
        });

        it('throw avec le message d\'erreur du serveur', async () => {
            mockFetch(400, { message: 'Impossible de désactiver un admin' });
            await expect(AdminService.disableUser(1))
                .rejects.toThrow('Impossible de désactiver un admin');
        });
    });

    // ─── getStats ────────────────────────────────────────────────────

    describe('getStats()', () => {
        it('appelle GET avec les filtres en query string', async () => {
            mockFetch(200, { totalCommandes: 50, revenue: 5000 });
            await AdminService.getStats({ startDate: '2026-01-01', endDate: '2026-12-31' });

            const url = fetch.mock.calls[0][0];
            expect(url).toContain('startDate=2026-01-01');
            expect(url).toContain('endDate=2026-12-31');
        });

        it('fonctionne sans filtres', async () => {
            mockFetch(200, { totalCommandes: 10 });
            const result = await AdminService.getStats();

            expect(result.totalCommandes).toBe(10);
        });

        it('throw si la réponse est en erreur', async () => {
            mockFetch(500, {});
            await expect(AdminService.getStats())
                .rejects.toThrow('Erreur récupération statistiques');
        });
    });
});
