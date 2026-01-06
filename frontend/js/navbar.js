/**
 * Navbar Component
 * Gestion du menu mobile responsive
 */

let navbarInitialized = false;

function initNavbar() {
    // Éviter la double initialisation
    if (navbarInitialized) return;

    const toggle = document.querySelector('.navbar__toggle');
    const menu = document.getElementById('navMenu');

    // Si les éléments n'existent pas encore, on arrête
    if (!toggle || !menu) return;

    navbarInitialized = true;

    // Fonction pour ouvrir le menu
    const openMenu = () => {
        toggle.setAttribute('aria-expanded', 'true');
        menu.hidden = false;
    };

    // Fonction pour fermer le menu
    const closeMenu = () => {
        toggle.setAttribute('aria-expanded', 'false');
        menu.hidden = true;
    };

    // Toggle du menu au clic sur le burger
    toggle.addEventListener('click', (e) => {
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        isExpanded ? closeMenu() : openMenu();
    });

    // Fermer le menu au clic sur un lien
    const mobileLinks = menu.querySelectorAll('a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            closeMenu();
        });
    });

    // Fermer le menu au clic en dehors
    document.addEventListener('click', (e) => {
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        
        if (!isExpanded) return;
        
        // Vérifier si le clic est en dehors du menu et du toggle
        if (!menu.contains(e.target) && !toggle.contains(e.target)) {
            closeMenu();
        }
    }, true);

    // Fermer le menu avec la touche Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                closeMenu();
                toggle.focus();
            }
        }
    });
}

// Écouter l'event personnalisé des composants chargés
document.addEventListener('componentsLoaded', () => {
    initNavbar();
});

// Fallback au cas où
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        initNavbar();
    }, 200);
});
