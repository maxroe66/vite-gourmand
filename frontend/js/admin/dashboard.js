/**
 * Dashboard — Orchestrateur principal
 * Initialise la page admin, gère la navigation entre onglets
 * et délègue le rendu à chaque module spécialisé.
 *
 * Modules chargés séparément :
 *   dashboard-menus.js, dashboard-plats.js, dashboard-commandes.js,
 *   dashboard-avis.js, dashboard-employes.js, dashboard-stats.js
 */

document.addEventListener('DOMContentLoaded', async () => {
    // 1. Protection de la page
    let currentUser = null;
    try {
        currentUser = await AdminGuard.checkAccess();
    } catch (e) {
        // Redirection gérée par le guard
        return;
    }

    // 2. Initialisation interface utilisateur
    initUserInfo(currentUser);
    initSidebar();
    
    // 3. Charger le premier onglet par défaut
    loadTab('menus');
});

/**
 * Affiche les infos utilisateur dans le header du dashboard.
 * @param {Object} user
 */
function initUserInfo(user) {
    document.getElementById('user-name').textContent = `${user.prenom} ${user.nom}`;
    document.getElementById('user-role').textContent = user.role;

    // Afficher les éléments ADMIN ONLY
    if (user.role === 'ADMINISTRATEUR') {
        document.querySelectorAll('.admin-only').forEach(el => el.classList.add('is-visible'));
    }
}

/**
 * Initialise la sidebar : écoute les clics sur les onglets.
 */
function initSidebar() {
    const buttons = document.querySelectorAll('.sidebar__link[data-tab]');
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Active class toggling
            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Load Content
            const tabName = btn.dataset.tab;
            loadTab(tabName);
        });
    });
}

/**
 * Charge un onglet dans le conteneur principal.
 * Délègue à la fonction loadXxxView() du module correspondant.
 * @param {string} tabName
 */
function loadTab(tabName) {
    const titleEl = document.getElementById('page-title');
    const contentEl = document.getElementById('dashboard-content');
    const actionsEl = document.getElementById('header-actions');

    // Reset
    contentEl.innerHTML = '<div class="loader-container"><div class="loader"></div></div>';
    actionsEl.innerHTML = '';

    switch(tabName) {
        case 'menus':
            titleEl.textContent = 'Gestion des Menus';
            loadMenusView(contentEl, actionsEl);
            break;
        case 'plats':
            titleEl.textContent = 'Gestion des Plats';
            loadPlatsView(contentEl, actionsEl);
            break;
        case 'commandes':
            titleEl.textContent = 'Historique des Commandes';
            loadCommandesView(contentEl, actionsEl);
            break;
        case 'avis':
            titleEl.textContent = 'Avis Clients - Modération';
            loadAvisView(contentEl, actionsEl);
            break;
        case 'equipe':
            titleEl.textContent = 'Gestion de l\'Équipe';
            loadEquipeView(contentEl, actionsEl);
            break;
        case 'stats':
            titleEl.textContent = 'Statistiques';
            loadStatsView(contentEl, actionsEl);
            break;
        case 'materiel':
            titleEl.textContent = 'Gestion du Matériel';
            loadMaterielView(contentEl, actionsEl);
            break;
        case 'horaires':
            titleEl.textContent = 'Gestion des Horaires';
            loadHorairesView(contentEl, actionsEl);
            break;
        default:
            contentEl.innerHTML = '<p>Onglet inconnu.</p>';
    }
}
