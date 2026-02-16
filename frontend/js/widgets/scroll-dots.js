/**
 * Scroll Dots — Indicateur de navigation scroll-snap (homepage).
 * Crée des points de navigation fixes à droite qui suivent la section active.
 * Utilise IntersectionObserver sur le conteneur <main> (scroll-snap container).
 */
document.addEventListener('DOMContentLoaded', () => {
    // Ne pas injecter sur mobile (scroll-snap désactivé sous 1024px)
    if (window.innerWidth <= 1024) return;

    // Respecter prefers-reduced-motion
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Sections cibles (doivent correspondre aux enfants snap du <main>)
    const sections = [
        { selector: '.hero',         label: 'Accueil' },
        { selector: '.presentation', label: 'Présentation' },
        { selector: '.menus',        label: 'Menus' },
        { selector: '.footer-section', label: 'Contact' }
    ];

    const mainEl = document.querySelector('main');
    if (!mainEl) return;

    // Vérifier que les sections existent
    const validSections = sections
        .map(s => ({ ...s, el: document.querySelector(s.selector) }))
        .filter(s => s.el);

    if (validSections.length < 2) return;

    // Créer le conteneur de dots
    const nav = document.createElement('nav');
    nav.className = 'scroll-dots';
    nav.setAttribute('role', 'navigation');
    nav.setAttribute('aria-label', 'Navigation entre les sections');

    validSections.forEach((section, index) => {
        const dot = document.createElement('button');
        dot.className = 'scroll-dots__dot';
        dot.setAttribute('data-label', section.label);
        dot.setAttribute('aria-label', `Aller à ${section.label}`);
        dot.setAttribute('type', 'button');

        dot.addEventListener('click', () => {
            section.el.scrollIntoView({
                behavior: prefersReduced ? 'auto' : 'smooth',
                block: 'start'
            });
        });

        if (index === 0) dot.classList.add('is-active');
        section.dot = dot;
        nav.appendChild(dot);
    });

    document.body.appendChild(nav);

    // Observer chaque section pour détecter laquelle est active
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Trouver l'index correspondant
                const match = validSections.find(s => s.el === entry.target);
                if (match) {
                    validSections.forEach(s => s.dot.classList.remove('is-active'));
                    match.dot.classList.add('is-active');
                }
            }
        });
    }, {
        root: mainEl, // Le conteneur de scroll (scroll-snap container)
        threshold: 0.5
    });

    validSections.forEach(s => observer.observe(s.el));

    // Masquer/afficher au resize (scroll-snap désactivé sous 1024px)
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            nav.style.display = window.innerWidth <= 1024 ? 'none' : '';
        }, 200);
    });
});
