import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';

/**
 * Tests unitaires — navbar.js
 * Couvre : initNavbar (toggle, aria-expanded, fermeture par lien/extérieur/Escape)
 */
describe('navbar.js', () => {

    /**
     * Crée la structure HTML minimale de la navbar dans le DOM.
     */
    function setupNavbarDOM() {
        document.body.innerHTML = `
            <nav class="navbar">
                <button class="navbar__toggle" aria-expanded="false">☰</button>
                <ul id="navMenu" hidden>
                    <li><a href="/home">Accueil</a></li>
                    <li><a href="/menus">Menus</a></li>
                </ul>
            </nav>
            <div id="outside">Contenu page</div>
        `;
    }

    beforeEach(() => {
        setupNavbarDOM();
        // Réinitialiser le flag navbarInitialized
        globalThis.navbarInitialized = false;
        loadScript('js/core/navbar.js');
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    /** Récupère le toggle + menu */
    function getElements() {
        return {
            toggle: document.querySelector('.navbar__toggle'),
            menu: document.getElementById('navMenu')
        };
    }

    // ─── Toggle au clic ──────────────────────────────────────────────

    describe('toggle au clic', () => {
        it('ouvre le menu au premier clic', () => {
            const { toggle, menu } = getElements();
            // Dispatch componentsLoaded pour déclencher initNavbar
            document.dispatchEvent(new Event('componentsLoaded'));

            toggle.click();

            expect(toggle.getAttribute('aria-expanded')).toBe('true');
            expect(menu.hidden).toBe(false);
        });

        it('ferme le menu au second clic', () => {
            const { toggle, menu } = getElements();
            document.dispatchEvent(new Event('componentsLoaded'));

            toggle.click(); // ouvrir
            toggle.click(); // fermer

            expect(toggle.getAttribute('aria-expanded')).toBe('false');
            expect(menu.hidden).toBe(true);
        });
    });

    // ─── Fermeture au clic sur un lien ───────────────────────────────

    describe('fermeture au clic sur un lien', () => {
        it('ferme le menu quand un lien est cliqué', () => {
            const { toggle, menu } = getElements();
            document.dispatchEvent(new Event('componentsLoaded'));

            toggle.click(); // ouvrir
            const link = menu.querySelector('a');
            link.click(); // cliquer un lien

            expect(toggle.getAttribute('aria-expanded')).toBe('false');
            expect(menu.hidden).toBe(true);
        });
    });

    // ─── Fermeture au clic extérieur ─────────────────────────────────

    describe('fermeture au clic extérieur', () => {
        it('ferme le menu au clic en dehors', () => {
            const { toggle, menu } = getElements();
            document.dispatchEvent(new Event('componentsLoaded'));

            toggle.click(); // ouvrir

            // Cliquer en dehors du menu et du toggle
            const outside = document.getElementById('outside');
            outside.click();

            expect(toggle.getAttribute('aria-expanded')).toBe('false');
            expect(menu.hidden).toBe(true);
        });

        it('ne ferme pas si le menu est déjà fermé', () => {
            const { toggle, menu } = getElements();
            document.dispatchEvent(new Event('componentsLoaded'));

            // Menu fermé par défaut — cliquer dehors ne devrait rien changer
            const outside = document.getElementById('outside');
            outside.click();

            expect(toggle.getAttribute('aria-expanded')).toBe('false');
            expect(menu.hidden).toBe(true);
        });
    });

    // ─── Fermeture avec Escape ───────────────────────────────────────

    describe('fermeture avec Escape', () => {
        it('ferme le menu à l\'appui de Escape', () => {
            const { toggle, menu } = getElements();
            document.dispatchEvent(new Event('componentsLoaded'));

            toggle.click(); // ouvrir

            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));

            expect(toggle.getAttribute('aria-expanded')).toBe('false');
            expect(menu.hidden).toBe(true);
        });

        it('ne fait rien si le menu est déjà fermé', () => {
            const { toggle } = getElements();
            document.dispatchEvent(new Event('componentsLoaded'));

            // Escape sans menu ouvert
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
            expect(toggle.getAttribute('aria-expanded')).toBe('false');
        });
    });

    // ─── Double initialisation ───────────────────────────────────────

    describe('protection double initialisation', () => {
        it('ne s\'initialise pas deux fois', () => {
            const { toggle, menu } = getElements();
            document.dispatchEvent(new Event('componentsLoaded'));
            document.dispatchEvent(new Event('componentsLoaded')); // double

            // Un seul click handler doit exister
            toggle.click();
            expect(toggle.getAttribute('aria-expanded')).toBe('true');
            toggle.click();
            expect(toggle.getAttribute('aria-expanded')).toBe('false');
        });
    });

    // ─── Éléments absents ────────────────────────────────────────────

    describe('éléments absents', () => {
        it('ne crashe pas si le toggle n\'existe pas', () => {
            document.body.innerHTML = '<div>Pas de navbar</div>';
            globalThis.navbarInitialized = false;
            expect(() => {
                loadScript('js/core/navbar.js');
                document.dispatchEvent(new Event('componentsLoaded'));
            }).not.toThrow();
        });
    });
});
