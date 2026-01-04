// Gestion du menu mobile
document.addEventListener('DOMContentLoaded', () => {
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
});
