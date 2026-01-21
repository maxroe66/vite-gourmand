document.addEventListener('DOMContentLoaded', async () => {
    const ordersLoader = document.getElementById('orders-loader');
    const ordersList = document.getElementById('orders-list');
    const modal = document.getElementById('order-detail-modal');
    const modalBody = document.getElementById('modal-body');
    const closeModal = document.getElementById('close-modal');

    // Modal Avis
    const modalAvis = document.getElementById('modal-avis');
    const closeAvis = document.getElementById('close-avis');
    const formAvis = document.getElementById('form-avis');

    // Init
    try {
        const user = await AuthService.getUser();
        if (!user) {
            window.location.href = '/frontend/frontend/pages/connexion.html';
            return;
        }
        loadOrders();
    } catch (e) {
        console.error(e);
    }

    // Load Orders
    async function loadOrders() {
        try {
            const orders = await CommandeService.getMyOrders();
            renderOrders(orders);
        } catch (e) {
            ordersList.innerHTML = `<p class="error-text">Erreur: ${e.message}</p>`;
        } finally {
            ordersLoader.style.display = 'none';
        }
    }

    function renderOrders(orders) {
        if (!orders || orders.length === 0) {
            ordersList.innerHTML = "<p>Vous n'avez pas encore passé de commande.</p>";
            return;
        }

        ordersList.innerHTML = orders.map(order => `
            <div class="order-card">
                <div class="order-info">
                    <h3>Commande #${order.id}</h3>
                    <div class="order-meta">
                        Date: ${new Date(order.dateCommande).toLocaleDateString()}<br>
                        Livraison: ${new Date(order.datePrestation).toLocaleDateString()}<br>
                        Montant: <strong>${formatPrice(order.prixTotal)}</strong>
                    </div>
                </div>
                <div class="order-status">
                    <span class="badge badge-${order.statut.toLowerCase()}">${order.statut}</span>
                </div>
                <div class="action-group">
                    <button class="button button--secondary button--sm btn-detail" data-id="${order.id}">Détails</button>
                    ${order.statut === 'EN_ATTENTE' ? 
                        `<button class="button button--danger button--sm btn-cancel" data-id="${order.id}">Annuler</button>` 
                        : ''}
                    ${order.canReview ? 
                        `<button class="button button--primary button--sm btn-avis" data-id="${order.id}">Laisser un avis</button>`
                        : ''}
                </div>
            </div>
        `).join('');

        // Attach Events
        document.querySelectorAll('.btn-detail').forEach(btn => {
            btn.addEventListener('click', () => showDetail(btn.dataset.id));
        });

        document.querySelectorAll('.btn-cancel').forEach(btn => {
            btn.addEventListener('click', () => cancelOrder(btn.dataset.id));
        });

        document.querySelectorAll('.btn-avis').forEach(btn => {
            btn.addEventListener('click', () => openAvisModal(btn.dataset.id));
        });
    }

    function openAvisModal(id) {
        document.getElementById('avis-cmd-id').value = id;
        formAvis.reset(); 
        modalAvis.style.display = 'flex';
    }

    formAvis.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(formAvis);
        const data = {
            commandeId: fd.get('commandeId'),
            note: parseInt(fd.get('note')),
            commentaire: fd.get('commentaire')
        };
        
        try {
            await AvisService.createAvis(data);
            alert("Merci ! Votre avis a été enregistré et sera publié après validation.");
            modalAvis.style.display = 'none';
            loadOrders(); // Refresh to hide button
        } catch (err) {
            alert(err.message);
        }
    });

    closeAvis.addEventListener('click', () => modalAvis.style.display = 'none');

    async function showDetail(id) {
        modal.style.display = 'flex';
        modalBody.innerHTML = '<p>Chargement...</p>';
        
        try {
            const data = await CommandeService.getOrder(id);
            const { commande, timeline, materiels } = data;

            let timelineHtml = timeline.map(t => `
                <div class="timeline-item">
                    <strong>${t.statut}</strong><br>
                    <small>${new Date(t.date).toLocaleString()}</small>
                    ${t.commentaire ? `<br><em>${t.commentaire}</em>` : ''}
                </div>
            `).join('');
            
            let materielHtml = '';
            if (materiels && materiels.length > 0) {
                 materielHtml = '<h3>Matériel Prêté</h3><ul>' + materiels.map(m => 
                    `<li>${m.libelle} (x${m.quantite}) - Retour prévu : ${new Date(m.date_retour_prevu).toLocaleDateString()}</li>`
                 ).join('') + '</ul>';
            }

            modalBody.innerHTML = `
                <div class="order-detail-view">
                    <p><strong>Menu :</strong> Commande #${commande.id}</p>
                    <p><strong>Adresse :</strong> ${commande.adresseLivraison}, ${commande.codePostal} ${commande.ville}</p>
                    <p><strong>Prix Total :</strong> ${formatPrice(commande.prixTotal)}</p>
                    
                    ${materielHtml}

                    <h3>Historique</h3>
                    <div class="timeline-container">
                        ${timelineHtml}
                    </div>
                </div>
            `;

        } catch (e) {
            modalBody.innerHTML = `<p class="error-text">Impossible de charger le détail.</p>`;
        }
    }

    async function cancelOrder(id) {
        if (!confirm("Êtes-vous sûr de vouloir annuler cette commande ?")) return;
        
        try {
            await CommandeService.cancelOrder(id);
            alert("Commande annulée.");
            loadOrders(); // Refresh
        } catch (e) {
            alert(e.message);
        }
    }

    // Modal behavior
    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
        if (e.target === modalAvis) modalAvis.style.display = 'none';
    });

    function formatPrice(amount) {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
    }
});
