/**
 * Utilitaires partagés — Vite & Gourmand
 * Chargé avant les scripts de page dans chaque HTML.
 */

/**
 * Échappe les caractères HTML dangereux pour prévenir les XSS.
 * Utilise createTextNode — méthode infaillible couvrant tous les caractères.
 * @param {string} str - Chaîne à échapper
 * @returns {string} Chaîne sécurisée
 */
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

/**
 * Formate un nombre en prix EUR (ex: "12,50 €").
 * @param {number} price
 * @returns {string}
 */
function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}
