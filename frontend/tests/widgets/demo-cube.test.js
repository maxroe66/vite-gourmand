/**
 * Tests — js/widgets/demo-cube.js
 * Animation Rubik's Cube 3D : flip / unflip, protection double clic
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { loadScript } from '../helpers/load-script.js';

const SRC = 'js/widgets/demo-cube.js';

describe('demo-cube.js', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
        document.body.innerHTML = `
            <div class="presentation__content">
                <div class="cube--split">Cube 1</div>
                <div class="cube--split">Cube 2</div>
            </div>
            <button id="demo-cube-btn">Découvrir</button>
            <button id="demo-cube-btn-back">Retour</button>
        `;
        loadScript(SRC);
        document.dispatchEvent(new Event('DOMContentLoaded'));
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('ajoute is-flipped aux cubes au clic sur btnDown', () => {
        document.getElementById('demo-cube-btn').click();

        document.querySelectorAll('.cube--split').forEach(cube => {
            expect(cube.classList.contains('is-flipped')).toBe(true);
        });
    });

    it('cache btnDown et active btnUp au flip', () => {
        const btnDown = document.getElementById('demo-cube-btn');
        const btnUp = document.getElementById('demo-cube-btn-back');

        btnDown.click();

        expect(btnDown.classList.contains('is-hidden')).toBe(true);
        expect(btnUp.classList.contains('is-active')).toBe(true);
    });

    it('retire is-flipped au clic sur btnUp (unflip)', () => {
        const btnDown = document.getElementById('demo-cube-btn');
        const btnUp = document.getElementById('demo-cube-btn-back');

        btnDown.click();  // flip
        btnUp.click();    // unflip

        document.querySelectorAll('.cube--split').forEach(cube => {
            expect(cube.classList.contains('is-flipped')).toBe(false);
        });
    });

    it('restaure les boutons à leur état initial après unflip', () => {
        const btnDown = document.getElementById('demo-cube-btn');
        const btnUp = document.getElementById('demo-cube-btn-back');

        btnDown.click();
        btnUp.click();

        expect(btnDown.classList.contains('is-hidden')).toBe(false);
        expect(btnUp.classList.contains('is-hidden')).toBe(true);
    });

    it('ignore un second clic sur btnDown si déjà flippé', () => {
        const btnDown = document.getElementById('demo-cube-btn');

        btnDown.click(); // flip
        btnDown.click(); // ignoré

        // Toujours flippé (aucune régression)
        document.querySelectorAll('.cube--split').forEach(cube => {
            expect(cube.classList.contains('is-flipped')).toBe(true);
        });
    });
});
