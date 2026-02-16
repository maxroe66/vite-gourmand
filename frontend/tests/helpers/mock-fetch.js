import { vi } from 'vitest';

/**
 * Crée un mock de `fetch` qui retourne une réponse configurée.
 * @param {number} status - Code HTTP de la réponse
 * @param {Object} data - Corps JSON de la réponse
 * @param {boolean} [ok] - Valeur de response.ok (déduite du status si non fournie)
 * @returns {import('vitest').Mock} Le mock `fetch` installé sur globalThis
 */
export function mockFetch(status, data, ok) {
    const isOk = ok !== undefined ? ok : (status >= 200 && status < 300);
    const mockFn = vi.fn().mockResolvedValue({
        ok: isOk,
        status,
        json: vi.fn().mockResolvedValue(data),
        text: vi.fn().mockResolvedValue(typeof data === 'string' ? data : JSON.stringify(data))
    });
    globalThis.fetch = mockFn;
    return mockFn;
}

/**
 * Crée un mock de `fetch` qui rejette avec une erreur réseau.
 * @param {string} [message='Network error'] - Message d'erreur
 * @returns {import('vitest').Mock}
 */
export function mockFetchError(message = 'Network error') {
    const mockFn = vi.fn().mockRejectedValue(new Error(message));
    globalThis.fetch = mockFn;
    return mockFn;
}

/**
 * Crée un mock de `fetch` qui retourne des réponses différentes pour chaque appel.
 * @param {Array<{status: number, data: Object}>} responses - Liste de réponses séquentielles
 * @returns {import('vitest').Mock}
 */
export function mockFetchSequence(responses) {
    const mockFn = vi.fn();
    responses.forEach(({ status, data }, index) => {
        const isOk = status >= 200 && status < 300;
        mockFn.mockResolvedValueOnce({
            ok: isOk,
            status,
            json: vi.fn().mockResolvedValue(data),
            text: vi.fn().mockResolvedValue(typeof data === 'string' ? data : JSON.stringify(data))
        });
    });
    globalThis.fetch = mockFn;
    return mockFn;
}
