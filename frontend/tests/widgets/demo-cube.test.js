/**
 * Tests — js/widgets/demo-cube.js
 * Cube 3D split : rotation cumulative 4 faces, navigation cyclique
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { loadScript } from '../helpers/load-script.js';

const SRC = 'js/widgets/demo-cube.js';

describe('demo-cube.js', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
        global.ResizeObserver = class {
            observe() {}
            unobserve() {}
            disconnect() {}
        };
        document.body.innerHTML = `
            <div class="presentation__content">
                <div class="presentation__arrows">
                    <button id="demo-cube-btn-back"><span>↑</span></button>
                    <button id="demo-cube-btn"><span>↓</span></button>
                    <div class="presentation__indicators">
                        <span class="presentation__dot is-active" data-index="0"></span>
                        <span class="presentation__dot" data-index="1"></span>
                        <span class="presentation__dot" data-index="2"></span>
                        <span class="presentation__dot" data-index="3"></span>
                    </div>
                </div>
                <div class="cube cube--split cube--left">
                    <div class="cube__face cube__face--1"></div>
                    <div class="cube__face cube__face--2"></div>
                    <div class="cube__face cube__face--3"></div>
                    <div class="cube__face cube__face--4"></div>
                </div>
                <div class="cube cube--split cube--right">
                    <div class="cube__face cube__face--1"></div>
                    <div class="cube__face cube__face--2"></div>
                    <div class="cube__face cube__face--3"></div>
                    <div class="cube__face cube__face--4"></div>
                </div>
            </div>
        `;
        loadScript(SRC);
        document.dispatchEvent(new Event('DOMContentLoaded'));
    });

    afterEach(() => {
        document.body.innerHTML = '';
        delete global.ResizeObserver;
    });

    it('tourne le cube gauche de -90° au clic ↓ (cumulatif)', () => {
        document.getElementById('demo-cube-btn').click();
        const cubeLeft = document.querySelector('.cube--left');
        expect(cubeLeft.style.transform).toBe('rotateX(-90deg)');
    });

    it('tourne le cube droit de +90° au clic ↓ (sens opposé)', () => {
        document.getElementById('demo-cube-btn').click();
        const cubeRight = document.querySelector('.cube--right');
        expect(cubeRight.style.transform).toBe('rotateX(90deg)');
    });

    it('accumule les angles : 2 clics ↓ = -180°', async () => {
        const btn = document.getElementById('demo-cube-btn');
        btn.click();
        await new Promise(r => setTimeout(r, 1200));
        btn.click();
        const cubeLeft = document.querySelector('.cube--left');
        expect(cubeLeft.style.transform).toBe('rotateX(-180deg)');
    });

    it('revient de -90° à 0° au clic ↑ après un clic ↓', async () => {
        const btnDown = document.getElementById('demo-cube-btn');
        const btnUp = document.getElementById('demo-cube-btn-back');
        btnDown.click();
        await new Promise(r => setTimeout(r, 1200));
        btnUp.click();
        const cubeLeft = document.querySelector('.cube--left');
        expect(cubeLeft.style.transform).toBe('rotateX(0deg)');
    });

    it('boucle face 4→1 avec un seul -90° (pas de saut 270°)', async () => {
        const btn = document.getElementById('demo-cube-btn');
        for (let i = 0; i < 4; i++) {
            btn.click();
            await new Promise(r => setTimeout(r, 1200));
        }
        const cubeLeft = document.querySelector('.cube--left');
        // 4 clics × -90° = -360° (pas retour à 0°)
        expect(cubeLeft.style.transform).toBe('rotateX(-360deg)');
    });

    it('met à jour le dot actif', () => {
        document.getElementById('demo-cube-btn').click();
        const dots = document.querySelectorAll('.presentation__dot');
        expect(dots[0].classList.contains('is-active')).toBe(false);
        expect(dots[1].classList.contains('is-active')).toBe(true);
    });

    it('ignore les clics pendant l\'animation (anti-spam)', () => {
        const btn = document.getElementById('demo-cube-btn');
        btn.click();
        btn.click(); // ignoré
        const cubeLeft = document.querySelector('.cube--left');
        expect(cubeLeft.style.transform).toBe('rotateX(-90deg)');
    });

    it('met à jour is-active-face pour le mobile', () => {
        document.getElementById('demo-cube-btn').click();
        const faces = document.querySelectorAll('.cube--left .cube__face');
        expect(faces[0].classList.contains('is-active-face')).toBe(false);
        expect(faces[1].classList.contains('is-active-face')).toBe(true);
    });

    it('supprime le hint bounce au premier clic', () => {
        const btn = document.getElementById('demo-cube-btn');
        btn.classList.add('arrow-btn--hint');
        btn.click();
        expect(btn.classList.contains('arrow-btn--hint')).toBe(false);
    });

    it('ajoute is-rotating sur le container pendant l\'animation', () => {
        document.getElementById('demo-cube-btn').click();
        const container = document.querySelector('.presentation__content');
        expect(container.classList.contains('is-rotating')).toBe(true);
    });
});
