/**
 * Dashboard — Module Gestion des Plats
 * Gère l'onglet Plats du dashboard admin (CRUD, modal, allergènes).
 */

// Global flag for plat modal
let isPlatModalInit = false;

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
        Logger.error("Erreur chargement plats:", error);
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

    document.querySelectorAll('.btn-edit-plat').forEach(btn => {
        btn.addEventListener('click', () => openPlatModal(btn.dataset.id));
    });

    document.querySelectorAll('.btn-delete-plat').forEach(btn => {
        btn.addEventListener('click', () => deletePlat(btn.dataset.id));
    });
}

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

    await loadAllergenesCheckboxes();

    if (platId) {
        document.getElementById('modal-plat-title').textContent = 'Modifier le Plat';
        try {
            const plat = await PlatService.getPlatDetails(platId);
            
            document.getElementById('plat-id').value = plat.id_plat;
            document.getElementById('plat-libelle').value = plat.libelle;
            document.getElementById('plat-description').value = plat.description || '';
            document.getElementById('plat-type').value = plat.type;

            if (plat.allergenes) {
                plat.allergenes.forEach(a => {
                    const checkbox = document.getElementById(`allergene-${a.id_allergene}`);
                    if (checkbox) checkbox.checked = true;
                });
            }

        } catch (e) {
            Logger.error(e);
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
    if(container.children.length > 1) { 
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
