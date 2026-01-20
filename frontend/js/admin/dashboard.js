
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
        document.querySelectorAll('.admin-only').forEach(el => el.style.display = 'block');
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
            contentEl.innerHTML = '<p>Liste des commandes (À implémenter)</p>';
            break;
        case 'avis':
            titleEl.textContent = 'Avis Clients';
            contentEl.innerHTML = '<p>Modération des avis (À implémenter)</p>';
            break;
        case 'equipe':
            titleEl.textContent = 'Gestion de l\'Équipe';
            contentEl.innerHTML = '<p>CRUD Utilisateurs (À implémenter)</p>';
            break;
        case 'stats':
            titleEl.textContent = 'Statistiques';
            contentEl.innerHTML = '<p>Graphiques et KPI (À implémenter)</p>';
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
                    <tr><td colspan="4" style="text-align:center;">Chargement...</td></tr>
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
            <tr><td colspan="4" style="color: red; text-align:center;">Erreur de chargement: ${error.message}</td></tr>
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
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Aucun menu trouvé.</td></tr>';
        return;
    }

    menus.forEach(menu => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td data-label="Titre">
                <div style="font-weight: bold;">${escapeHtml(menu.titre)}</div>
                <small style="color: #888;">${menu.description ? menu.description.substring(0, 50) + '...' : ''}</small>
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
    div.style.display = 'flex';
    div.style.gap = '0.5rem';
    div.style.alignItems = 'center';
    
    div.innerHTML = `
        <div style="flex:1; display:flex; gap:0.5rem;">
            <input type="text" name="menu_images[]" class="menu-image-input" placeholder="URL ou Upload..." value="${escapeHtml(value)}" style="flex:1;">
            <label class="btn btn--sm btn--secondary" style="margin:0; cursor:pointer;" title="Uploader depuis PC">
                <i class="fa-solid fa-upload"></i>
                <input type="file" class="file-upload-input" accept="image/*" style="display:none;">
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
            // Utilisation du token auth si besoin (authService devrait gérer, mais ici fetch brut)
            const token = localStorage.getItem('authToken'); // ou AuthService.getToken()
            
            const response = await fetch('http://localhost:8000/api/upload', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}` 
                },
                body: formData
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
            div.style.display = 'flex';
            div.style.alignItems = 'center';
            div.style.marginBottom = '4px';
            
            div.innerHTML = `
                <input type="checkbox" name="menu_plats" value="${plat.id_plat}" ${isChecked ? 'checked' : ''} style="margin-right: 6px;">
                <span title="${escapeHtml(plat.description || '')}">${escapeHtml(plat.libelle)}</span>
            `;
            container.appendChild(div);
        });
        
        // Empty message
        Object.values(containers).forEach(c => {
             if (c && c.children.length === 0) c.innerHTML = '<small style="color:#888; font-style:italic;">Aucun plat.</small>';
        });

    } catch (e) {
        console.error(e);
        Object.values(containers).forEach(c => c ? c.innerHTML = '<small style="color:red">Erreur chargement plats</small>' : null);
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
        // Reset images
        document.getElementById('menu-images-list').innerHTML = '';
        addImageInput();
    }

    modal.style.display = 'flex';
}

function closeMenuModal() {
    document.getElementById('modal-menu').style.display = 'none';
}

async function saveMenu() {
    const form = document.getElementById('form-menu');
    const formData = new FormData(form);
    
    // Récupérer les plats cochés
    const selectedPlats = [];
    document.querySelectorAll('input[name="menu_plats"]:checked').forEach(cb => {
        selectedPlats.push(parseInt(cb.value));
    });


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
                    <tr><td colspan="4" style="text-align:center;">Chargement...</td></tr>
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
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Aucun plat trouvé.</td></tr>';
        return;
    }

    // Mapping des types pour affichage propre
    const types = { 'ENTREE': 'Entrée', 'PLAT': 'Plat Principal', 'DESSERT': 'Dessert' };

    plats.forEach(plat => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="font-weight: bold;">${escapeHtml(plat.libelle)}</td>
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

    modal.style.display = 'flex';
}

function closePlatModal() {
    document.getElementById('modal-plat').style.display = 'none';
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
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; font-size: 0.9rem;">
                    <input type="checkbox" name="allergenes" value="${a.id_allergene}" id="allergene-${a.id_allergene}">
                    ${escapeHtml(a.libelle)}
                </label>
            `;
            container.appendChild(div);
        });
    } catch (e) {
        container.innerHTML = '<p style="color:red">Erreur chargement allergènes</p>';
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
