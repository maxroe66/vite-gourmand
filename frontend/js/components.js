/**
 * Component Loader
 * Charge dynamiquement les composants HTML (header, footer)
 */

async function loadComponent(elementId, componentPath) {
    // Utilise un chemin absolu depuis la racine du projet
    const basePath = '/frontend/frontend/pages/components/';
    const fullPath = basePath + componentPath;
    
    try {
        const response = await fetch(fullPath);
        if (!response.ok) {
            throw new Error(`Failed to load ${fullPath}: ${response.status}`);
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
    await loadComponent('header-placeholder', 'navbar.html');
    
    // Charger le footer
    await loadComponent('footer-placeholder', 'footer.html');
    
    // Dispatcher un event pour signaler que les composants sont chargés
    document.dispatchEvent(new Event('componentsLoaded'));
});
