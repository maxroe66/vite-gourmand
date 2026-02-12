/**
 * Dashboard — Module Gestion des Menus
 * Gère l'onglet Menus du dashboard admin (CRUD, modal, images, plats, matériel).
 */

// Global state for menu modal
let isMenuModalMsgInit = false;

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
            <tr><td colspan="4" class="data-table__cell--error">Erreur de chargement: ${escapeHtml(error.message)}</td></tr>
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
            if (!container) return;

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

        const selectedMap = new Map();
        selectedMaterials.forEach(m => selectedMap.set(m.id_materiel, m.quantite));

        materiels.forEach(mat => {
            const isSelected = selectedMap.has(mat.id_materiel);
            const qty = selectedMap.get(mat.id_materiel) || 1;

            const div = document.createElement('div');
            div.className = 'materiel-item';

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
            
            document.getElementById('menu-id').value = menu.id_menu;
            document.getElementById('menu-titre').value = menu.titre;
            document.getElementById('menu-description').value = menu.description || '';
            document.getElementById('menu-prix').value = menu.prix;
            document.getElementById('menu-nb-pers').value = menu.nombre_personne_min;
            document.getElementById('menu-stock').value = menu.stock_disponible || menu.stock;
            
            document.getElementById('menu-theme').value = menu.id_theme;
            document.getElementById('menu-regime').value = menu.id_regime;

            const existingDishIds = (menu.plats || []).map(p => p.id_plat);
            await loadMenuDishesSelectors(existingDishIds);
            await loadMenuMaterialSelectors(menu.materiels || []);

            const imgContainer = document.getElementById('menu-images-list');
            imgContainer.innerHTML = '';
            if (menu.images && menu.images.length > 0) {
                menu.images.forEach(url => addImageInput(url));
            } else {
                addImageInput();
            }

        } catch (error) {
            console.error("Erreur chargement menu", error);
            alert("Impossible de charger les détails du menu.");
            return;
        }

    } else {
        document.getElementById('modal-menu-title').textContent = 'Nouveau Menu';
        document.getElementById('menu-stock').value = 10;
        document.getElementById('menu-nb-pers').value = 2;
        await loadMenuDishesSelectors([]);
        await loadMenuMaterialSelectors([]);
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
    
    const selectedPlats = [];
    document.querySelectorAll('input[name="menu_plats"]:checked').forEach(cb => {
        selectedPlats.push(parseInt(cb.value));
    });

    const selectedMaterials = [];
    const matList = document.getElementById('menu-materiel-list');
    if (matList) {
        matList.querySelectorAll('.mat-check:checked').forEach(cb => {
            const row = cb.closest('label').parentElement;
            const qtyInput = row.querySelector('.mat-qty');
            
            selectedMaterials.push({
                id: parseInt(cb.value),
                quantite: parseInt(qtyInput.value) || 1
            });
        });
    }

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
        plats: selectedPlats,
        materiels: selectedMaterials,
        images: images
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
