/**
 * Dashboard — Module Gestion des Horaires
 * Gère l'onglet Horaires du dashboard admin (update-only, 7 jours fixes).
 */

const JOURS_FR = {
    'LUNDI': 'Lundi',
    'MARDI': 'Mardi',
    'MERCREDI': 'Mercredi',
    'JEUDI': 'Jeudi',
    'VENDREDI': 'Vendredi',
    'SAMEDI': 'Samedi',
    'DIMANCHE': 'Dimanche'
};

/**
 * Charge la vue de gestion des horaires.
 * @param {HTMLElement} container - Conteneur principal
 * @param {HTMLElement} headerActions - Zone d'actions du header
 */
async function loadHorairesView(container, headerActions) {
    headerActions.innerHTML = '';

    container.innerHTML = `
        <p class="horaires-intro">Modifiez les horaires d'ouverture de l'entreprise. Les 7 jours de la semaine sont pré-définis.</p>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Jour</th>
                        <th>Ouverture</th>
                        <th>Fermeture</th>
                        <th>Fermé</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="horaires-table-body">
                    <tr><td colspan="5" class="data-table__cell--center">Chargement...</td></tr>
                </tbody>
            </table>
        </div>
    `;

    try {
        await fetchHorairesList();
    } catch (error) {
        Logger.error("Erreur chargement horaires:", error);
        container.innerHTML += '<p class="error-text">Erreur lors du chargement des horaires.</p>';
    }
}

/**
 * Récupère et affiche la liste des horaires.
 */
async function fetchHorairesList() {
    const response = await fetch('/api/horaires', { credentials: 'include' });
    if (!response.ok) throw new Error('Erreur API horaires');
    const result = await response.json();
    const horaires = result.data || [];

    const tbody = document.getElementById('horaires-table-body');
    tbody.innerHTML = '';

    if (horaires.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="data-table__cell--center">Aucun horaire trouvé.</td></tr>';
        return;
    }

    horaires.forEach(h => {
        const tr = document.createElement('tr');
        tr.dataset.id = h.id;
        tr.classList.toggle('horaire-row--ferme', h.ferme);

        tr.innerHTML = `
            <td class="u-text-bold">${JOURS_FR[h.jour] || h.jour}</td>
            <td>
                <input type="time" class="input input--sm horaire-input" data-field="heureOuverture" 
                       value="${h.heureOuverture ? h.heureOuverture.substring(0, 5) : ''}" 
                       ${h.ferme ? 'disabled' : ''}>
            </td>
            <td>
                <input type="time" class="input input--sm horaire-input" data-field="heureFermeture" 
                       value="${h.heureFermeture ? h.heureFermeture.substring(0, 5) : ''}" 
                       ${h.ferme ? 'disabled' : ''}>
            </td>
            <td>
                <label class="switch">
                    <input type="checkbox" class="horaire-ferme-toggle" ${h.ferme ? 'checked' : ''}>
                    <span class="switch__label">${h.ferme ? 'Oui' : 'Non'}</span>
                </label>
            </td>
            <td>
                <button class="btn btn--sm btn--primary btn-save-horaire" data-id="${h.id}">
                    <i class="fa-solid fa-floppy-disk"></i> Sauver
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    // Toggle fermé : active/désactive les champs time
    document.querySelectorAll('.horaire-ferme-toggle').forEach(toggle => {
        toggle.addEventListener('change', (e) => {
            const row = e.target.closest('tr');
            const inputs = row.querySelectorAll('.horaire-input');
            const label = row.querySelector('.switch__label');
            inputs.forEach(input => {
                input.disabled = e.target.checked;
                if (e.target.checked) input.value = '';
            });
            label.textContent = e.target.checked ? 'Oui' : 'Non';
            row.classList.toggle('horaire-row--ferme', e.target.checked);
        });
    });

    // Boutons sauvegarder
    document.querySelectorAll('.btn-save-horaire').forEach(btn => {
        btn.addEventListener('click', () => saveHoraire(btn.dataset.id));
    });
}

/**
 * Sauvegarde les modifications d'un horaire.
 * @param {number} id
 */
async function saveHoraire(id) {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (!row) return;

    const ferme = row.querySelector('.horaire-ferme-toggle').checked;
    const heureOuverture = row.querySelector('[data-field="heureOuverture"]').value;
    const heureFermeture = row.querySelector('[data-field="heureFermeture"]').value;

    const data = { ferme };
    if (!ferme) {
        data.heureOuverture = heureOuverture;
        data.heureFermeture = heureFermeture;
    }

    try {
        const response = await fetch(`/api/horaires/${id}`, AuthService.getFetchOptions({
            method: 'PUT',
            body: JSON.stringify(data)
        }));

        const result = await response.json();

        if (!response.ok) {
            const errMsg = result.errors
                ? Object.values(result.errors).join(', ')
                : result.error || 'Erreur lors de la sauvegarde.';
            showToast(escapeHtml(errMsg), 'error');
            return;
        }

        showToast('Horaire mis à jour.', 'success');
    } catch (err) {
        showToast('Erreur réseau.', 'error');
    }
}
