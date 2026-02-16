import { vi } from 'vitest';

/**
 * Configure les stubs globaux utilisés par la majorité des scripts du projet.
 * Appeler dans `beforeEach()` de chaque fichier de test.
 *
 * @param {Object} [overrides] - Surcharges optionnelles pour personnaliser les stubs
 * @param {Object} [overrides.AuthService] - Surcharger des méthodes d'AuthService
 * @param {Object} [overrides.Logger] - Surcharger des méthodes de Logger
 * @returns {{ Logger: Object, AuthService: Object }} Les stubs installés
 */
export function setupGlobals(overrides = {}) {
    // Logger (silencieux par défaut)
    const Logger = {
        error: vi.fn(),
        warn: vi.fn(),
        log: vi.fn(),
        isDev: true,
        ...overrides.Logger
    };
    globalThis.Logger = Logger;

    // AuthService (stub minimal)
    const AuthService = {
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
        resetPassword: vi.fn(),
        isAuthenticated: vi.fn().mockResolvedValue(null),
        getUser: vi.fn().mockResolvedValue(null),
        getCsrfToken: vi.fn().mockReturnValue('fake-csrf-token'),
        addCsrfHeader: vi.fn((headers = {}) => ({
            ...headers,
            'X-CSRF-Token': 'fake-csrf-token'
        })),
        getFetchOptions: vi.fn((options = {}) => ({
            ...options,
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': 'fake-csrf-token',
                ...(options.headers || {})
            }
        })),
        ...overrides.AuthService
    };
    globalThis.AuthService = AuthService;

    // Fonctions utilitaires souvent présentes en global
    globalThis.initPasswordToggles = overrides.initPasswordToggles || vi.fn();
    globalThis.escapeHtml = overrides.escapeHtml || ((str) => {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, (m) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[m]));
    });
    globalThis.formatPrice = overrides.formatPrice || ((price) =>
        new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price)
    );
    globalThis.showToast = overrides.showToast || vi.fn();

    return { Logger, AuthService };
}
