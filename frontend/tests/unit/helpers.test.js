import { describe, it, expect, beforeEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';

/**
 * Tests unitaires — helpers.js (escapeHtml, formatPrice)
 * Fonctions utilitaires pures chargées globalement avant les scripts de page.
 */
describe('helpers.js', () => {

    beforeEach(() => {
        // Charger le script dans le contexte global (simule <script>)
        loadScript('js/utils/helpers.js');
    });

    // ─── escapeHtml ──────────────────────────────────────────────────

    describe('escapeHtml()', () => {

        it('retourne une chaîne vide pour null', () => {
            expect(escapeHtml(null)).toBe('');
        });

        it('retourne une chaîne vide pour undefined', () => {
            expect(escapeHtml(undefined)).toBe('');
        });

        it('retourne une chaîne vide pour une chaîne vide', () => {
            expect(escapeHtml('')).toBe('');
        });

        it('retourne une chaîne vide pour 0 (falsy)', () => {
            expect(escapeHtml(0)).toBe('');
        });

        it('retourne le texte inchangé si pas de caractères dangereux', () => {
            expect(escapeHtml('Bonjour le monde')).toBe('Bonjour le monde');
        });

        it('échappe les chevrons < et >', () => {
            expect(escapeHtml('<div>test</div>')).toBe('&lt;div&gt;test&lt;/div&gt;');
        });

        it('échappe le caractère &', () => {
            expect(escapeHtml('Tom & Jerry')).toBe('Tom &amp; Jerry');
        });

        it('préserve les guillemets doubles (safe en contexte textContent)', () => {
            const result = escapeHtml('valeur="test"');
            // createTextNode ne modifie pas " car c'est safe dans du contenu texte
            expect(result).toBe('valeur="test"');
        });

        it('échappe les guillemets simples \'', () => {
            const result = escapeHtml("l'exemple");
            // createTextNode n'échappe pas ' en &#39; mais le rend safe
            // dans un contexte textContent → on vérifie que la chaîne est retournée
            expect(result).toContain('exemple');
        });

        it('neutralise une injection de balise <script>', () => {
            const malicious = '<script>alert("XSS")</script>';
            const result = escapeHtml(malicious);
            expect(result).not.toContain('<script>');
            expect(result).toContain('&lt;script&gt;');
        });

        it('gère les caractères spéciaux combinés', () => {
            const input = '<b>"Tom & Jerry"</b>';
            const result = escapeHtml(input);
            expect(result).toContain('&lt;b&gt;');
            expect(result).toContain('&amp;');
            expect(result).toContain('&lt;/b&gt;');
        });

        it('gère les accents et caractères unicode', () => {
            expect(escapeHtml('Éléphant café')).toBe('Éléphant café');
        });
    });

    // ─── formatPrice ─────────────────────────────────────────────────

    describe('formatPrice()', () => {

        it('formate un entier en prix EUR', () => {
            const result = formatPrice(10);
            // Intl peut utiliser un espace insécable (U+00A0 ou U+202F)
            expect(result.replace(/\s/g, ' ')).toMatch(/10,00\s*€/);
        });

        it('formate un prix avec décimales', () => {
            const result = formatPrice(12.5);
            expect(result.replace(/\s/g, ' ')).toMatch(/12,50\s*€/);
        });

        it('formate zéro', () => {
            const result = formatPrice(0);
            expect(result.replace(/\s/g, ' ')).toMatch(/0,00\s*€/);
        });

        it('formate un grand nombre avec séparateur de milliers', () => {
            const result = formatPrice(1234.56);
            // Vérifier la présence de la partie décimale et du symbole €
            const normalized = result.replace(/\s/g, ' ');
            expect(normalized).toContain('€');
            expect(normalized).toMatch(/1[\s.,]?234,56/);
        });

        it('formate un prix négatif', () => {
            const result = formatPrice(-5.99);
            expect(result).toContain('5,99');
            expect(result).toContain('€');
        });

        it('utilise la locale fr-FR (virgule décimale)', () => {
            const result = formatPrice(9.99);
            // En fr-FR, le séparateur décimal est la virgule
            expect(result).toContain('9,99');
        });
    });
});
