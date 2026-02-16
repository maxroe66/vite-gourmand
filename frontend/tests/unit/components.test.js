import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';
import { mockFetch, mockFetchError } from '../helpers/mock-fetch.js';

/**
 * Tests unitaires — components.js
 * Couvre : loadComponent, CSRF init, chargement navbar/footer,
 *          currentYear, dispatch componentsLoaded
 */
describe('components.js', () => {

    beforeEach(() => {
        document.body.innerHTML = `
            <header id="header-placeholder"></header>
            <footer id="footer-placeholder"></footer>
            <span id="currentYear"></span>
        `;

        globalThis.Logger = { error: vi.fn(), warn: vi.fn(), log: vi.fn() };
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    // ─── loadComponent ───────────────────────────────────────────────

    describe('loadComponent()', () => {
        it('charge le HTML d\'un composant dans l\'élément cible', async () => {
            // Charger le script pour avoir la fonction globale
            loadScript('js/core/components.js');

            const htmlContent = '<nav>Navbar HTML</nav>';
            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: true,
                text: vi.fn().mockResolvedValue(htmlContent)
            });

            await loadComponent('header-placeholder', 'navbar.html');

            const header = document.getElementById('header-placeholder');
            expect(header.innerHTML).toBe(htmlContent);
        });

        it('construit le chemin avec le basePath /frontend/pages/components/', async () => {
            loadScript('js/core/components.js');

            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: true,
                text: vi.fn().mockResolvedValue('<div>Test</div>')
            });

            await loadComponent('header-placeholder', 'navbar.html');

            expect(fetch.mock.calls[0][0]).toBe('/frontend/pages/components/navbar.html');
        });

        it('log une erreur si le fetch échoue', async () => {
            loadScript('js/core/components.js');

            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: false,
                status: 404
            });

            await loadComponent('header-placeholder', 'missing.html');

            expect(Logger.error).toHaveBeenCalled();
        });

        it('log une erreur réseau', async () => {
            loadScript('js/core/components.js');

            globalThis.fetch = vi.fn().mockRejectedValue(new Error('Offline'));

            await loadComponent('header-placeholder', 'test.html');

            expect(Logger.error).toHaveBeenCalled();
        });

        it('ne crashe pas si l\'élément cible n\'existe pas', async () => {
            loadScript('js/core/components.js');

            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: true,
                text: vi.fn().mockResolvedValue('<div>OK</div>')
            });

            // Pas de crash, même si l'ID n'existe pas
            await expect(loadComponent('nonexistent', 'test.html')).resolves.not.toThrow();
        });
    });

    // ─── DOMContentLoaded init ───────────────────────────────────────

    describe('initialisation DOMContentLoaded', () => {
        it('dispatche componentsLoaded après le chargement', async () => {
            // Mock fetch pour toutes les requêtes (CSRF + 2 composants)
            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: true,
                text: vi.fn().mockResolvedValue('<div>Component</div>')
            });

            const listener = vi.fn();
            document.addEventListener('componentsLoaded', listener);

            vi.resetModules();
            await import('../../js/core/components.js');
            document.dispatchEvent(new Event('DOMContentLoaded'));

            // Attendre que les promesses async se résolvent
            await new Promise(r => setTimeout(r, 50));

            expect(listener).toHaveBeenCalled();
        });

        it('initialise le CSRF en appelant /api/csrf', async () => {
            globalThis.fetch = vi.fn().mockResolvedValue({
                ok: true,
                text: vi.fn().mockResolvedValue('<div>OK</div>')
            });

            vi.resetModules();
            await import('../../js/core/components.js');
            document.dispatchEvent(new Event('DOMContentLoaded'));

            await new Promise(r => setTimeout(r, 50));

            // Le premier appel fetch doit être /api/csrf
            const csrfCall = fetch.mock.calls.find(c => c[0] === '/api/csrf');
            expect(csrfCall).toBeDefined();
        });
    });
});
