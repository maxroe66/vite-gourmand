/**
 * Dashboard — Module Gestion du Matériel
 * Gère l'onglet Matériel du dashboard admin (CRUD).
 */

let isMaterielModalInit = false;

/**
 * Charge la vue de gestion du matériel.
 * @param {HTMLElement} container - Conteneur principal
 * @param {HTMLElement} headerActions - Zone d'actions du header
 */
async function loadMaterielView(container, headerActions) {
    headerActions.innerHTML = `<button class="btn btn--primary" id="btn-add-materiel"><i class="fa-solid fa-plus"></i> Nouveau Matériel</button>`;

    container.innerHTML = `
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Libellé</th>
                        <th>Description</th>
                        <th>Valeur unitaire</th>
                        <th>Stock disponible</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="materiel-table-body">
                    <tr><td colspan="5" class="data-table__cell--center">Chargement...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Matériel -->
        <div class="modal" id="modal-materiel" aria-hidden="true">
            <div class="modal__dialog">
                <button class="modal__close" id="modal-materiel-close" aria-label="Fermer">&times;</button>
                <h2 id="modal-materiel-title">Nouveau Matériel</h2>
                <form id="form-materiel">
                    <input type="hidden" id="materiel-id" name="id">
                    
                    <div class="form-group">
                        <label for="materiel-libelle">Libellé *</label>
                        <input type="text" id="materiel-libelle" name="libelle" required maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="materiel-description">Description</label>
                        <textarea id="materiel-description" name="description" rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="materiel-valeur">Valeur unitaire (€) *</label>
                        <input type="number" id="materiel-valeur" name="valeur_unitaire" step="0.01" min="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="materiel-stock">Stock disponible *</label>
                        <input type="number" id="materiel-stock" name="stock_disponible" min="0" required>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn--ghost" id="modal-materiel-cancel">Annuler</button>
                        <button type="submit" class="btn btn--primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.getElementById('btn-add-materiel').addEventListener('click', () => {
        openMaterielModal();
    });

    try {
        await fetchMaterielList();
    } catch (error) {
        Logger.error("Erreur chargement matériel:", error);
    }

    initMaterielModalLogic();
}

/**
 * Récupère et affiche la liste du matériel.
 */
async function fetchMaterielList() {
    const materiels = await MenuService.getMaterials();
    const tbody = document.getElementById('materiel-table-body');
    tbody.innerHTML = '';

    if (!materiels || materiels.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="data-table__cell--center">Aucun matériel trouvé.</td></tr>';
        return;
    }

    materiels.forEach(mat => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="u-text-bold">${escapeHtml(mat.libelle || '')}</td>
            <td>${escapeHtml(mat.description || '-')}</td>
            <td>${formatPrice(parseFloat(mat.valeur_unitaire || 0))}</td>
            <td>${parseInt(mat.stock_disponible || 0)}</td>
            <td>
                <div class="data-table__actions">
                    <button class="btn btn--sm btn--outline-primary btn-edit-materiel" data-id="${mat.id_materiel}">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn btn--sm btn--outline-danger btn-delete-materiel" data-id="${mat.id_materiel}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.querySelectorAll('.btn-edit-materiel').forEach(btn => {
        btn.addEventListener('click', () => openMaterielModal(btn.dataset.id));
    });

    document.querySelectorAll('.btn-delete-materiel').forEach(btn => {
        btn.addEventListener('click', () => deleteMateriel(btn.dataset.id));
    });
}

/**
 * Ouvre le modal de création ou d'édition du matériel.
 * @param {number|null} id - ID du matériel à éditer (null pour création)
 */
async function openMaterielModal(id = null) {
    const modal = document.getElementById('modal-materiel');
    const title = document.getElementById('modal-materiel-title');
    const form = document.getElementById('form-materiel');

    form.reset();
    document.getElementById('materiel-id').value = '';

    if (id) {
        title.textContent = 'Modifier le Matériel';
        try {
            const response = await fetch(`/api/materiels/${id}`, {
                credentials: 'include'
            });
            if (!response.ok) throw new Error('Matériel introuvable');
            const mat = await response.json();

            document.getElementById('materiel-id').value = mat.id_materiel;
            document.getElementById('materiel-libelle').value = mat.libelle || '';
            document.getElementById('materiel-description').value = mat.description || '';
            document.getElementById('materiel-valeur').value = mat.valeur_unitaire || '';
            document.getElementById('materiel-stock').value = mat.stock_disponible || 0;
        } catch (e) {
            showToast('Impossible de charger le matériel.', 'error');
            return;
        }
    } else {
        title.textContent = 'Nouveau Matériel';
    }

    modal.classList.add('is-visible');
}

/**
 * Initialise la logique du modal (submit, cancel, close).
 */
function initMaterielModalLogic() {
    if (isMaterielModalInit) return;
    isMaterielModalInit = true;

    const modal = document.getElementById('modal-materiel');
    const form = document.getElementById('form-materiel');

    document.getElementById('modal-materiel-close').addEventListener('click', () => {
        modal.classList.remove('is-visible');
    });
    document.getElementById('modal-materiel-cancel').addEventListener('click', () => {
        modal.classList.remove('is-visible');
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('materiel-id').value;
        const data = {
            libelle: document.getElementById('materiel-libelle').value.trim(),
            description: document.getElementById('materiel-description').value.trim(),
            valeur_unitaire: parseFloat(document.getElementById('materiel-valeur').value),
            stock_disponible: parseInt(document.getElementById('materiel-stock').value, 10)
        };

        try {
            const isEdit = id && id !== '';
            const url = isEdit ? `/api/materiels/${id}` : '/api/materiels';
            const method = isEdit ? 'PUT' : 'POST';

            const response = await fetch(url, AuthService.getFetchOptions({
                method,
                body: JSON.stringify(data)
            }));

            const result = await response.json();

            if (!response.ok) {
                const errMsg = result.errors
                    ? Object.values(result.errors).join(', ')
                    : result.error || 'Erreur lors de l\'enregistrement.';
                showToast(escapeHtml(errMsg), 'error');
                return;
            }

            showToast(isEdit ? 'Matériel modifié.' : 'Matériel créé.', 'success');
            modal.classList.remove('is-visible');
            await fetchMaterielList();
        } catch (err) {
            showToast('Erreur réseau.', 'error');
        }
    });
}

/**
 * Supprime un matériel après confirmation.
 * @param {number} id
 */
async function deleteMateriel(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce matériel ?')) return;

    try {
        const response = await fetch(`/api/materiels/${id}`, AuthService.getFetchOptions({
            method: 'DELETE'
        }));

        const result = await response.json();

        if (!response.ok) {
            showToast(escapeHtml(result.error || 'Impossible de supprimer.'), 'error');
            return;
        }

        showToast('Matériel supprimé.', 'success');
        await fetchMaterielList();
    } catch (err) {
        showToast('Erreur réseau.', 'error');
    }
}
