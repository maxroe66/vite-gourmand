/**
 * Tests — js/widgets/avis-carousel.js
 * Fonctions pures (renderStars, formatDate) + chargement DOM + initCarousel
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { loadScript } from '../helpers/load-script.js';

const SRC = 'js/widgets/avis-carousel.js';

/* ────────────────────────────────────────────
 *  renderStars() & formatDate() — fonctions pures
 * ──────────────────────────────────────────── */
describe('avis-carousel — renderStars()', () => {
    beforeEach(() => {
        globalThis.Logger = { error: vi.fn(), log: vi.fn(), warn: vi.fn() };
        globalThis.escapeHtml = vi.fn(s => s);
        loadScript(SRC);
    });

    afterEach(() => vi.restoreAllMocks());

    it('retourne 5 étoiles pleines pour note = 5', () => {
        expect(renderStars(5)).toBe('★★★★★');
    });

    it('retourne 5 étoiles vides pour note = 0', () => {
        expect(renderStars(0)).toBe('☆☆☆☆☆');
    });

    it('retourne 3 pleines + 2 vides pour note = 3', () => {
        expect(renderStars(3)).toBe('★★★☆☆');
    });

    it('retourne 1 pleine + 4 vides pour note = 1', () => {
        expect(renderStars(1)).toBe('★☆☆☆☆');
    });
});

describe('avis-carousel — formatDate()', () => {
    beforeEach(() => {
        globalThis.Logger = { error: vi.fn(), log: vi.fn(), warn: vi.fn() };
        globalThis.escapeHtml = vi.fn(s => s);
        loadScript(SRC);
    });

    afterEach(() => vi.restoreAllMocks());

    it('retourne chaîne vide pour null', () => {
        expect(formatDate(null)).toBe('');
    });

    it('retourne chaîne vide pour undefined', () => {
        expect(formatDate(undefined)).toBe('');
    });

    it('formate une date ISO en date française', () => {
        const result = formatDate('2024-06-15T12:00:00Z');
        expect(result).toContain('15');
    });

    it('gère un objet PHP DateTime avec propriété .date', () => {
        const phpDate = { date: '2024-01-20 10:30:00.000000' };
        const result = formatDate(phpDate);
        expect(result).toContain('20');
    });
});

/* ────────────────────────────────────────────
 *  DOMContentLoaded — chargement des avis
 * ──────────────────────────────────────────── */
describe('avis-carousel — chargement DOM', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
        globalThis.Logger = { error: vi.fn(), log: vi.fn(), warn: vi.fn() };
        globalThis.escapeHtml = vi.fn(s => s);
    });

    afterEach(() => {
        document.body.innerHTML = '';
        globalThis.fetch = undefined;
    });

    it('affiche les avis après un fetch réussi', async () => {
        document.body.innerHTML = '<div class="avis-clients__track"></div>';

        const reviews = [
            { commentaire: 'Excellent !', note: 5 },
            { commentaire: 'Correct', note: 3 }
        ];
        globalThis.fetch = vi.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({ data: reviews })
        });

        loadScript(SRC);
        document.dispatchEvent(new Event('DOMContentLoaded'));
        await new Promise(r => setTimeout(r, 50));

        const items = document.querySelectorAll('.avis-clients__item');
        expect(items.length).toBeGreaterThanOrEqual(2);
    });

    it('affiche un message vide si aucun avis', async () => {
        document.body.innerHTML = '<div class="avis-clients__track"></div>';

        globalThis.fetch = vi.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({ data: [] })
        });

        loadScript(SRC);
        document.dispatchEvent(new Event('DOMContentLoaded'));
        await new Promise(r => setTimeout(r, 50));

        const empty = document.querySelector('.avis-clients__empty');
        expect(empty).not.toBeNull();
        expect(empty.textContent).toContain('Aucun avis');
    });

    it('log une erreur si le fetch échoue', async () => {
        document.body.innerHTML = '<div class="avis-clients__track"></div>';

        globalThis.fetch = vi.fn().mockRejectedValue(new Error('Network'));

        loadScript(SRC);
        document.dispatchEvent(new Event('DOMContentLoaded'));
        await new Promise(r => setTimeout(r, 50));

        expect(Logger.error).toHaveBeenCalled();
    });
});

/* ────────────────────────────────────────────
 *  initCarousel() — navigation & auto-scroll
 * ──────────────────────────────────────────── */
describe('avis-carousel — initCarousel()', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
        globalThis.Logger = { error: vi.fn(), log: vi.fn(), warn: vi.fn() };
        globalThis.escapeHtml = vi.fn(s => s);

        document.body.innerHTML = `
            <section class="avis-clients">
                <div class="avis-clients__list">
                    <div class="avis-clients__track">
                        <div class="avis-clients__item">A</div>
                        <div class="avis-clients__item">B</div>
                    </div>
                </div>
                <button class="avis-clients__arrow--prev"></button>
                <button class="avis-clients__arrow--next"></button>
            </section>
        `;
        // Désactiver l'auto-scroll par défaut pour isoler les tests
        window.matchMedia = vi.fn(() => ({ matches: true }));
        loadScript(SRC);
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.useRealTimers();
    });

    it('scrolle à droite au clic sur la flèche next', () => {
        const list = document.querySelector('.avis-clients__list');
        list.scrollBy = vi.fn();

        initCarousel();
        document.querySelector('.avis-clients__arrow--next').click();

        expect(list.scrollBy).toHaveBeenCalledWith({ left: 300, behavior: 'smooth' });
    });

    it('scrolle à gauche au clic sur la flèche prev', () => {
        const list = document.querySelector('.avis-clients__list');
        list.scrollBy = vi.fn();

        initCarousel();
        document.querySelector('.avis-clients__arrow--prev').click();

        expect(list.scrollBy).toHaveBeenCalledWith({ left: -300, behavior: 'smooth' });
    });

    it('ne lance pas l\'auto-scroll si prefers-reduced-motion est actif', () => {
        window.matchMedia = vi.fn(() => ({ matches: true }));
        vi.useFakeTimers();

        initCarousel();

        // setInterval ne doit PAS avoir été appelé
        expect(vi.getTimerCount()).toBe(0);
        vi.useRealTimers();
    });

    it('lance l\'auto-scroll toutes les 4 s sans reduced-motion', () => {
        window.matchMedia = vi.fn(() => ({ matches: false }));
        vi.useFakeTimers();

        initCarousel();

        expect(vi.getTimerCount()).toBeGreaterThanOrEqual(1);
        vi.useRealTimers();
    });

    it('ne plante pas sans les éléments DOM requis', () => {
        document.body.innerHTML = '';
        expect(() => initCarousel()).not.toThrow();
    });
});
