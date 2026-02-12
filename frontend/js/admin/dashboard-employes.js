/**
 * Dashboard — Module Gestion de l'Équipe
 * Gère l'onglet Équipe du dashboard admin (liste des employés, création, désactivation).
 */

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
            showToast('Compte employé créé avec succès. Un email a été envoyé.', 'success');
            closeModal();
            fetchEquipeList(); // Refresh list
        } catch (error) {
            showToast(escapeHtml(error.message), 'error');
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
                        showToast(escapeHtml(e.message), 'error');
                    }
                }
            });
        });

    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="5" class="data-table__cell--error">${escapeHtml(error.message || 'Erreur')}</td></tr>`;
    }
}
