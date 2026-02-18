document.addEventListener('DOMContentLoaded', async () => {
    const ordersList = document.getElementById('orders-list');
    const ordersLoader = document.getElementById('orders-loader');
    const modal = document.getElementById('order-detail-modal');
    const modalBody = document.getElementById('modal-body');
    const closeModal = document.getElementById('close-modal');

    // Modal Avis
    const modalAvis = document.getElementById('modal-avis');
    const closeAvis = document.getElementById('close-avis');
    const formAvis = document.getElementById('form-avis');

    // Tabs navigation
    const tabBtns = document.querySelectorAll('.tabs__btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('is-active'));
            tabContents.forEach(c => c.classList.remove('is-visible'));
            btn.classList.add('is-active');
            const target = document.getElementById('tab-' + btn.dataset.tab);
            if (target) target.classList.add('is-visible');
        });
    });

    // Afficher des skeletons de chargement
    Skeleton.renderCards(ordersList, 3);
    ordersLoader.classList.add('u-hidden');

    // Init
    let currentUser = null;
    try {
        currentUser = await AuthService.getUser();
        if (!currentUser) {
            window.location.href = '/frontend/pages/connexion.html';
            return;
        }
        fillProfileForm(currentUser);
        await loadOrders();
        checkQueryOrderParam();
    } catch (e) {
        Logger.error(e);
    }

    // --- PROFIL ---
    function fillProfileForm(user) {
        document.getElementById('profile-firstName').value = user.prenom || '';
        document.getElementById('profile-lastName').value = user.nom || '';
        document.getElementById('profile-email').value = user.email || '';
        document.getElementById('profile-phone').value = user.gsm || '';
        document.getElementById('profile-address').value = user.adresse_postale || '';
        document.getElementById('profile-city').value = user.ville || '';
        document.getElementById('profile-postalCode').value = user.code_postal || '';
    }

    const formProfile = document.getElementById('form-profile');
    formProfile.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
            firstName: document.getElementById('profile-firstName').value.trim(),
            lastName: document.getElementById('profile-lastName').value.trim(),
            phone: document.getElementById('profile-phone').value.trim(),
            address: document.getElementById('profile-address').value.trim(),
            city: document.getElementById('profile-city').value.trim(),
            postalCode: document.getElementById('profile-postalCode').value.trim()
        };

        try {
            const result = await AuthService.updateProfile(data);
            if (result.ok) {
                showToast('Profil mis à jour avec succès.', 'success');
                if (result.data.user) {
                    fillProfileForm(result.data.user);
                }
            } else {
                const msg = result.data?.message || 'Erreur lors de la mise à jour.';
                showToast(escapeHtml(msg), 'error');
            }
        } catch (err) {
            showToast('Erreur réseau. Réessayez plus tard.', 'error');
        }
    });

    // Load Orders
    async function loadOrders() {
        try {
            const orders = await CommandeService.getMyOrders();
            renderOrders(orders);
        } catch (e) {
            ordersList.innerHTML = `<p class="error-text">Erreur: ${escapeHtml(e.message)}</p>`;
        } finally {
            ordersLoader.classList.add('u-hidden');
            Skeleton.clear(ordersList);
            Skeleton.staggerChildren(ordersList, '.order-card');
        }
    }

    // Si l'URL contient ?orderId=123, ouvrir le détail et éventuellement la modale d'avis
    async function checkQueryOrderParam() {
        const params = new URLSearchParams(window.location.search);
        const orderId = params.get('orderId');
        if (!orderId) return;

        try {
            const data = await CommandeService.getOrder(orderId);
            const commande = data.commande || data;
            if (commande.statut === 'TERMINEE' && !commande.hasAvis) {
                // Ouvrir directement la modale avis
                openAvisModal(orderId);
                // Scroll to modal
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                // Ouvrir le détail de la commande
                showDetail(orderId);
            }
        } catch (e) {
            Logger.error('Param orderId present but impossible de charger la commande', e);
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
                        `<button class="button button--danger button--sm btn-cancel" data-id="${order.id}">Annuler</button>
                         <button class="button button--sm btn-edit-order" data-id="${order.id}">Modifier</button>` 
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

        document.querySelectorAll('.btn-edit-order').forEach(btn => {
            btn.addEventListener('click', () => openEditOrderModal(btn.dataset.id));
        });
    }

    // --- MODALE EDITION COMMANDE ---
    const modalEditOrder = document.getElementById('modal-edit-order');
    const closeEditOrder = document.getElementById('close-edit-order');
    const formEditOrder = document.getElementById('form-edit-order');

    async function openEditOrderModal(orderId) {
        // Récupérer les infos de la commande
        try {
            const data = await CommandeService.getOrder(orderId);
            const commande = data.commande || data; // selon structure API
            document.getElementById('edit-order-id').value = commande.id;
            document.getElementById('edit-adresse').value = commande.adresseLivraison || '';
            document.getElementById('edit-ville').value = commande.ville || '';
            document.getElementById('edit-cp').value = commande.codePostal || '';
            document.getElementById('edit-nb-personnes').value = commande.nombrePersonnes || 1;
            // Date de livraison (format yyyy-mm-dd)
            if (commande.datePrestation) {
                const d = new Date(commande.datePrestation);
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                document.getElementById('edit-date-prestation').value = `${yyyy}-${mm}-${dd}`;
            } else {
                document.getElementById('edit-date-prestation').value = '';
            }
            // Afficher la modale
            modalEditOrder.classList.add('is-visible');
        } catch (e) {
            showToast('Impossible de charger la commande à modifier.', 'error');
        }
    }

    closeEditOrder.addEventListener('click', () => {
        modalEditOrder.classList.remove('is-visible');
    });

    formEditOrder.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('edit-order-id').value;
        const data = {
            adresseLivraison: document.getElementById('edit-adresse').value,
            ville: document.getElementById('edit-ville').value,
            codePostal: document.getElementById('edit-cp').value,
            nombrePersonnes: parseInt(document.getElementById('edit-nb-personnes').value, 10),
            datePrestation: document.getElementById('edit-date-prestation').value
        };
        try {
            await CommandeService.updateOrder(id, data);
            showToast('Commande modifiée avec succès.', 'success');
            modalEditOrder.classList.remove('is-visible');
            loadOrders();
        } catch (err) {
            showToast(escapeHtml(err.message || 'Erreur lors de la modification.'), 'error');
        }
    });

    function openAvisModal(id) {
        document.getElementById('avis-cmd-id').value = id;
        formAvis.reset(); 
        modalAvis.classList.add('is-visible');
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
            showToast('Merci ! Votre avis a été enregistré et sera publié après validation.', 'success');
            modalAvis.classList.remove('is-visible');
            loadOrders(); // Refresh to hide button
        } catch (err) {
            showToast(escapeHtml(err.message), 'error');
        }
    });

    closeAvis.addEventListener('click', () => modalAvis.classList.remove('is-visible'));

    // Modal detail: skeleton loading
    async function showDetail(orderId) {
        modal.classList.add('is-visible');
        modalBody.textContent = '';
        Skeleton.renderModalContent(modalBody);

        try {
            const data = await CommandeService.getOrder(orderId);
            const { commande, timeline, materiels } = data;

            const timelineHtml = timeline.map(t => `
                <div class="timeline-item">
                    <strong>${escapeHtml(t.statut)}</strong><br>
                    <small>${new Date(t.date).toLocaleString()}</small>
                    ${t.commentaire ? `<br><em>${escapeHtml(t.commentaire)}</em>` : ''}
                </div>
            `).join('');

            let materielHtml = '';
            if (materiels && materiels.length > 0) {
                materielHtml = '<h3>Matériel Prêté</h3><ul>' + materiels.map(m =>
                    `<li>${escapeHtml(m.libelle)} (x${m.quantite}) - Retour prévu : ${new Date(m.date_retour_prevu).toLocaleDateString()}</li>`
                ).join('') + '</ul>';
            }

            Skeleton.clear(modalBody);
            modalBody.innerHTML = `
                <div class="order-detail-view anim-fade-in-up">
                    <p><strong>Menu :</strong> Commande #${commande.id}</p>
                    <p><strong>Adresse :</strong> ${escapeHtml(commande.adresseLivraison)}, ${escapeHtml(commande.codePostal)} ${escapeHtml(commande.ville)}</p>
                    <p><strong>Prix Total :</strong> ${formatPrice(commande.prixTotal)}</p>
                    ${materielHtml}
                    <h3>Historique</h3>
                    <div class="timeline-container">
                        ${timelineHtml}
                    </div>
                </div>
            `;
        } catch (e) {
            Skeleton.clear(modalBody);
            modalBody.innerHTML = '<p class="error-text">Impossible de charger le détail.</p>';
        }
    }

    async function cancelOrder(id) {
        if (!confirm("Êtes-vous sûr de vouloir annuler cette commande ?")) return;
        
        try {
            await CommandeService.cancelOrder(id);
            showToast('Commande annulée.', 'success');
            loadOrders(); // Refresh
        } catch (e) {
            showToast(escapeHtml(e.message), 'error');
        }
    }

    // Modal behavior
    closeModal.addEventListener('click', () => {
        modal.classList.remove('is-visible');
    });
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.remove('is-visible');
        if (e.target === modalAvis) modalAvis.classList.remove('is-visible');
    });


});
