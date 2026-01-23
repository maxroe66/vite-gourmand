    
class CommandeService {
    static API_URL = '/api/commandes';

    /**
     * Calcule le prix de la commande (Simulation)
     * @param {Object} data { menuId, nombrePersonnes, adresseLivraison }
     * @returns {Promise<Object>} Détails du prix
     */
    static async calculatePrice(data) {
        // Authentification via Cookie HttpOnly, pas de token LocalStorage
        const headers = {
            'Content-Type': 'application/json'
        };

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
    }

    /**
     * Crée une nouvelle commande
     * @param {Object} data Données du formulaire
     * @returns {Promise<Object>} Résultat { success, id }
     */
    static async createOrder(data) {
        // Authentification via Cookie HttpOnly
        const response = await fetch(this.API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
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
    }

    /**
     * Récupère mes commandes
     */
    static async getMyOrders() {
        const response = await fetch('/api/my-orders', {
            credentials: 'include'
        });

        if (!response.ok) throw new Error("Impossible de récupérer les commandes");
        return await response.json();
    }

    /**
     * Récupère une commande par ID (avec timeline)
     */
    static async getOrder(id) {
        const response = await fetch(`${this.API_URL}/${id}`, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error("Impossible de récupérer la commande");
        return await response.json();
    }

    /**
     * Annule une commande (Client)
     */
    static async cancelOrder(id) {
        const response = await fetch(`${this.API_URL}/${id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ status: 'ANNULEE' }),
            credentials: 'include'
        });

        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || "Impossible d'annuler la commande");
        }

        return await response.json();
    }

    /**
     * Met à jour une commande (Client)
     * @param {number|string} id
     * @param {Object} data
     * @returns {Promise<Object>}
     */
    static async updateOrder(id, data) {
        const response = await fetch(`${this.API_URL}/${id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data),
            credentials: 'include'
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || 'Erreur lors de la modification de la commande');
        }
        return result;
    }

    /**
     * Récupère toutes les commandes (Admin/Employé)
     * @param {Object} filters { status, user, date }
     */
    static async getAllOrders(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        const response = await fetch(`${this.API_URL}?${params}`, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error("Impossible de récupérer les commandes");
        return await response.json();
    }

    /**
     * Met à jour le statut d'une commande (Admin/Employé)
     */
    static async updateStatus(id, status, motif = null, modeContact = null) {
        const body = { status };
        if (motif) body.motif = motif;
        if (modeContact) body.modeContact = modeContact;

        const response = await fetch(`${this.API_URL}/${id}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body),
            credentials: 'include'
        });

        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || "Erreur mise à jour statut");
        }
        return await response.json();
    }
}

window.CommandeService = CommandeService;
