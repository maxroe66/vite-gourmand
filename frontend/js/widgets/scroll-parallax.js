/**
 * Scroll Parallax — Effet de profondeur subtil sur les backgrounds.
 *
 * Écoute le scroll de <main> (piloté par scroll-snap-controller)
 * et applique un translateY parallax sur les pseudo-éléments ::before
 * des sections hero et menus.
 *
 * Actif uniquement desktop (>1024px) et si prefers-reduced-motion n'est pas activé.
 */
document.addEventListener('DOMContentLoaded', () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (window.innerWidth <= 1024) return;

    const mainEl = document.querySelector('main');
    if (!mainEl) return;

    // Sections avec background image à parallaxer
    const hero = document.querySelector('.hero');
    const menus = document.querySelector('.menus');

    if (!hero && !menus) return;

    // Ratio parallax : mouvement = scroll * RATIO (subtil)
    const RATIO = 0.12;

    /**
     * Applique le décalage parallax via CSS custom property.
     * Le CSS utilise var(--parallax-y) dans un transform sur ::before.
     */
    function updateParallax() {
        const scrollTop = mainEl.scrollTop;
        const viewportH = mainEl.clientHeight;

        if (hero) {
            const rect = hero.getBoundingClientRect();
            // Quand la section est visible, calculer le décalage
            const offset = (rect.top / viewportH) * -RATIO * 100;
            hero.style.setProperty('--parallax-y', `${offset.toFixed(2)}px`);
        }

        if (menus) {
            const rect = menus.getBoundingClientRect();
            const offset = (rect.top / viewportH) * -RATIO * 100;
            menus.style.setProperty('--parallax-y', `${offset.toFixed(2)}px`);
        }
    }

    // Écouter le scroll de main (pas window — le scroll est hijacké)
    mainEl.addEventListener('scroll', () => {
        requestAnimationFrame(updateParallax);
    }, { passive: true });

    // Position initiale
    updateParallax();

    // Désactiver au resize si on passe en mobile
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (window.innerWidth <= 1024) {
                if (hero) hero.style.removeProperty('--parallax-y');
                if (menus) menus.style.removeProperty('--parallax-y');
            }
        }, 200);
    });
});
