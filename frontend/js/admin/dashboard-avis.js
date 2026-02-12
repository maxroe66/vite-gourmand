/**
 * Dashboard — Module Gestion des Avis
 * Gère l'onglet Avis du dashboard admin (modération, validation, suppression).
 */

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
