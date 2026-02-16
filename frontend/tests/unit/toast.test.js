import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';

/**
 * Tests unitaires — toast.js
 * Couvre : showToast, dismissToast, getToastContainer, constantes
 */
describe('toast.js', () => {

    beforeEach(() => {
        document.body.innerHTML = '';
        loadScript('js/utils/toast.js');
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    // ─── getToastContainer ───────────────────────────────────────────

    describe('getToastContainer()', () => {
        it('crée un conteneur .toast-container si absent', () => {
            const container = getToastContainer();
            expect(container).not.toBeNull();
            expect(container.classList.contains('toast-container')).toBe(true);
            expect(document.querySelector('.toast-container')).toBe(container);
        });

        it('réutilise le conteneur existant', () => {
            const first = getToastContainer();
            const second = getToastContainer();
            expect(first).toBe(second);
            expect(document.querySelectorAll('.toast-container').length).toBe(1);
        });
    });

    // ─── showToast ───────────────────────────────────────────────────

    describe('showToast()', () => {
        it('crée un élément toast dans le conteneur', () => {
            showToast('Test message');
            const toast = document.querySelector('.toast');
            expect(toast).not.toBeNull();
        });

        it('affiche le message dans .toast__message', () => {
            showToast('Hello world');
            const msg = document.querySelector('.toast__message');
            expect(msg.textContent).toBe('Hello world');
        });

        it('utilise le type info par défaut', () => {
            showToast('Info');
            const toast = document.querySelector('.toast');
            expect(toast.classList.contains('toast--info')).toBe(true);
        });

        it('applique la classe de type success', () => {
            showToast('Succès', 'success');
            const toast = document.querySelector('.toast');
            expect(toast.classList.contains('toast--success')).toBe(true);
        });

        it('applique la classe de type error', () => {
            showToast('Erreur', 'error');
            expect(document.querySelector('.toast--error')).not.toBeNull();
        });

        it('applique la classe de type warning', () => {
            showToast('Attention', 'warning');
            expect(document.querySelector('.toast--warning')).not.toBeNull();
        });

        it('affiche l\'icône correspondante au type', () => {
            showToast('OK', 'success');
            const icon = document.querySelector('.toast__icon');
            expect(icon.textContent).toBe('✓');
        });

        it('affiche l\'icône erreur ✕', () => {
            showToast('Erreur', 'error');
            const icon = document.querySelector('.toast__icon');
            expect(icon.textContent).toBe('✕');
        });

        it('inclut un bouton de fermeture avec aria-label', () => {
            showToast('Test');
            const closeBtn = document.querySelector('.toast__close');
            expect(closeBtn).not.toBeNull();
            expect(closeBtn.getAttribute('aria-label')).toBe('Fermer');
        });

        it('ferme le toast au clic sur le bouton close', () => {
            showToast('Test', 'info', 0); // pas d'auto-close
            const closeBtn = document.querySelector('.toast__close');
            closeBtn.click();

            const toast = document.querySelector('.toast');
            expect(toast.classList.contains('is-leaving')).toBe(true);
        });

        it('programme un auto-close avec setTimeout', () => {
            const spy = vi.spyOn(globalThis, 'setTimeout');
            showToast('Auto', 'info', 3000);

            // Au moins un setTimeout pour l'auto-dismiss
            const timeoutCalls = spy.mock.calls.filter(c => c[1] === 3000);
            expect(timeoutCalls.length).toBeGreaterThanOrEqual(1);
        });

        it('ne programme pas d\'auto-close si duration=0', () => {
            const spy = vi.spyOn(globalThis, 'setTimeout');
            showToast('Permanent', 'info', 0);

            // Aucun setTimeout avec le pattern auto-close
            const timeoutCalls = spy.mock.calls.filter(c => c[1] === 0);
            expect(timeoutCalls.length).toBe(0);
        });
    });

    // ─── dismissToast ────────────────────────────────────────────────

    describe('dismissToast()', () => {
        it('ajoute la classe is-leaving au toast', () => {
            showToast('Test', 'info', 0);
            const toast = document.querySelector('.toast');
            dismissToast(toast);
            expect(toast.classList.contains('is-leaving')).toBe(true);
        });

        it('ne fait rien si déjà en cours de fermeture', () => {
            showToast('Test', 'info', 0);
            const toast = document.querySelector('.toast');
            toast.classList.add('is-leaving');
            // Appeler une seconde fois ne devrait pas crasher
            dismissToast(toast);
            expect(toast.classList.contains('is-leaving')).toBe(true);
        });
    });
});
