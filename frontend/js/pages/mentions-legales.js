/**
 * Mentions Légales — Vite & Gourmand
 * Gère la table des matières interactive (TOC) avec :
 * - Indicateur de section active via IntersectionObserver
 * - Smooth scroll sur clic des liens TOC
 * - Injection dynamique de la date de dernière mise à jour
 */
document.addEventListener('DOMContentLoaded', () => {

    // ── Injection de la date de dernière mise à jour ──
    const updateDateEl = document.getElementById('legalUpdateDate');
    if (updateDateEl) {
        const now = new Date();
        updateDateEl.textContent = now.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    // ── TOC : smooth scroll au clic ──
    const tocLinks = document.querySelectorAll('.legal-toc__link');
    tocLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = link.getAttribute('href')?.substring(1);
            if (!targetId) return;
            const targetEl = document.getElementById(targetId);
            if (!targetEl) return;

            // Offset pour la navbar + TOC sticky
            const navbarHeight = parseInt(
                getComputedStyle(document.documentElement)
                    .getPropertyValue('--navbar-height') || '80', 10
            );
            const tocEl = document.querySelector('.legal-toc');
            const tocHeight = tocEl ? tocEl.offsetHeight : 0;
            const offset = navbarHeight + tocHeight + 16;

            const top = targetEl.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        });
    });

    // ── TOC : indicateur de section active via IntersectionObserver ──
    const sections = document.querySelectorAll('.legal-section[id]');
    if (!sections.length || !tocLinks.length) return;

    /**
     * Met à jour la classe active sur le lien TOC correspondant
     * @param {string} activeId - ID de la section visible
     */
    function setActiveLink(activeId) {
        tocLinks.forEach(link => {
            const href = link.getAttribute('href')?.substring(1);
            if (href === activeId) {
                link.classList.add('legal-toc__link--active');
            } else {
                link.classList.remove('legal-toc__link--active');
            }
        });
    }

    // Calcul du rootMargin dynamique pour compenser navbar + TOC
    const navbarHeight = parseInt(
        getComputedStyle(document.documentElement)
            .getPropertyValue('--navbar-height') || '80', 10
    );
    const tocEl = document.querySelector('.legal-toc');
    const tocHeight = tocEl ? tocEl.offsetHeight : 0;
    const topOffset = navbarHeight + tocHeight;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                setActiveLink(entry.target.id);
            }
        });
    }, {
        rootMargin: `-${topOffset + 1}px 0px -60% 0px`,
        threshold: 0
    });

    sections.forEach(section => observer.observe(section));

    // Activer le premier lien par défaut
    if (sections.length > 0) {
        setActiveLink(sections[0].id);
    }
});
