import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';

/**
 * Tests unitaires — logger.js (Logger conditionnel)
 * Vérifie que les logs sont affichés uniquement en environnement de développement.
 */
describe('logger.js', () => {

    let consoleErrorSpy;
    let consoleWarnSpy;
    let consoleLogSpy;

    beforeEach(() => {
        // Espions sur console
        consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
        consoleWarnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
        consoleLogSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
        globalThis.Logger = undefined;
    });

    /**
     * Charge le module Logger après avoir configuré le hostname.
     * @param {string} hostname - Le hostname à simuler
     * @returns {Object} Le Logger chargé
     */
    function loadLogger(hostname) {
        // jsdom par défaut utilise 'localhost', on le change si besoin
        Object.defineProperty(window, 'location', {
            value: { ...window.location, hostname },
            writable: true,
            configurable: true
        });
        globalThis.Logger = undefined;
        loadScript('js/utils/logger.js');
        return globalThis.Logger;
    }

    // ─── isDev ───────────────────────────────────────────────────────

    describe('isDev', () => {
        it('est true sur localhost', () => {
            const Logger = loadLogger('localhost');
            expect(Logger.isDev).toBe(true);
        });

        it('est true sur 127.0.0.1', () => {
            const Logger = loadLogger('127.0.0.1');
            expect(Logger.isDev).toBe(true);
        });

        it('est false sur un domaine de production', () => {
            const Logger = loadLogger('vite-gourmand.azurewebsites.net');
            expect(Logger.isDev).toBe(false);
        });
    });

    // ─── error() ─────────────────────────────────────────────────────

    describe('error()', () => {
        it('appelle console.error en dev', () => {
            const Logger = loadLogger('localhost');
            Logger.error('test error', { detail: 42 });
            expect(consoleErrorSpy).toHaveBeenCalledWith('test error', { detail: 42 });
        });

        it('ne log rien en production', () => {
            const Logger = loadLogger('vite-gourmand.com');
            Logger.error('secret error');
            expect(consoleErrorSpy).not.toHaveBeenCalled();
        });
    });

    // ─── warn() ──────────────────────────────────────────────────────

    describe('warn()', () => {
        it('appelle console.warn en dev', () => {
            const Logger = loadLogger('localhost');
            Logger.warn('attention');
            expect(consoleWarnSpy).toHaveBeenCalledWith('attention');
        });

        it('ne log rien en production', () => {
            const Logger = loadLogger('vite-gourmand.com');
            Logger.warn('warning prod');
            expect(consoleWarnSpy).not.toHaveBeenCalled();
        });
    });

    // ─── log() ───────────────────────────────────────────────────────

    describe('log()', () => {
        it('appelle console.log en dev', () => {
            const Logger = loadLogger('127.0.0.1');
            Logger.log('debug info');
            expect(consoleLogSpy).toHaveBeenCalledWith('debug info');
        });

        it('ne log rien en production', () => {
            const Logger = loadLogger('example.com');
            Logger.log('debug prod');
            expect(consoleLogSpy).not.toHaveBeenCalled();
        });
    });
});
