
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

function initUserInfo(user) {
    document.getElementById('user-name').textContent = `${user.prenom} ${user.nom}`;
    document.getElementById('user-role').textContent = user.role;

    // Affiches les éléments ADMIN ONLY
    if (user.role === 'ADMINISTRATEUR') {
        document.querySelectorAll('.admin-only').forEach(el => el.classList.add('is-visible'));
    }
}

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

function loadTab(tabName) {
    const titleEl = document.getElementById('page-title');
    const contentEl = document.getElementById('dashboard-content');
    const actionsEl = document.getElementById('header-actions');

    // Reset basics
    contentEl.innerHTML = '<div class="loader-container"><div class="loader"></div></div>';
    actionsEl.innerHTML = ''; // Clear header buttons

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
        default:
            contentEl.innerHTML = '<p>Onglet inconnu.</p>';
    }
}

// --- View Loaders (Stubs for now) ---

async function loadMenusView(container, headerActions) {
    // Bouton d'ajout
    headerActions.innerHTML = `<button class="btn btn--primary" id="btn-add-menu"><i class="fa-solid fa-plus"></i> Nouveau Menu</button>`;
    
    // Structure de base
    container.innerHTML = `
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Prix</th>
                        <th>Pers. Min</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="menus-table-body">
                    <tr><td colspan="4" class="data-table__cell--center">Chargement...</td></tr>
                </tbody>
            </table>
        </div>
    `;

    // --- Gestionnaire Bouton Nouveau Menu ---
    document.getElementById('btn-add-menu').addEventListener('click', () => {
        openMenuModal(); // Mode Création
    });

    try {
        await fetchMenusList();
    } catch (error) {
        console.error("Erreur chargement menus:", error);
        document.getElementById('menus-table-body').innerHTML = `
            <tr><td colspan="4" class="data-table__cell--error">Erreur de chargement: ${error.message}</td></tr>
        `;
    }
    
    // Initialiser les options des selects (Theme/Regime) une seule fois si possible
    initMenuFormSelects();
    initMenuModalLogic();
}

async function fetchMenusList() {
    const menus = await MenuService.getMenus();
    const tbody = document.getElementById('menus-table-body');
    tbody.innerHTML = ''; 

    if (menus.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="data-table__cell--center">Aucun menu trouvé.</td></tr>';
        return;
    }

    menus.forEach(menu => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td data-label="Titre">
                <div class="u-text-bold">${escapeHtml(menu.titre)}</div>
                <small class="u-text-muted">${menu.description ? menu.description.substring(0, 50) + '...' : ''}</small>
            </td>
            <td data-label="Prix">${parseFloat(menu.prix).toFixed(2)} €</td>
            <td data-label="Pers. Min">${menu.nombre_personne_min}</td>
            <td data-label="Actions">
                <div class="data-table__actions">
                    <button class="btn btn--sm btn--outline-primary btn-edit-menu" data-id="${menu.id_menu}">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn btn--sm btn--outline-danger btn-delete-menu" data-id="${menu.id_menu}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    // Attach evenements aux boutons dynamiques
    document.querySelectorAll('.btn-edit-menu').forEach(btn => {
        btn.addEventListener('click', () => openMenuModal(btn.dataset.id));
    });

    document.querySelectorAll('.btn-delete-menu').forEach(btn => {
        btn.addEventListener('click', () => deleteMenu(btn.dataset.id));
    });
}

// Global state for modals
let isMenuModalMsgInit = false;
let commandesById = new Map();

async function initMenuFormSelects() {
    // Ne charger qu'une fois
    const themeSelect = document.getElementById('menu-theme');
    if(themeSelect.options.length > 1) return;

    try {
        const [themes, regimes] = await Promise.all([
            MenuService.getThemes(),
            MenuService.getRegimes()
        ]);

        themeSelect.innerHTML = '<option value="">-- Choisir --</option>';
        themes.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id_theme;
            opt.textContent = t.libelle;
            themeSelect.appendChild(opt);
        });

        const regimeSelect = document.getElementById('menu-regime');
        regimeSelect.innerHTML = '<option value="">-- Choisir --</option>';
        regimes.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id_regime;
            opt.textContent = r.libelle;
            regimeSelect.appendChild(opt);
        });

    } catch(e) {
        console.error("Erreur chargement selects", e);
    }
}


// --- Gestion des Images (URLs + Upload) ---

function addImageInput(value = '') {
    const container = document.getElementById('menu-images-list');
    const div = document.createElement('div');
    div.className = 'image-url-list__item u-flex u-gap-sm u-items-center';
    
    div.innerHTML = `
        <div class="image-row">
            <input type="text" name="menu_images[]" class="menu-image-input" placeholder="URL ou Upload..." value="${escapeHtml(value)}">
            <label class="btn btn--sm btn--secondary" title="Uploader depuis PC">
                <i class="fa-solid fa-upload"></i>
                <input type="file" class="file-upload-input u-hidden" accept="image/*">
            </label>
        </div>
        <button type="button" class="btn btn--sm btn--outline-danger btn-remove-image" title="Supprimer">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;

    // Suppression
    div.querySelector('.btn-remove-image').addEventListener('click', () => {
        container.removeChild(div);
    });

    // Upload
    const fileInput = div.querySelector('.file-upload-input');
    const textInput = div.querySelector('.menu-image-input');

    fileInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        // Feedback visuel
        textInput.value = "Upload en cours...";
        textInput.disabled = true;

        const formData = new FormData();
        formData.append('image', file);

        try {
            // Authentification via cookie HttpOnly (credentials: 'include')
            const response = await fetch('/api/upload', {
                method: 'POST',
                headers: AuthService.addCsrfHeader({}),
                body: formData,
                credentials: 'include'
            });

            if (!response.ok) throw new Error('Erreur upload');
            
            const resData = await response.json();
            if (resData.success && resData.url) {
                textInput.value = resData.url;
            } else {
                throw new Error(resData.error || 'Erreur inconnue');
            }

        } catch (err) {
            console.error(err);
            alert("Echec de l'upload : " + err.message);
            textInput.value = ""; 
        } finally {
            textInput.disabled = false;
        }
    });

    container.appendChild(div);
}

function initMenuModalLogic() {
    if (isMenuModalMsgInit) return;
    
    // Close handlers
    document.getElementById('modal-menu-close').addEventListener('click', closeMenuModal);
    document.getElementById('modal-menu-cancel').addEventListener('click', closeMenuModal);
    
    // Image add button
    document.getElementById('btn-add-image-url').addEventListener('click', () => addImageInput());

    // Form Submit
    document.getElementById('form-menu').addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveMenu();
    });

    isMenuModalMsgInit = true;
}

async function loadMenuDishesSelectors(selectedIds = []) {
    const containers = {
        'ENTREE': document.getElementById('menu-entrees-list'),
        'PLAT': document.getElementById('menu-plats-list'),
        'DESSERT': document.getElementById('menu-desserts-list')
    };
    
    // Clear and Loading state
    Object.values(containers).forEach(c => {
        if(c) c.innerHTML = '<small>Chargement...</small>';
    });

    try {
        const plats = await PlatService.getPlats();
        
        // Reset
        Object.values(containers).forEach(c => c ? c.innerHTML = '' : null);
        
        plats.forEach(plat => {
            const container = containers[plat.type];
            if (!container) return; // Should not happen if types match

            const isChecked = selectedIds.includes(plat.id_plat);
            
            const div = document.createElement('label');
            div.className = 'checkbox-item';
            
            div.innerHTML = `
                <input type="checkbox" name="menu_plats" value="${plat.id_plat}" ${isChecked ? 'checked' : ''}>
                <span title="${escapeHtml(plat.description || '')}">${escapeHtml(plat.libelle)}</span>
            `;
            container.appendChild(div);
        });
        
        // Empty message
        Object.values(containers).forEach(c => {
             if (c && c.children.length === 0) c.innerHTML = '<small class="u-text-muted u-text-italic">Aucun plat.</small>';
        });

    } catch (e) {
        console.error(e);
        Object.values(containers).forEach(c => c ? c.innerHTML = '<small class="u-text-error">Erreur chargement plats</small>' : null);
    }
}

async function loadMenuMaterialSelectors(selectedMaterials = []) {
    // selectedMaterials est un tableau d'objets : [{ id_materiel: 1, quantite: 2 }, ...] 
    // Ou directement depuis l'API Menu details : items avec id_materiel et quantite
    
    const container = document.getElementById('menu-materiel-list');
    if (!container) return;
    
    container.innerHTML = '<small>Chargement...</small>';

    try {
        const materiels = await MenuService.getMaterials();
        container.innerHTML = '';

        if (!materiels || materiels.length === 0) {
            container.innerHTML = '<small class="u-text-muted">Aucun matériel disponible.</small>';
            return;
        }

        // Création d'une Map pour accès rapide aux quantités existantes
        const selectedMap = new Map();
        selectedMaterials.forEach(m => selectedMap.set(m.id_materiel, m.quantite));

        materiels.forEach(mat => {
            const isSelected = selectedMap.has(mat.id_materiel);
            const qty = selectedMap.get(mat.id_materiel) || 1; // Défaut 1

            const div = document.createElement('div');
            div.className = 'materiel-item';

            // HTML Structure: Checkbox | Nom | Input Qty
            div.innerHTML = `
                <label class="materiel-item__label">
                    <input type="checkbox" class="mat-check" value="${mat.id_materiel}" ${isSelected ? 'checked' : ''}>
                    <div>
                        <span class="u-text-bold">${escapeHtml(mat.libelle)}</span>
                        <br><small class="u-text-muted">Stock: ${mat.stock_disponible}</small>
                    </div>
                </label>
                <div class="materiel-item__qty">
                    <input type="number" class="mat-qty input input--sm" value="${qty}" min="1" max="100" ${!isSelected ? 'disabled' : ''} title="Quantité par personne">
                </div>
            `;
            
            container.appendChild(div);

            // Activation/Désactivation auto du champ quantité
            const check = div.querySelector('.mat-check');
            const input = div.querySelector('.mat-qty');
            
            check.addEventListener('change', () => {
                input.disabled = !check.checked;
                if(check.checked && !input.value) input.value = 1;
            });
        });

    } catch (e) {
        console.error("Erreur chargement matériel", e);
        container.innerHTML = '<small class="u-text-error">Impossible de charger le matériel.</small>';
    }
}

async function openMenuModal(menuId = null) {
    const modal = document.getElementById('modal-menu');
    const form = document.getElementById('form-menu');
    
    // Reset form
    form.reset();
    document.getElementById('menu-id').value = '';

    // Init selects (thèmes/régimes)
    await initMenuFormSelects();

    if (menuId) {
        document.getElementById('modal-menu-title').textContent = 'Modifier le Menu';
        
        try {
            const menu = await MenuService.getMenuDetails(menuId);
            
            // Pré-remplissage
            document.getElementById('menu-id').value = menu.id_menu;
            document.getElementById('menu-titre').value = menu.titre;
            document.getElementById('menu-description').value = menu.description || '';
            document.getElementById('menu-prix').value = menu.prix;
            document.getElementById('menu-nb-pers').value = menu.nombre_personne_min;
            document.getElementById('menu-stock').value = menu.stock_disponible || menu.stock;
            
            // Selects
            document.getElementById('menu-theme').value = menu.id_theme;
            document.getElementById('menu-regime').value = menu.id_regime;

            // Charger les plats et cocher ceux existants
            // menu.plats est un tableau d'objets ou vide
            const existingDishIds = (menu.plats || []).map(p => p.id_plat);
            await loadMenuDishesSelectors(existingDishIds);

            // Charger liste matériel
            // Il faudrait que l'API renvoie "materiels" : [{id_materiel, quantite}, ...]
            // Si pas dispo, on suppose un tableau vide
            await loadMenuMaterialSelectors(menu.materiels || []);

            // Charger les images
            const imgContainer = document.getElementById('menu-images-list');
            imgContainer.innerHTML = '';
            if (menu.images && menu.images.length > 0) {
                menu.images.forEach(url => addImageInput(url));
            } else {
                addImageInput(); // Au moins un champ vide par défaut
            }

        } catch (error) {
            console.error("Erreur chargement menu", error);
            alert("Impossible de charger les détails du menu.");
            return;
        }

    } else {
        document.getElementById('modal-menu-title').textContent = 'Nouveau Menu';
        // Valeurs par défaut
        document.getElementById('menu-stock').value = 10;
        document.getElementById('menu-nb-pers').value = 2;
        // Charger la liste des plats vide
        await loadMenuDishesSelectors([]);
        // Charger matériel vide
        await loadMenuMaterialSelectors([]);
        // Reset images
        document.getElementById('menu-images-list').innerHTML = '';
        addImageInput();
    }

    modal.classList.add('is-visible');
}

function closeMenuModal() {
    document.getElementById('modal-menu').classList.remove('is-visible');
}

async function saveMenu() {
    const form = document.getElementById('form-menu');
    const formData = new FormData(form);
    
    // Récupérer les plats cochés
    const selectedPlats = [];
    document.querySelectorAll('input[name="menu_plats"]:checked').forEach(cb => {
        selectedPlats.push(parseInt(cb.value));
    });

    // Récupérer matériel coché + quantités
    const selectedMaterials = [];
    const matList = document.getElementById('menu-materiel-list');
    if (matList) {
        matList.querySelectorAll('.mat-check:checked').forEach(cb => {
            // Trouver l'input number frère
            const div = cb.closest('div').parentElement; // Remonte au label puis au parent div (structure Checkbox | Nom | Input)
            // Ah attention à ma structure HTML insérée dans loadMenuMaterialSelectors...
            // Structure: div > label (checkbox+div) + div(input)
            const rowDiv = cb.closest('div').parentElement; // Checkbox est dans Label, Label est dans RowDiv ? 
            // Non: <label...><input checkbox>...</label> est un enfant de RowDiv.
            // Donc cb.closest('label').parentElement => RowDiv
            const row = cb.closest('label').parentElement;
            const qtyInput = row.querySelector('.mat-qty');
            
            selectedMaterials.push({
                id: parseInt(cb.value),
                quantite: parseInt(qtyInput.value) || 1
            });
        });
    }


    // Récupérer les images
    const images = [];
    document.querySelectorAll('.menu-image-input').forEach(input => {
        const val = input.value.trim();
        if(val) images.push(val);
    });

    const data = {
        titre: formData.get('titre'),
        description: formData.get('description'),
        prix: parseFloat(formData.get('prix')),
        nb_personnes_min: parseInt(formData.get('nombre_personne_min')),
        stock: parseInt(formData.get('stock') || 0),
        id_theme: formData.get('theme_id') ? parseInt(formData.get('theme_id')) : null,
        id_regime: formData.get('regime_id') ? parseInt(formData.get('regime_id')) : null,
        plats: selectedPlats, // Envoi du tableau d'IDs
        materiels: selectedMaterials, // Tableau d'objets {id, quantite}
        images: images // Envoi du tableau d'URLs
    };

    const id = formData.get('id');

    try {
        if (id) {
            await MenuService.updateMenu(id, data);
            alert('Menu mis à jour !');
        } else {
            await MenuService.createMenu(data);
            alert('Menu créé avec succès !');
        }
        closeMenuModal();
        fetchMenusList(); 
    } catch (e) {
        alert('Erreur : ' + e.message);
    }
}

async function deleteMenu(id) {
    if(!confirm('Voulez-vous vraiment supprimer ce menu ?')) return;
    try {
        await MenuService.deleteMenu(id);
        fetchMenusList();
    } catch(e) {
        alert('Erreur suppression : ' + e.message);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

async function loadPlatsView(container, headerActions) {
    headerActions.innerHTML = `<button class="btn btn--primary" id="btn-add-plat"><i class="fa-solid fa-plus"></i> Nouveau Plat</button>`;
    
    container.innerHTML = `
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Libellé</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="plats-table-body">
                    <tr><td colspan="4" class="data-table__cell--center">Chargement...</td></tr>
                </tbody>
            </table>
        </div>
    `;

    document.getElementById('btn-add-plat').addEventListener('click', () => {
        openPlatModal();
    });

    try {
        await fetchPlatsList();
    } catch (error) {
        console.error("Erreur chargement plats:", error);
    }

    initPlatModalLogic();
}

async function fetchPlatsList() {
    const plats = await PlatService.getPlats();
    const tbody = document.getElementById('plats-table-body');
    tbody.innerHTML = '';

    if (plats.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="data-table__cell--center">Aucun plat trouvé.</td></tr>';
        return;
    }

    // Mapping des types pour affichage propre
    const types = { 'ENTREE': 'Entrée', 'PLAT': 'Plat Principal', 'DESSERT': 'Dessert' };

    plats.forEach(plat => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="u-text-bold">${escapeHtml(plat.libelle)}</td>
            <td><span class="badge badge--info">${types[plat.type] || plat.type}</span></td>
            <td>${escapeHtml(plat.description || '')}</td>
            <td>
                <div class="data-table__actions">
                    <button class="btn btn--sm btn--outline-primary btn-edit-plat" data-id="${plat.id_plat}">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn btn--sm btn--outline-danger btn-delete-plat" data-id="${plat.id_plat}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    // Attach events
    document.querySelectorAll('.btn-edit-plat').forEach(btn => {
        btn.addEventListener('click', () => openPlatModal(btn.dataset.id));
    });

    document.querySelectorAll('.btn-delete-plat').forEach(btn => {
        btn.addEventListener('click', () => deletePlat(btn.dataset.id));
    });
}

// Global flag for plat modal
let isPlatModalInit = false;

function initPlatModalLogic() {
    if (isPlatModalInit) return;
    
    document.getElementById('modal-plat-close').addEventListener('click', closePlatModal);
    document.getElementById('modal-plat-cancel').addEventListener('click', closePlatModal);
    
    document.getElementById('form-plat').addEventListener('submit', async (e) => {
        e.preventDefault();
        await savePlat();
    });

    isPlatModalInit = true;
}

async function openPlatModal(platId = null) {
    const modal = document.getElementById('modal-plat');
    const form = document.getElementById('form-plat');
    form.reset();
    document.getElementById('plat-id').value = '';

    // Charger les allergènes (si pas déjà fait ou refresh)
    await loadAllergenesCheckboxes();

    if (platId) {
        document.getElementById('modal-plat-title').textContent = 'Modifier le Plat';
        try {
            const plat = await PlatService.getPlatDetails(platId);
            
            document.getElementById('plat-id').value = plat.id_plat;
            document.getElementById('plat-libelle').value = plat.libelle;
            document.getElementById('plat-description').value = plat.description || '';
            document.getElementById('plat-type').value = plat.type;

            // Cocher les allergènes
            if (plat.allergenes) {
                plat.allergenes.forEach(a => {
                    const checkbox = document.getElementById(`allergene-${a.id_allergene}`);
                    if (checkbox) checkbox.checked = true;
                });
            }

        } catch (e) {
            console.error(e);
            alert('Erreur chargement plat');
            return;
        }

    } else {
        document.getElementById('modal-plat-title').textContent = 'Nouveau Plat';
    }

    modal.classList.add('is-visible');
}

function closePlatModal() {
    document.getElementById('modal-plat').classList.remove('is-visible');
}

async function loadAllergenesCheckboxes() {
    const container = document.getElementById('plat-allergenes-list');
    // On peut vérifier si container est vide pour éviter re-fetch, 
    // mais si on ajoute des allergenes entre temps... bon ok simple check
    if(container.children.length > 1) { 
        // Reset checkboxes
        container.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        return; 
    }

    try {
        const allergenes = await PlatService.getAllergenes();
        container.innerHTML = '';
        
        allergenes.forEach(a => {
            const div = document.createElement('div');
            div.innerHTML = `
                <label class="allergene-label">
                    <input type="checkbox" name="allergenes" value="${a.id_allergene}" id="allergene-${a.id_allergene}">
                    ${escapeHtml(a.libelle)}
                </label>
            `;
            container.appendChild(div);
        });
    } catch (e) {
        container.innerHTML = '<p class="u-text-error">Erreur chargement allergènes</p>';
    }
}

async function savePlat() {
    const form = document.getElementById('form-plat');
    const formData = new FormData(form);
    
    // Récupérer les ID des allergènes cochés
    const allergenIds = [];
    form.querySelectorAll('input[name="allergenes"]:checked').forEach(cb => {
        allergenIds.push(parseInt(cb.value));
    });

    const data = {
        libelle: formData.get('libelle'),
        description: formData.get('description'),
        type: formData.get('type'),
        allergenIds: allergenIds
    };

    const id = formData.get('id');

    try {
        if (id) {
            await PlatService.updatePlat(id, data);
            alert('Plat mis à jour !');
        } else {
            await PlatService.createPlat(data);
            alert('Plat créé avec succès !');
        }
        closePlatModal();
        fetchPlatsList();
    } catch (e) {
        alert('Erreur: ' + e.message);
    }
}

async function deletePlat(id) {
    if(!confirm('Supprimer ce plat ? Cela pourrait affecter les menus existants.')) return;
    try {
        await PlatService.deletePlat(id);
        fetchPlatsList();
    } catch(e) {
        alert('Erreur suppression : ' + e.message);
    }
}

// --- Commandes View ---

async function loadCommandesView(container, headerActions) {
    headerActions.innerHTML = '';

    // Filtres
    container.innerHTML = `
        <div class="filters-bar">
            <select id="filter-status" class="input">
                <option value="">Tous les statuts</option>
                <option value="EN_ATTENTE">En attente</option>
                <option value="ACCEPTE">Accepté</option>
                <option value="EN_PREPARATION">En préparation</option>
                <option value="EN_LIVRAISON">En livraison</option>
                <option value="EN_ATTENTE_RETOUR">Retour Matériel</option>
                <option value="TERMINEE">Terminée</option>
                <option value="ANNULEE">Annulée</option>
            </select>
            <button class="btn btn--secondary" id="btn-refresh-cmd"><i class="fa-solid fa-rotate-right"></i></button>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client / Date</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="cmd-table-body">
                    <tr><td colspan="5" class="data-table__cell--center">Chargement...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Changement Statut (Annulation) -->
        <div id="modal-cancel-cmd" class="modal-overlay">
            <div class="modal">
                <div class="modal__header">
                    <h3 class="modal__title">Annuler la commande</h3>
                    <button class="modal__close" id="close-cancel-cmd">&times;</button>
                </div>
                <div class="modal__body">
                    <form id="form-cancel-cmd">
                        <input type="hidden" name="cmdId" id="cancel-cmd-id">
                        <div class="form-group">
                            <label>Motif d'annulation *</label>
                            <textarea name="motif" class="input" required rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Mode de contact *</label>
                            <select name="modeContact" class="input" required>
                                <option value="GSM">Téléphone (GSM)</option>
                                <option value="MAIL">Email</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn--danger">Confirmer Annulation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Détail Commande -->
        <div id="modal-view-cmd" class="modal-overlay">
            <div class="modal modal--wide">
                <div class="modal__header">
                    <h3 class="modal__title" id="view-cmd-title">Commande #...</h3>
                    <button class="modal__close" id="close-view-cmd">&times;</button>
                </div>
                <div class="modal__body" id="view-cmd-body">
                    <!-- Détails injectés ici -->
                </div>
            </div>
        </div>
    `;

    document.getElementById('btn-refresh-cmd').addEventListener('click', fetchCommandesList);
    document.getElementById('filter-status').addEventListener('change', fetchCommandesList);
    
    // Init Modal Cancel
    const modalCancel = document.getElementById('modal-cancel-cmd');
    document.getElementById('close-cancel-cmd').addEventListener('click', () => modalCancel.classList.remove('is-visible'));
    
    // Init Modal View
    const modalView = document.getElementById('modal-view-cmd');
    document.getElementById('close-view-cmd').addEventListener('click', () => modalView.classList.remove('is-visible'));

    document.getElementById('form-cancel-cmd').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const id = formData.get('cmdId');
        const motif = formData.get('motif');
        const mode = formData.get('modeContact');
        
        try {
            await CommandeService.updateStatus(id, 'ANNULEE', motif, mode);
            modalCancel.classList.remove('is-visible');
            fetchCommandesList();
            alert("Commande annulée avec succès.");
        } catch (err) {
            alert(err.message);
        }
    });

    fetchCommandesList();
}

async function fetchCommandesList() {
    const status = document.getElementById('filter-status').value;
    const body = document.getElementById('cmd-table-body');
    body.innerHTML = '<tr><td colspan="5" class="data-table__cell--center">Chargement...</td></tr>';

    try {
        const filters = {};
        if (status) filters.status = status;
        
        const commandes = await CommandeService.getAllOrders(filters);
        
        if (commandes.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="data-table__cell--center">Aucune commande trouvée.</td></tr>';
            return;
        }

        commandesById = new Map(commandes.map(cmd => [String(cmd.id), cmd]));

        body.innerHTML = commandes.map(cmd => {
            const safeId = escapeHtml(String(cmd.id ?? ''));
            const safeUserId = escapeHtml(String(cmd.userId ?? ''));
            
            // Logique d'affichage des boutons
            let actions = '';
            
            // Bouton spécial Retour Matériel
            if (cmd.statut === 'EN_ATTENTE_RETOUR') {
                actions += `
                    <button class="btn btn--sm btn--success btn-return-material" data-id="${cmd.id}" title="Valider Retour Matériel">
                        <i class="fa-solid fa-box-open"></i>
                    </button>
                `;
            }

            actions += `
                <button class="btn btn--sm btn--secondary btn-view-cmd" data-id="${safeId}"><i class="fa-solid fa-eye"></i></button>
            `;


            return `
            <tr>
                <td>#${safeId}</td>
                <td>
                    <div><strong>${new Date(cmd.datePrestation).toLocaleDateString()}</strong></div>
                    <small>Client #${safeUserId}</small>
                </td>
                <td>${parseFloat(cmd.prixTotal).toFixed(2)} €</td>
                <td>
                    ${renderStatusSelect(cmd.id, cmd.statut)}
                </td>
                <td>
                    <div class="data-table__actions">
                        ${actions}
                    </div>
                </td>
            </tr>
            `;
        }).join('');

        // Attach listeners to select
        document.querySelectorAll('.cmd-status-select').forEach(select => {
            select.addEventListener('change', async (e) => {
                const newStat = e.target.value;
                const cmdId = e.target.dataset.id;
                
                if (newStat === 'ANNULEE') {
                    // Open Modal
                    document.getElementById('cancel-cmd-id').value = cmdId;
                    document.getElementById('modal-cancel-cmd').classList.add('is-visible');
                } else {
                    if (confirm(`Passer la commande #${cmdId} à ${newStat} ?`)) {
                        try {
                            await CommandeService.updateStatus(cmdId, newStat);
                            fetchCommandesList(); // Refresh to confirm backend state
                        } catch (err) {
                            alert(err.message);
                            fetchCommandesList(); // Reset
                        }
                    } else {
                         fetchCommandesList(); // Reset
                    }
                }
            });
        });

        // Attach listeners to Return Material buttons
        document.querySelectorAll('.btn-return-material').forEach(btn => {
            btn.addEventListener('click', async () => {
                if(confirm('Confirmez-vous le retour complet du matériel pour cette commande ?\n\n- Le stock sera réincrémenté.\n- La commande passera en TERMINEE.\n- Un email de confirmation sera envoyé.')) {
                    // Sauvegarder le contenu original avant le try
                    const originalContent = btn.innerHTML;
                    try {
                        // Afficher un loader temporaire sur le bouton
                        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                        btn.disabled = true;

                        await CommandeService.returnMaterial(btn.dataset.id);
                        
                        alert('Retour validé avec succès !');
                        fetchCommandesList();
                    } catch (e) {
                         alert('Erreur : ' + e.message);
                         btn.innerHTML = originalContent;
                         btn.disabled = false;
                    }
                }
            });
        });


        // Attach listeners to view buttons
        document.querySelectorAll('.btn-view-cmd').forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Remonter au bouton si click sur icon
                const target = e.target.closest('.btn-view-cmd');
                const cmdId = target.dataset.id;
                const cmd = commandesById.get(cmdId);
                if (!cmd) return;
                openCmdDetails(cmd);
            });
        });

    } catch (e) {
        console.error(e);
        body.innerHTML = `<tr><td colspan="5" class="data-table__cell--error">${escapeHtml(e.message || 'Erreur')}</td></tr>`;
    }
}

function openCmdDetails(cmd) {
    const modal = document.getElementById('modal-view-cmd');
    document.getElementById('view-cmd-title').textContent = `Commande #${cmd.id} - ${cmd.statut}`;
    const safe = (value) => escapeHtml(String(value ?? ''));
    const datePrestation = new Date(cmd.datePrestation).toLocaleDateString();
    const dateCommande = new Date(cmd.dateCommande).toLocaleString();
    const distanceLabel = cmd.horsBordeaux ? 'Hors Zone' : 'Bordeaux';
    
    document.getElementById('view-cmd-body').innerHTML = `
        <div class="cmd-detail-grid">
            <div class="cmd-detail-section">
                <h4>Info Client & Livraison</h4>
                <p><strong>Client ID:</strong> ${safe(cmd.userId)}</p>
                <p><strong>Adresse:</strong> ${safe(cmd.adresseLivraison)}</p>
                <p><strong>Ville:</strong> ${safe(cmd.codePostal)} ${safe(cmd.ville)}</p>
                <p><strong>Tel:</strong> ${safe(cmd.gsm)}</p>
                <p><strong>Distance:</strong> ${safe(cmd.distanceKm)} km (${safe(distanceLabel)})</p>
            </div>
            <div class="cmd-detail-section">
                <h4>Prestation</h4>
                <p><strong>Date Prestation:</strong> ${safe(datePrestation)}</p>
                <p><strong>Heure:</strong> ${safe(cmd.heureLivraison)}</p>
                <p><strong>Nb Personnes:</strong> ${safe(cmd.nombrePersonnes)} (Min: ${safe(cmd.nombrePersonneMinSnapshot)})</p>
                <p><strong>Matériel Prêt:</strong> ${cmd.materielPret ? 'Oui' : 'Non'}</p>
            </div>
        </div>
        <div class="cmd-detail-financials">
            <h4>Détails Financiers</h4>
            <table>
                <tr><td>Prix Unitaire Menu:</td> <td>${safe(cmd.prixMenuUnitaire)} €</td></tr>
                <tr><td>Réduction:</td> <td class="u-text-success">-${safe(cmd.montantReduction)} € ${cmd.reductionAppliquee ? '(APPLIQUÉE)' : ''}</td></tr>
                <tr><td>Frais Livraison:</td> <td>${safe(cmd.fraisLivraison)} €</td></tr>
                <tr class="cmd-detail-total"><td>TOTAL:</td> <td>${safe(cmd.prixTotal)} €</td></tr>
            </table>
        </div>
        <div class="cmd-detail-meta">
            Commande passée le ${safe(dateCommande)}
        </div>
    `;
    
    modal.classList.add('is-visible');
}

function renderStatusSelect(id, currentStatus) {
    const safeId = escapeHtml(String(id ?? ''));
    const statuses = ['EN_ATTENTE', 'ACCEPTE', 'EN_PREPARATION', 'EN_LIVRAISON', 'LIVRE', 'EN_ATTENTE_RETOUR', 'TERMINEE', 'ANNULEE'];
    const options = statuses.map(s => `
        <option value="${s}" ${s === currentStatus ? 'selected' : ''}>${s}</option>
    `).join('');
    
    // Status color via modifier class
    let statusMod = 'default';
    if(currentStatus === 'EN_ATTENTE') statusMod = 'pending';
    else if(currentStatus === 'ACCEPTE') statusMod = 'accepted';
    else if(currentStatus === 'ANNULEE') statusMod = 'cancelled';
    
    return `<select class="input input--sm cmd-status-select cmd-status-select--${statusMod}" data-id="${safeId}">${options}</select>`;
}

// --- AVIS VIEW ---

async function loadAvisView(container, headerActions) {
    // Boutons de filtre 
    headerActions.innerHTML = `
        <div class="filters">
            <button class="btn btn--sm btn--primary active" onclick="window.filterAvis('EN_ATTENTE', this)">En Attente</button>
            <button class="btn btn--sm" onclick="window.filterAvis('VALIDE', this)">Validés</button>
        </div>
    `;

    // Par défaut on charge 'EN_ATTENTE'
    await fetchAndRenderAvis('EN_ATTENTE', container);
}

window.filterAvis = async function(status, btn) {
    // Update active class
    const filters = btn.parentElement;
    if(filters) {
       filters.querySelectorAll('button').forEach(b => b.classList.remove('btn--primary', 'active'));
       btn.classList.add('btn--primary', 'active');
    }

    const container = document.getElementById('dashboard-content');
    container.innerHTML = '<div class="loader-container"><div class="loader"></div></div>';
    
    await fetchAndRenderAvis(status, container);
};

async function fetchAndRenderAvis(status, container) {
    try {
        const result = await AvisService.getAvis(status);
        let reviews = [];
        if (result && result.data) {
           reviews = result.data;
        } else if (Array.isArray(result)) {
           reviews = result;
        }

        if (reviews.length === 0) {
            container.innerHTML = `<div class="empty-state">Aucun avis avec le statut : ${status}</div>`;
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th class="data-table__th--actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        html += reviews.map(avis => {
            const dateStr = avis.date_creation 
                ? new Date(avis.date_creation).toLocaleDateString('fr-FR')
                : 'N/A';
            const noteHtml = renderStars(avis.note);
            const safeComment = escapeHtml(avis.commentaire || '');
            
            return `
                <tr>
                    <td>${dateStr}</td>
                    <td>${noteHtml}</td>
                    <td><p class="text-truncate" title="${safeComment}">${safeComment}</p></td>
                    <td>
                        ${renderAvisActions(avis, status)}
                    </td>
                </tr>
            `;
        }).join('');

        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        container.innerHTML = html;

        bindAvisActions();

    } catch (error) {
        console.error("Erreur chargement avis", error);
        container.innerHTML = `<div class="error-banner">Erreur lors du chargement des avis.</div>`;
    }
}

function renderStars(note) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= note) stars += '<i class="fa-solid fa-star text-warning"></i>';
        else stars += '<i class="fa-regular fa-star text-muted"></i>';
    }
    return stars;
}

function renderAvisActions(avis, currentStatus) {
    if (currentStatus === 'EN_ATTENTE') {
        return `
            <div class="btn-group">
                <button class="btn btn--sm btn--success btn-validate-avis" data-id="${avis.id}" title="Valider">
                    <i class="fa-solid fa-check"></i>
                </button>
                <button class="btn btn--sm btn--danger btn-reject-avis" data-id="${avis.id}" title="Refuser">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `;
    }
    
    // Pour les avis déjà validés, on permet de les supprimer (refuser/annuler)
    return `
         <button class="btn btn--sm btn--danger btn-reject-avis" data-id="${avis.id}" title="Supprimer">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
}

function bindAvisActions() {
    document.querySelectorAll('.btn-validate-avis').forEach(btn => {
        btn.onclick = async () => {
             const id = btn.dataset.id;
             if(!confirm('Valider cet avis le rendra visible sur le site. Continuer ?')) return;
             
             try {
                 await AvisService.validateAvis(id);
                 // Reload click on active filter
                 const activeBtn = document.querySelector('#header-actions .filters .active');
                 if(activeBtn) activeBtn.click();
             } catch(e) {
                 alert('Erreur : ' + e.message);
             }
        };
    });

    document.querySelectorAll('.btn-reject-avis').forEach(btn => {
        btn.onclick = async () => {
             const id = btn.dataset.id;
             if(!confirm('Supprimer cet avis ?')) return;
             
             try {
                 await AvisService.deleteAvis(id);
                 const activeBtn = document.querySelector('#header-actions .filters .active');
                 if(activeBtn) activeBtn.click();
             } catch(e) {
                 alert('Erreur : ' + e.message);
             }
        };
    });
}

// --- View : Gestion de l'équipe ---

async function loadEquipeView(container, headerActions) {
    // Bouton Créer Employé
    headerActions.innerHTML = `<button class="btn btn--primary" id="btn-add-employee"><i class="fa-solid fa-user-plus"></i> Créer Employé</button>`;
    
    container.innerHTML = `
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom / Prénom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="equipe-table-body">
                    <tr><td colspan="5" class="data-table__cell--center">Chargement...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Hidden Modal for Employee Creation -->
        <div id="modal-employee" class="modal-overlay">
            <div class="modal">
                <div class="modal__header">
                    <h2 class="modal__title">Créer un Employé</h2>
                    <button class="modal__close" id="btn-close-employee-modal">&times;</button>
                </div>
                <form id="form-employee">
                    <div class="form-group">
                        <label for="emp-email">Email (Username) *</label>
                        <input type="email" id="emp-email" name="email" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="emp-password">Mot de passe *</label>
                        <input type="password" id="emp-password" name="password" required class="form-input" minlength="8">
                        <small>Ne sera pas envoyé par mail. Communiquer à l'employé.</small>
                    </div>
                    <div class="form-group">
                        <label for="emp-firstName">Prénom</label>
                        <input type="text" id="emp-firstName" name="firstName" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="emp-lastName">Nom</label>
                        <input type="text" id="emp-lastName" name="lastName" class="form-input">
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn--secondary" id="btn-cancel-employee">Annuler</button>
                        <button type="submit" class="btn btn--primary">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // Events Modal
    const modal = document.getElementById('modal-employee');
    const form = document.getElementById('form-employee');

    document.getElementById('btn-add-employee').addEventListener('click', () => {
        form.reset();
        modal.classList.add('is-visible');
    });

    const closeModal = () => modal.classList.remove('is-visible');
    document.getElementById('btn-close-employee-modal').addEventListener('click', closeModal);
    document.getElementById('btn-cancel-employee').addEventListener('click', closeModal);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            await AdminService.createEmployee(data);
            alert('Compte employé créé avec succès. Un email a été envoyé.');
            closeModal();
            fetchEquipeList(); // Refresh list
        } catch (error) {
            alert('Erreur: ' + error.message);
        }
    });

    await fetchEquipeList();
}

async function fetchEquipeList() {
    const tbody = document.getElementById('equipe-table-body');
    try {
        const users = await AdminService.getEmployees();
        tbody.innerHTML = '';

        if (users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="data-table__cell--center">Aucun employé trouvé.</td></tr>';
            return;
        }

        users.forEach(user => {
            const isActif = user.actif == 1;
            const tr = document.createElement('tr');
            
            let actionBtn = '';
            if (isActif) {
                // Bouton désactiver seulement si actif
                actionBtn = `<button class="btn btn--sm btn--danger btn-disable-user" data-id="${user.id}">
                                <i class="fa-solid fa-ban"></i> Désactiver
                             </button>`;
            } else {
                 actionBtn = `<span class="badge badge--danger">Désactivé</span>`;
            }

            tr.innerHTML = `
                <td data-label="Nom">${escapeHtml(user.nom)} ${escapeHtml(user.prenom)}</td>
                <td data-label="Email">${escapeHtml(user.email)}</td>
                <td data-label="Rôle"><span class="badge badge--info">${escapeHtml(user.role)}</span></td>
                <td data-label="Statut">${isActif ? '<span class="badge badge--success">Actif</span>' : '<span class="badge badge--secondary">Inactif</span>'}</td>
                <td data-label="Actions">${actionBtn}</td>
            `;
            tbody.appendChild(tr);
        });

        // Event listener pour désactivation
        document.querySelectorAll('.btn-disable-user').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (confirm('Voulez-vous vraiment désactiver ce compte employé ? Il ne pourra plus se connecter.')) {
                    try {
                        await AdminService.disableUser(btn.dataset.id);
                        fetchEquipeList();
                    } catch (e) {
                        alert("Erreur: " + e.message);
                    }
                }
            });
        });

    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="5" class="data-table__cell--error">${escapeHtml(error.message || 'Erreur')}</td></tr>`;
    }
}

// --- View : Statistiques ---

async function loadStatsView(container, headerActions) {
    headerActions.innerHTML = '';
    
    container.innerHTML = `
        <div class="stats-controls">
            <div class="form-group">
                <label>Date Début</label>
                <input type="date" id="stats-start" class="form-input">
            </div>
            <div class="form-group">
                <label>Date Fin</label>
                <input type="date" id="stats-end" class="form-input">
            </div>
            <button class="btn btn--primary" id="btn-refresh-stats">Filtrer</button>
        </div>

        <div>
            <!-- Tableau CA -->
            <div class="card">
                <h3>Chiffre d'Affaires par Menu</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Menu ID</th>
                                <th>Commandes</th>
                                <th>Pers. Total</th>
                                <th>C.A. (€)</th>
                            </tr>
                        </thead>
                        <tbody id="stats-table-body"></tbody>
                    </table>
                </div>
            </div>

            <!-- Graphique -->
            <div class="card">
                 <h3>Chiffre d'Affaires et Commandes</h3>
                 <canvas id="statsChart"></canvas>
            </div>
        </div>
    `;

    document.getElementById('btn-refresh-stats').addEventListener('click', () => {
        fetchStatsData();
    });

    // Load initial data
    fetchStatsData();
}

let statsChartInstance = null;

async function fetchStatsData() {
    const startDate = document.getElementById('stats-start').value;
    const endDate = document.getElementById('stats-end').value;
    
    try {
        const stats = await AdminService.getStats({ startDate, endDate });
        
        // 1. Update Table
        const tbody = document.getElementById('stats-table-body');
        tbody.innerHTML = '';
        
        if (stats.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="data-table__cell--center">Aucune donnée (MongoDB)</td></tr>';
        } else {
            stats.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${item.menuId}</td>
                    <td>${item.totalCommandes}</td>
                    <td>${item.nombrePersonnesTotal}</td>
                    <td><strong>${item.chiffreAffaires.toFixed(2)} €</strong></td>
                `;
                tbody.appendChild(tr);
            });
        }

        // 2. Update Chart
        const ctx = document.getElementById('statsChart').getContext('2d');
        
        const labels = stats.map(item => `Menu #${item.menuId}`);
        const dataCA = stats.map(item => item.chiffreAffaires);
        const dataCount = stats.map(item => item.totalCommandes);

        if (statsChartInstance) {
            statsChartInstance.destroy();
        }

        statsChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Chiffre d\'Affaires (€)',
                        data: dataCA,
                        backgroundColor: 'rgba(46, 204, 113, 0.6)', // Vert
                        borderColor: 'rgba(39, 174, 96, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Nombre de Commandes',
                        data: dataCount,
                        backgroundColor: 'rgba(52, 152, 219, 0.6)', // Bleu
                        borderColor: 'rgba(41, 128, 185, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Chiffre d\'Affaires (€)' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Commandes' },
                        grid: {
                            drawOnChartArea: false, // only want the grid lines for one axis to show up
                        },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

    } catch (e) {
        console.error("Erreur stats:", e);
    }
}
