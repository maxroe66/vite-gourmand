/**
 * Counter Animate — Animation de comptage sur les chiffres clés.
 *
 * Anime les éléments avec l'attribut [data-count-target]
 * quand ils deviennent visibles (IntersectionObserver).
 * Le compteur monte de 0 à la valeur cible avec un easing doux.
 *
 * Usage HTML :
 *   <span data-count-target="148" data-count-suffix=" avis">0 avis</span>
 *   <span data-count-target="4.8" data-count-decimals="1">0</span>
 */
document.addEventListener('DOMContentLoaded', () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const counters = document.querySelectorAll('[data-count-target]');
    if (!counters.length) return;

    // Root : <main> sur la homepage (scroll hijacké), sinon viewport
    const mainEl = document.querySelector('main');
    const isScrollHijacked = mainEl &&
        getComputedStyle(mainEl).overflowY !== 'visible' &&
        mainEl.scrollHeight > mainEl.clientHeight;
    const observerRoot = isScrollHijacked ? mainEl : null;

    /**
     * Easing deceleration (ease-out cubic).
     * @param {number} t - Progression [0, 1]
     * @returns {number} Valeur easée
     */
    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    /**
     * Anime un compteur de 0 à la valeur cible.
     * @param {HTMLElement} el - Élément DOM contenant le compteur
     */
    function animateCounter(el) {
        const target = parseFloat(el.dataset.countTarget);
        const decimals = parseInt(el.dataset.countDecimals || '0', 10);
        const prefix = el.dataset.countPrefix || '';
        const suffix = el.dataset.countSuffix || '';
        const duration = 1200; // ms
        const startTime = performance.now();

        function step(now) {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const easedProgress = easeOutCubic(progress);
            const current = target * easedProgress;

            el.textContent = prefix + current.toFixed(decimals) + suffix;

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                // Valeur finale exacte
                el.textContent = prefix + target.toFixed(decimals) + suffix;
            }
        }

        requestAnimationFrame(step);
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, {
        root: observerRoot,
        threshold: 0.5
    });

    counters.forEach(el => observer.observe(el));
});
