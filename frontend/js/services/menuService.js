
/**
 * Service de gestion des menus
 * Communique avec l'API backend pour les opérations liées aux menus
 */
const MenuService = {
    
    /**
     * URL de base pour les menus
     */
    API_URL: '/api/menus',

    /**
     * Gestionnaire de réponse générique
     * @param {Response} response 
     * @returns {Promise<any>}
     */
    async _handleResponse(response) {
        // Redirection si session expirée (401)
        if (response.status === 401) {
            window.location.href = '/connexion.html?error=session_expired';
            throw new Error('Session expirée');
        }

        // Accès refusé (403)
        if (response.status === 403) {
            throw new Error('Accès refusé. Vous n\'avez pas les droits nécessaires.');
        }

        // Si status 204 (No Content), on retourne null sans parser le JSON
        if (response.status === 204) {
            return null;
        }

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || data.message || 'Une erreur est survenue');
        }

        return data;
    },

    /**
     * Récupère la liste des menus avec filtres optionnels
     * @param {Object} filters - { prix_max, theme, regime, nb_personnes }
     * @returns {Promise<Array>}
     */
    async getMenus(filters = {}) {
        // Construction de la Query String (ex: ?prix_max=20&theme=1)
        const params = new URLSearchParams();
        
        Object.keys(filters).forEach(key => {
            if (filters[key] !== null && filters[key] !== undefined && filters[key] !== '') {
                params.append(key, filters[key]);
            }
        });

        try {
            const response = await fetch(`${this.API_URL}?${params.toString()}`, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            return this._handleResponse(response);
        } catch (error) {
            console.error('Erreur getMenus:', error);
            throw error;
        }
    },

    /**
     * Récupère les détails complets d'un menu
     * @param {number} id 
     * @returns {Promise<Object>}
     */
    async getMenuDetails(id) {
        try {
            const response = await fetch(`${this.API_URL}/${id}`, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            return this._handleResponse(response);
        } catch (error) {
            console.error(`Erreur getMenuDetails(${id}):`, error);
            throw error;
        }
    },

    /**
     * Crée un nouveau menu (Nécessite droits EMPLOYE/ADMIN)
     * @param {Object} menuData 
     * @returns {Promise<Object>}
     */
    async createMenu(menuData) {
        try {
            const response = await fetch(this.API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include', // Important pour envoyer le cookie auth
                body: JSON.stringify(menuData)
            });
            return this._handleResponse(response);
        } catch (error) {
            console.error('Erreur createMenu:', error);
            throw error;
        }
    },

    /**
     * Met à jour un menu existant (Nécessite droits EMPLOYE/ADMIN)
     * @param {number} id 
     * @param {Object} menuData 
     * @returns {Promise<Object>}
     */
    async updateMenu(id, menuData) {
        try {
            const response = await fetch(`${this.API_URL}/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(menuData)
            });
            return this._handleResponse(response);
        } catch (error) {
            console.error(`Erreur updateMenu(${id}):`, error);
            throw error;
        }
    },

    /**
     * Supprime un menu (Nécessite droits EMPLOYE/ADMIN)
     * @param {number} id 
     * @returns {Promise<Object>}
     */
    async deleteMenu(id) {
        try {
            const response = await fetch(`${this.API_URL}/${id}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include'
            });
            return this._handleResponse(response);
        } catch (error) {
            console.error(`Erreur deleteMenu(${id}):`, error);
            throw error;
        }
    },

    /**
     * Récupère la liste des thèmes disponibles
     * @returns {Promise<Array>}
     */
    async getThemes() {
        try {
            const response = await fetch(`${this.API_URL}/themes`, {
                method: 'GET'
            });
            return this._handleResponse(response);
        } catch (error) {
            console.error('Erreur getThemes:', error);
            throw error;
        }
    },

    /**
     * Récupère la liste des régimes disponibles
     * @returns {Promise<Array>}
     */
    async getRegimes() {
        try {
            const response = await fetch(`${this.API_URL}/regimes`, {
                method: 'GET'
            });
            return this._handleResponse(response);
        } catch (error) {
            console.error('Erreur getRegimes:', error);
            throw error;
        }
    },

    /**
     * Récupère la liste du matériel disponible pour configuration
     * @returns {Promise<Array>}
     */
    async getMaterials() {
        try {
            // Utilise l'endpoint global ou celui des menus si imbriqué
            const response = await fetch(`/api/materiels`, {
                method: 'GET',
                credentials: 'include'
            });
            
            // Si l'endpoint principal '/api/materiels' n'est pas encore ouvert publiquement, 
            // on pourrait devoir passer par un endpoint authentifié.
            // On suppose ici que le backend expose GET /api/materiels (vérifié dans routes.php ?)
            
            // Note: Dans une architecture précédente, c'était peut-être restreint. 
            // On s'assure de gérer la réponse standard.
            return this._handleResponse(response);
        } catch (error) {
            console.error('Erreur getMaterials:', error);
            throw error;
        }
    }
};

// Export global pour utilisation simple dans les pages HTML
window.MenuService = MenuService;
