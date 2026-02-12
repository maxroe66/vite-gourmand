
/**
 * Gestion de l'affichage dynamique des menus sur la page d'accueil
 */
document.addEventListener('DOMContentLoaded', async () => {
    
    // Éléments du DOM
    const menusList = document.getElementById('menus-list');
    const filterPriceMax = document.getElementById('filter-price-max');
    const filterTheme = document.getElementById('filter-theme');
    const filterRegime = document.getElementById('filter-regime');
    const filterNbPers = document.getElementById('filter-nb-pers');

    if (!menusList) return; // Sécurité si on n'est pas sur la page d'accueil

    /**
     * Initialisation : Chargement des options de filtre et des menus
     */
    async function init() {
        try {
            // Chargement parallèle des filtres et des menus initiaux
            await Promise.all([
                loadThemes(),
                loadRegimes(),
                loadMenus()
            ]);
        } catch (error) {
            console.error("Erreur lors de l'initialisation des menus:", error);
            menusList.innerHTML = '<p class="error-msg">Impossible de charger les menus.</p>';
        }
    }

    /**
     * Charge les thèmes dans le select
     */
    async function loadThemes() {
        try {
            const themes = await MenuService.getThemes();
            themes.forEach(theme => {
                const option = document.createElement('option');
                option.value = theme.id_theme;
                option.textContent = theme.libelle;
                filterTheme.appendChild(option);
            });
        } catch (e) {
            console.warn('Erreur chargement thèmes', e);
        }
    }

    /**
     * Charge les régimes dans le select
     */
    async function loadRegimes() {
        try {
            const regimes = await MenuService.getRegimes();
            regimes.forEach(regime => {
                const option = document.createElement('option');
                option.value = regime.id_regime;
                option.textContent = regime.libelle;
                filterRegime.appendChild(option);
            });
        } catch (e) {
            console.warn('Erreur chargement régimes', e);
        }
    }

    /**
     * Charge et affiche les menus selon les filtres courants
     */
    async function loadMenus() {
        const filters = {
            prix_max: filterPriceMax.value,
            theme: filterTheme.value,
            regime: filterRegime.value,
            nb_personnes: filterNbPers.value
        };

        try {
            const menus = await MenuService.getMenus(filters);
            renderMenus(menus);
        } catch (error) {
            menusList.innerHTML = `<p class="error-msg">${error.message}</p>`;
        }
    }

    /**
     * Génère le HTML des cartes menus
     * @param {Array} menus 
     */
    function renderMenus(menus) {
        if (!menus || menus.length === 0) {
            menusList.innerHTML = '<p class="empty-msg">Aucun menu ne correspond \u00e0 votre recherche.</p>';
            if (window.refreshMenuCarousel) window.refreshMenuCarousel();
            return;
        }

        const html = menus.map(menu => {
            // Image par défaut si pas d'image
            const image = menu.image ? menu.image : '/assets/images/menu-noel.webp'; 
            
            // Formatage prix
            const prix = parseFloat(menu.prix).toFixed(0);

            return `
            <div class="menu-card">
                <img src="${image}" alt="${escapeHtml(menu.titre)}" loading="lazy">
                <div class="menu-card__content">
                    <h3>${escapeHtml(menu.titre)}</h3>
                    <div class="menu-card__price">${prix}€ / ${menu.nombre_personne_min} pers</div>
                    <p>${escapeHtml(menu.description || '')}</p>
                    <a href="/frontend/pages/menu-detail.html?id=${menu.id_menu}" class="menu-card__cta">Voir détails</a>
                </div>
            </div>
            `;
        }).join('');

        menusList.innerHTML = html;

        // Mise à jour importante pour le carrousel (flèches)
        if (window.refreshMenuCarousel) {
            // Petit délai pour laisser le temps au DOM de calculer les nouvelles largeurs
            setTimeout(window.refreshMenuCarousel, 50);
        }
    }


    // --- Écouteurs d'événements pour les filtres ---
    
    // Debounce pour éviter de spammer l'API quand on tape dans les champs texte
    let debounceTimer;
    function debounceLoad() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadMenus, 300);
    }

    filterPriceMax.addEventListener('input', debounceLoad);
    filterNbPers.addEventListener('input', debounceLoad);
    
    // Changement direct pour les selects
    filterTheme.addEventListener('change', loadMenus);
    filterRegime.addEventListener('change', loadMenus);

    // Lancement
    init();

});
