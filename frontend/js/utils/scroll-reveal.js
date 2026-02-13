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

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target); // Une seule fois
            }
        });
    }, {
        threshold: 0.15
    });

    reveals.forEach(el => observer.observe(el));
});
