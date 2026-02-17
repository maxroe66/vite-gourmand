/**
 * Scroll Progress Bar — Barre de progression horizontale entre sections.
 *
 * Fine barre sous la navbar qui montre la progression dans la page.
 * S'intègre au scroll-snap-controller (écoute le scroll de <main>).
 *
 * Actif uniquement desktop (>1024px) et si prefers-reduced-motion n'est pas activé.
 */
document.addEventListener('DOMContentLoaded', () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const mainEl = document.querySelector('main');
    if (!mainEl) return;

    // Créer la barre de progression
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    progressBar.setAttribute('role', 'progressbar');
    progressBar.setAttribute('aria-label', 'Progression de la page');
    progressBar.setAttribute('aria-valuemin', '0');
    progressBar.setAttribute('aria-valuemax', '100');
    document.body.appendChild(progressBar);

    /**
     * Met à jour la largeur de la barre selon le scroll de main.
     */
    function updateProgress() {
        const scrollTop = mainEl.scrollTop;
        const maxScroll = mainEl.scrollHeight - mainEl.clientHeight;
        if (maxScroll <= 0) return;

        const progress = Math.min((scrollTop / maxScroll) * 100, 100);
        progressBar.style.width = `${progress}%`;
        progressBar.setAttribute('aria-valuenow', Math.round(progress).toString());
    }

    mainEl.addEventListener('scroll', () => {
        requestAnimationFrame(updateProgress);
    }, { passive: true });

    // Position initiale
    updateProgress();

    // Masquer/afficher selon le breakpoint
    function toggleVisibility() {
        progressBar.style.display = window.innerWidth > 1024 ? '' : 'none';
    }

    toggleVisibility();
    window.addEventListener('resize', () => {
        requestAnimationFrame(toggleVisibility);
    });
});
