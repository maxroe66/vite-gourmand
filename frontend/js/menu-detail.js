
document.addEventListener('DOMContentLoaded', () => {

    // --- Éléments DOM ---
    const loader = document.getElementById('menu-detail-loader');
    const content = document.getElementById('menu-detail-content');
    
    // Info principales
    const elTitle = document.getElementById('menu-title');
    const elPrice = document.getElementById('menu-price');
    const elDishesList = document.getElementById('dishes-list');
    const elAllergensBox = document.getElementById('allergens-box');
    const elAllergensList = document.getElementById('allergens-list');
    
    // Info secondaires
    const elTheme = document.getElementById('menu-theme');
    const elRegime = document.getElementById('menu-regime');
    const elStock = document.getElementById('menu-stock');
    const elConditions = document.getElementById('menu-conditions');
    
    // Galerie
    const elMainImage = document.getElementById('main-image');
    const btnPrevImg = document.getElementById('btn-prev-img');
    const btnNextImg = document.getElementById('btn-next-img');
    const galleryControls = document.getElementById('gallery-controls');
    
    // Actions
    const btnOrder = document.getElementById('btn-order');

    // --- State ---
    let currentMenu = null;
    let currentImageIndex = 0;
    let menuImages = [];

    // --- Initialisation ---
    init();

    async function init() {
        const urlParams = new URLSearchParams(window.location.search);
        const menuId = urlParams.get('id');

        if (!menuId) {
            handleError("Aucun identifiant de menu fourni.");
            return;
        }

        try {
            // Chargement parallèle des détails et des infos auxiliaires (thèmes/régimes pour affichage libellé si besoin)
            // Note: Le backend renvoie souvent les IDs pour thème/régime, idéalement le `findById` devrait renvoyer les libellés via JOIN
            // Vérifions d'abord ce que renvoie le service.
            
            const menu = await MenuService.getMenuDetails(menuId);
            
            // Pour afficher les libellés Thème/Régime, si l'API ne renvoie que l'ID, il faudrait fetcher les listes.
            // On suppose ici que `findById` renvoie soit les libellés, soit on fera un appel supplémentaire.
            // On va fetcher les listes de thèmes/régimes au cas où pour mapping.
            const [themes, regimes] = await Promise.all([
                MenuService.getThemes(),
                MenuService.getRegimes()
            ]);

            currentMenu = menu;
            renderMenu(menu, themes, regimes);
            loader.classList.add('u-hidden');
            content.classList.add('is-visible');

        } catch (error) {
            console.error(error);
            handleError("Impossible de charger le menu. Il n'existe peut-être plus.");
        }
    }

    function handleError(msg) {
        loader.innerHTML = `<p class="error-text">${msg}</p><a href="/frontend/pages/home.html" class="button button--secondary">Retour à l'accueil</a>`;
    }

    function renderMenu(menu, themes, regimes) {
        // 1. Titre & Prix
        elTitle.textContent = menu.titre;
        const prix = parseFloat(menu.prix).toFixed(0);
        elPrice.textContent = `${prix}€ / ${menu.nombre_personne_min} personnes`;

        // 2. Thème & Régime (Mapping ID -> Libelle si nécessaire)
        // Si menu.id_theme est un entier, on cherche dans la liste.
        // Si le backend a déjà fait la jointure (ex: renvoie 'theme_libelle'), on l'utilise.
        // On va assumer ici mapping ID.
        if (menu.id_theme && themes) {
            const themeObj = themes.find(t => t.id_theme == menu.id_theme);
            elTheme.textContent = themeObj ? themeObj.libelle : 'Inconnu';
        } else {
            elTheme.textContent = menu.id_theme || '-';
        }

        if (menu.id_regime && regimes) {
            const regimeObj = regimes.find(r => r.id_regime == menu.id_regime);
            elRegime.textContent = regimeObj ? regimeObj.libelle : 'Inconnu';
        } else {
            elRegime.textContent = menu.id_regime || '-';
        }

        // 3. Stock
        const stock = parseInt(menu.stock_disponible || menu.stock || 0);
        if (stock <= 0) {
            elStock.textContent = "Rupture de stock";
            elStock.className = "stock-critical";
            btnOrder.disabled = true;
            btnOrder.textContent = "Indisponible";
            btnOrder.classList.add('is-disabled');
        } else if (stock < 5) {
            elStock.textContent = `Vite ! Plus que ${stock} commandes restantes`;
            elStock.className = "stock-warning";
        } else {
            elStock.textContent = "Stock disponible";
            elStock.className = "stock-ok";
        }

        // 4. Galerie Images
        // Le repo renvoie un tableau 'images' (strings URL)
        // S'il est vide, image par défaut
        if (menu.images && menu.images.length > 0) {
            menuImages = menu.images;
        } else {
            menuImages = ['/assets/images/menu-noel.webp']; // Fallback
        }
        updateGallery();

        // 5. Conditions
        if (menu.conditions) {
            // On sépare par les retours à la ligne
            const lines = menu.conditions.split('\n');
            elConditions.innerHTML = lines.map(line => `<li>${escapeHtml(line)}</li>`).join('');
        } else {
            elConditions.innerHTML = '<li>Aucune condition particulière.</li>';
        }

        // 6. Plats (Composition)
        // Les clés retournées par le repo sont 'libelle', 'type', 'description'
        if (menu.plats && menu.plats.length > 0) {
            // On peut trier par type : Entrée > Plat > Dessert
            const order = { 'ENTREE': 1, 'PLAT': 2, 'DESSERT': 3 };
            const sortedPlats = [...menu.plats].sort((a, b) => {
                return (order[a.type] || 99) - (order[b.type] || 99);
            });

            elDishesList.innerHTML = sortedPlats.map(plat => {
                const typeLabel = formatTypePlat(plat.type);
                return `
                    <li>
                        <span class="dish-type">${typeLabel} :</span>
                        <span class="dish-name">${escapeHtml(plat.libelle)}</span>
                    </li>
                `;
            }).join('');

            // 7. Allergènes (Agrégation)
            const allAllergens = new Set();
            menu.plats.forEach(plat => {
                if (plat.allergenes && Array.isArray(plat.allergenes)) {
                    plat.allergenes.forEach(allergene => {
                        if (allergene.libelle) {
                            allAllergens.add(allergene.libelle);
                        }
                    });
                }
            });
            
            if (allAllergens.size > 0) {
                elAllergensList.textContent = Array.from(allAllergens).join(', ');
                elAllergensBox.classList.add('is-visible');
            } else {
                elAllergensBox.classList.remove('is-visible'); 
            }

        } else {
            elDishesList.innerHTML = '<li>Composition non renseignée.</li>';
        }
    }

    // --- Helpers ---

    function formatTypePlat(type) {
        switch((type || '').toUpperCase()) {
            case 'ENTREE': return 'Entrée';
            case 'PLAT': return 'Plat';
            case 'DESSERT': return 'Dessert';
            default: return 'Autre';
        }
    }


    // --- Galerie ---

    function updateGallery() {
        if (menuImages.length === 0) return;
        
        // Sécurité index
        if (currentImageIndex < 0) currentImageIndex = menuImages.length - 1;
        if (currentImageIndex >= menuImages.length) currentImageIndex = 0;

        // Fade effect
        elMainImage.classList.add('is-fading');
        
        setTimeout(() => {
            elMainImage.src = menuImages[currentImageIndex];
            elMainImage.classList.remove('is-fading');
        }, 200);

        // Hide buttons if only 1 image
        if (menuImages.length <= 1) {
            galleryControls.classList.add('u-hidden');
        } else {
            galleryControls.classList.remove('u-hidden');
        }
    }

    btnPrevImg.addEventListener('click', () => {
        currentImageIndex--;
        updateGallery();
    });

    btnNextImg.addEventListener('click', () => {
        currentImageIndex++;
        updateGallery();
    });

    // --- Commande ---

    btnOrder.addEventListener('click', async () => {
        if (!currentMenu) return;

        // Vérification de l'authentification
        const user = await AuthService.getUser(); // Utilise le endpoint /api/auth/check
        
        if (user) {
            // Utilisateur connecté -> Page de commande
            window.location.href = `/frontend/pages/commande.html?menuId=${currentMenu.id_menu}`;
        } else {
            // Utilisateur non connecté -> Connexion avec redirection
            // On encode l'URL de retour pour revenir sur la commande après login
            const returnUrl = encodeURIComponent(`/frontend/pages/commande.html?menuId=${currentMenu.id_menu}`);
            window.location.href = `/frontend/pages/connexion.html?redirect=${returnUrl}`;
        }
    });

});
