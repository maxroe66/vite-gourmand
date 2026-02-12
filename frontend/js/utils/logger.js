/**
 * Logger conditionnel — Vite & Gourmand
 * Affiche les logs uniquement en environnement de développement (localhost).
 * En production, les logs sont silencieux pour ne pas exposer de détails techniques.
 *
 * Usage : Logger.error('message', data), Logger.warn(...), Logger.log(...)
 */
const Logger = {
    /** @type {boolean} true si on est en environnement de développement */
    isDev: ['localhost', '127.0.0.1'].includes(window.location.hostname),

    /**
     * @param {...any} args
     */
    error(...args) {
        if (this.isDev) console.error(...args);
    },

    /**
     * @param {...any} args
     */
    warn(...args) {
        if (this.isDev) console.warn(...args);
    },

    /**
     * @param {...any} args
     */
    log(...args) {
        if (this.isDev) console.log(...args);
    }
};
