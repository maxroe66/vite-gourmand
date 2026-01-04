/**
 * Component Loader
 * Charge dynamiquement les composants HTML (header, footer)
 */

async function loadComponent(elementId, componentPath) {
    try {
        const response = await fetch(componentPath);
        if (!response.ok) {
            throw new Error(`Failed to load ${componentPath}: ${response.status}`);
        }
        const html = await response.text();
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = html;
        }
    } catch (error) {
        console.error('Error loading component:', error);
    }
}

// Charger les composants au chargement de la page
document.addEventListener('DOMContentLoaded', async () => {
    // Charger le header
    await loadComponent('header-placeholder', '/frontend/frontend/pages/components/navbar.html');
    
    // Charger le footer
    await loadComponent('footer-placeholder', '/frontend/frontend/pages/components/footer.html');
    
    // Initialiser le menu mobile après le chargement du header
    initMobileMenu();
});

// Initialisation du menu mobile
function initMobileMenu() {
    const toggle = document.querySelector('.navbar__toggle');
    const menu = document.getElementById('navMenu');

    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            
            toggle.setAttribute('aria-expanded', !isExpanded);
            menu.hidden = isExpanded;
        });
    }

    // Fermer le menu mobile au clic sur un lien
    const mobileLinks = menu?.querySelectorAll('a');
    mobileLinks?.forEach(link => {
        link.addEventListener('click', () => {
            menu.hidden = true;
            toggle?.setAttribute('aria-expanded', 'false');
        });
    });
}
