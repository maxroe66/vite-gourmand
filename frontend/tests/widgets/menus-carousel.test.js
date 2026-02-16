/**
 * Tests — js/widgets/menus-carousel.js
 * Navigation par flèches, updateDisabled, refreshMenuCarousel, robustesse
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { loadScript } from '../helpers/load-script.js';

const SRC = 'js/widgets/menus-carousel.js';

describe('menus-carousel.js', () => {
    function setupDOM() {
        document.body.innerHTML = `
            <section class="menus">
                <div class="menus__list">
                    <div class="menu-card">Menu 1</div>
                    <div class="menu-card">Menu 2</div>
                    <div class="menu-card">Menu 3</div>
                </div>
                <button class="menus__arrow--prev"></button>
                <button class="menus__arrow--next"></button>
            </section>
        `;
    }

    beforeEach(() => {
        vi.restoreAllMocks();
        document.body.innerHTML = '';
        window.matchMedia = vi.fn(() => ({ matches: false }));
        globalThis.refreshMenuCarousel = undefined;
    });

    afterEach(() => {
        document.body.innerHTML = '';
        globalThis.refreshMenuCarousel = undefined;
    });

    it('scrolle vers la droite au clic sur next', () => {
        setupDOM();
        loadScript(SRC);
        document.dispatchEvent(new Event('DOMContentLoaded'));

        // Mock APRÈS init pour garder les mêmes références DOM
        const list = document.querySelector('.menus__list');
        list.scrollBy = vi.fn();
        document.querySelectorAll('.menu-card').forEach(card => {
            card.getBoundingClientRect = vi.fn(() => ({
                width: 300, height: 200, x: 0, y: 0, top: 0, right: 300, bottom: 200, left: 0
            }));
        });

        // updateDisabled() met disabled=true en jsdom (scroll=0), on réactive
        const nextBtn = document.querySelector('.menus__arrow--next');
        nextBtn.disabled = false;
        nextBtn.click();

        expect(list.scrollBy).toHaveBeenCalled();
        const lastCall = list.scrollBy.mock.calls[list.scrollBy.mock.calls.length - 1][0];
        expect(lastCall.left).toBe(300);
        expect(lastCall.behavior).toBe('smooth');
    });

    it('scrolle vers la gauche au clic sur prev', () => {
        setupDOM();
        loadScript(SRC);
        document.dispatchEvent(new Event('DOMContentLoaded'));

        const list = document.querySelector('.menus__list');
        list.scrollBy = vi.fn();
        document.querySelectorAll('.menu-card').forEach(card => {
            card.getBoundingClientRect = vi.fn(() => ({
                width: 250, height: 200, x: 0, y: 0, top: 0, right: 250, bottom: 200, left: 0
            }));
        });

        const prevBtn = document.querySelector('.menus__arrow--prev');
        prevBtn.disabled = false;
        prevBtn.click();

        expect(list.scrollBy).toHaveBeenCalled();
        const lastCall = list.scrollBy.mock.calls[list.scrollBy.mock.calls.length - 1][0];
        expect(lastCall.left).toBe(-250);
    });

    it('expose window.refreshMenuCarousel après initialisation', () => {
        setupDOM();
        loadScript(SRC);
        document.dispatchEvent(new Event('DOMContentLoaded'));

        expect(typeof window.refreshMenuCarousel).toBe('function');
    });

    it('désactive les flèches aux extrémités du scroll', () => {
        setupDOM();
        loadScript(SRC);
        document.dispatchEvent(new Event('DOMContentLoaded'));

        const prev = document.querySelector('.menus__arrow--prev');
        const next = document.querySelector('.menus__arrow--next');

        // En jsdom, scrollLeft=0, scrollWidth=clientWidth=0 → début ET fin
        expect(prev.disabled).toBe(true);
        expect(next.disabled).toBe(true);
    });

    it('ne plante pas si la section .menus est absente', () => {
        document.body.innerHTML = '<div>Autre contenu</div>';
        loadScript(SRC);

        expect(() => {
            document.dispatchEvent(new Event('DOMContentLoaded'));
        }).not.toThrow();
    });

    it('ne plante pas si les flèches sont absentes', () => {
        document.body.innerHTML = `
            <section class="menus">
                <div class="menus__list">
                    <div class="menu-card">Menu 1</div>
                </div>
            </section>
        `;
        loadScript(SRC);

        expect(() => {
            document.dispatchEvent(new Event('DOMContentLoaded'));
        }).not.toThrow();
    });
});
