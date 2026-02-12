/**
 * Dashboard — Module Gestion des Commandes
 * Gère l'onglet Commandes du dashboard admin (liste, détails, changement de statut, annulation, retour matériel).
 */

// Shared state: map of commandes by ID for quick lookup
let commandesById = new Map();

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
