/**
 * Système de toast/notification non-bloquant.
 * Remplace les alert() natifs par des notifications stylées.
 *
 * Usage :
 *   showToast('Message de succès', 'success');
 *   showToast('Une erreur est survenue', 'error');
 *   showToast('Information utile', 'info');
 *   showToast('Attention', 'warning');
 */

const TOAST_ICONS = {
    success: '✓',
    error: '✕',
    info: 'ℹ',
    warning: '⚠'
};

const TOAST_DURATION = 4000;

/**
 * Crée (ou récupère) le conteneur de toasts.
 * @returns {HTMLElement}
 */
function getToastContainer() {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.classList.add('toast-container');
        document.body.appendChild(container);
    }
    return container;
}

/**
 * Affiche un toast de notification.
 * @param {string} message — Texte à afficher (doit être déjà échappé si dynamique)
 * @param {'success'|'error'|'info'|'warning'} [type='info'] — Type de notification
 * @param {number} [duration=4000] — Durée d'affichage en ms (0 = pas d'auto-close)
 */
function showToast(message, type = 'info', duration = TOAST_DURATION) {
    const container = getToastContainer();

    const toast = document.createElement('div');
    toast.classList.add('toast', `toast--${type}`);

    const icon = document.createElement('span');
    icon.classList.add('toast__icon');
    icon.textContent = TOAST_ICONS[type] || TOAST_ICONS.info;

    const msg = document.createElement('span');
    msg.classList.add('toast__message');
    msg.textContent = message;

    const closeBtn = document.createElement('button');
    closeBtn.classList.add('toast__close');
    closeBtn.setAttribute('aria-label', 'Fermer');
    closeBtn.textContent = '×';
    closeBtn.addEventListener('click', () => dismissToast(toast));

    toast.appendChild(icon);
    toast.appendChild(msg);
    toast.appendChild(closeBtn);
    container.appendChild(toast);

    if (duration > 0) {
        setTimeout(() => dismissToast(toast), duration);
    }
}

/**
 * Ferme un toast avec animation.
 * @param {HTMLElement} toast
 */
function dismissToast(toast) {
    if (toast.classList.contains('is-leaving')) return;
    toast.classList.add('is-leaving');
    toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    // Fallback si transitionend ne se déclenche pas
    setTimeout(() => toast.remove(), 400);
}
