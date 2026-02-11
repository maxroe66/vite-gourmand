/**
 * Service de gestion des plats
 * Communique avec l'API backend pour les opérations liées aux plats
 */
const PlatService = {
    
    API_URL: '/api/plats',

    async _handleResponse(response) {
        if (response.status === 401) {
            window.location.href = '/connexion.html?error=session_expired';
            throw new Error('Session expirée');
        }
        if (response.status === 403) {
            throw new Error('Accès refusé.');
        }

        // 204 No Content
        if (response.status === 204) {
             return null;
        }

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || data.message || 'Erreur inconnue');
        }
        return data;
    },

    /**
     * Récupère tous les plats
     */
    async getPlats() {
        try {
            const response = await fetch(this.API_URL);
            return this._handleResponse(response);
        } catch (error) {
            console.error('Erreur getPlats:', error);
            throw error;
        }
    },

    /**
     * Récupère les détails d'un plat (avec allergènes)
     */
    async getPlatDetails(id) {
        try {
            const response = await fetch(`${this.API_URL}/${id}`);
            return this._handleResponse(response);
        } catch (error) {
            console.error(`Erreur getPlatDetails(${id}):`, error);
            throw error;
        }
    },

    /**
     * Crée un nouveau plat
     * @param {Object} data { libelle, description, type, allergenIds: [] }
     */
    async createPlat(data) {
        try {
            const response = await fetch(this.API_URL, {
                method: 'POST',
                headers: AuthService.addCsrfHeader({ 'Content-Type': 'application/json' }),
                credentials: 'include',
                body: JSON.stringify(data)
            });
            return this._handleResponse(response);
        } catch (error) {
            console.error('Erreur createPlat:', error);
            throw error;
        }
    },

    /**
     * Met à jour un plat
     */
    async updatePlat(id, data) {
        try {
            const response = await fetch(`${this.API_URL}/${id}`, {
                method: 'PUT',
                headers: AuthService.addCsrfHeader({ 'Content-Type': 'application/json' }),
                credentials: 'include',
                body: JSON.stringify(data)
            });
            return this._handleResponse(response);
        } catch (error) {
            console.error('Erreur updatePlat:', error);
            throw error;
        }
    },

    /**
     * Supprime un plat
     */
    async deletePlat(id) {
        try {
            const response = await fetch(`${this.API_URL}/${id}`, {
                method: 'DELETE',
                headers: AuthService.addCsrfHeader(),
                credentials: 'include'
            });
            return this._handleResponse(response);
        } catch (error) {
            console.error('Erreur deletePlat:', error);
            throw error;
        }
    },

    /**
     * Récupère la liste des allergènes
     */
    async getAllergenes() {
        try {
            const response = await fetch(`${this.API_URL}/allergenes`);
            return this._handleResponse(response);
        } catch (error) {
            console.error('Erreur getAllergenes:', error);
            throw error;
        }
    }
};

window.PlatService = PlatService;
