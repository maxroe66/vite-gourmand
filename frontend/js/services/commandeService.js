
const CommandeService = {
    API_URL: '/api/commandes',

    /**
     * Calcule le prix de la commande (Simulation)
     * @param {Object} data { menuId, nombrePersonnes, adresseLivraison }
     * @returns {Promise<Object>} Détails du prix
     */
    async calculatePrice(data) {
        // Authentification via Cookie HttpOnly, pas de token LocalStorage
        const headers = AuthService.addCsrfHeader({
            'Content-Type': 'application/json'
        });

        const response = await fetch(`${this.API_URL}/calculate-price`, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(data),
            credentials: 'include'
        });

        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || "Erreur lors du calcul du prix");
        }

        return await response.json();
    },

    /**
     * Crée une nouvelle commande
     * @param {Object} data Données du formulaire
     * @returns {Promise<Object>} Résultat { success, id }
     */
    async createOrder(data) {
        // Authentification via Cookie HttpOnly
        const response = await fetch(this.API_URL, {
            method: 'POST',
            headers: AuthService.addCsrfHeader({
                'Content-Type': 'application/json'
            }),
            body: JSON.stringify(data),
            credentials: 'include'
        });

        const result = await response.json();

        if (!response.ok) {
            // Gestion des erreurs de validation (array)
            if (result.errors) {
                const msg = Object.values(result.errors).join('\n');
                throw new Error(msg);
            }
            throw new Error(result.error || "Erreur lors de la création de la commande");
        }

        return result;
    },

    /**
     * Récupère mes commandes
     */
    async getMyOrders() {
        const response = await fetch('/api/my-orders', {
            credentials: 'include'
        });

        if (!response.ok) throw new Error("Impossible de récupérer les commandes");
        return await response.json();
    },

    /**
     * Récupère une commande par ID (avec timeline)
     */
    async getOrder(id) {
        const response = await fetch(`${this.API_URL}/${id}`, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error("Impossible de récupérer la commande");
        return await response.json();
    },

    /**
     * Annule une commande (Client)
     */
    async cancelOrder(id) {
        const response = await fetch(`${this.API_URL}/${id}`, {
            method: 'PATCH',
            headers: AuthService.addCsrfHeader({
                'Content-Type': 'application/json'
            }),
            body: JSON.stringify({ status: 'ANNULEE' }),
            credentials: 'include'
        });

        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || "Impossible d'annuler la commande");
        }

        return await response.json();
    },

    /**
     * Met à jour une commande (Client)
     * @param {number|string} id
     * @param {Object} data
     * @returns {Promise<Object>}
     */
    async updateOrder(id, data) {
        const response = await fetch(`${this.API_URL}/${id}`, {
            method: 'PATCH',
            headers: AuthService.addCsrfHeader({
                'Content-Type': 'application/json'
            }),
            body: JSON.stringify(data),
            credentials: 'include'
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || 'Erreur lors de la modification de la commande');
        }
        return result;
    },

    /**
     * Récupère toutes les commandes (Admin/Employé)
     * @param {Object} filters { status, user, date }
     */
    async getAllOrders(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        const response = await fetch(`${this.API_URL}?${params}`, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error("Impossible de récupérer les commandes");
        return await response.json();
    },

    /**
     * Met à jour le statut d'une commande (Admin/Employé)
     */
    async updateStatus(id, status, motif = null, modeContact = null) {
        const body = { status };
        if (motif) body.motif = motif;
        if (modeContact) body.modeContact = modeContact;

        const response = await fetch(`${this.API_URL}/${id}/status`, {
            method: 'PUT',
            headers: AuthService.addCsrfHeader({
                'Content-Type': 'application/json'
            }),
            body: JSON.stringify(body),
            credentials: 'include'
        });

        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || "Erreur mise à jour statut");
        }
        return await response.json();
    },

    /**
     * Valide le retour du matériel pour une commande
     * @param {number|string} id 
     * @returns {Promise<Object>}
     */
    async returnMaterial(id) {
        const response = await fetch(`${this.API_URL}/${id}/return-material`, {
            method: 'POST',
            headers: AuthService.addCsrfHeader({
                'Content-Type': 'application/json'
            }),
            credentials: 'include'
        });

        if (!response.ok) {
            const err = await response.json();
            // Gestion erreur spécifique stock
            throw new Error(err.error || "Erreur lors du retour matériel");
        }
        return await response.json();
    },

    /**
     * Vérifie les matériels en retard de retour.
     * Cas d'utilisation E7 : Vérifier retours matériels en retard.
     * @param {boolean} notify Si true, envoie des emails de relance
     * @returns {Promise<{count: number, overdueCommandes: Array, emailsSent: boolean}>}
     */
    async getOverdueMaterials(notify = false) {
        const params = notify ? '?notify=true' : '';
        const response = await fetch(`${this.API_URL}/overdue-materials${params}`, {
            credentials: 'include'
        });

        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || "Erreur vérification retards matériel");
        }
        return await response.json();
    }
};

window.CommandeService = CommandeService;
