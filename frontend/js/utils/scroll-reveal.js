/**
 * Scroll Reveal — Animations d'apparition au scroll.
 * Utilise IntersectionObserver pour ajouter .is-visible
 * quand les éléments .reveal entrent dans le viewport.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Respecter prefers-reduced-motion (le CSS gère le fallback, mais on évite le JS inutile)
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const reveals = document.querySelectorAll('.reveal');
    if (!reveals.length) return;

    // Sur la homepage, le scroll se fait dans <main> (overflow-y: hidden, piloté par JS).
    // L'IntersectionObserver doit utiliser <main> comme root pour détecter la visibilité.
    // Sur les autres pages, on garde le viewport document (root: null) par défaut.
    const mainEl = document.querySelector('main');
    const isScrollHijacked = mainEl &&
        getComputedStyle(mainEl).overflowY === 'hidden' &&
        mainEl.scrollHeight > mainEl.clientHeight;
    const observerRoot = isScrollHijacked ? mainEl : null;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target); // Une seule fois
            }
        });
    }, {
        root: observerRoot,
        threshold: 0.15
    });

    reveals.forEach(el => observer.observe(el));

    // Activer le shimmer sur le CTA hero après le fadeIn initial
    const heroCta = document.querySelector('.hero__content .button');
    if (heroCta) {
        setTimeout(() => {
            heroCta.classList.add('is-shimmer');
        }, 1600); // Après la fin du heroFadeIn (0.8s delay + 0.8s animation)
    }
});
