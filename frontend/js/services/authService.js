/**
 * Service de gestion de l'authentification côté client
 * Utilise des cookies httpOnly gérés automatiquement par le navigateur
 */
const AuthService = {
        /**
         * Réinitialise le mot de passe avec le token et le nouveau mot de passe
         * @param {string} token
         * @param {string} password
         * @returns {Promise<{ok: boolean, status: number, data: object}>}
         */
        async resetPassword(token, password) {
            try {
                const response = await fetch('/api/auth/reset-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token, password }),
                    credentials: 'include'
                });
                const data = await response.json();
                return { ok: response.ok, status: response.status, data };
            } catch (error) {
                throw error;
            }
        },
        /**
         * Inscrit un nouvel utilisateur avec les données fournies
         * @param {Object} userData
         * @returns {Promise<{ok: boolean, status: number, data: object}>}
         */
        async register(userData) {
            try {
                const response = await fetch('/api/auth/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(userData),
                    credentials: 'include'
                });
                const data = await response.json();
                return { ok: response.ok, status: response.status, data };
            } catch (error) {
                throw error;
            }
        },
        /**
         * Tente de connecter l'utilisateur avec email et mot de passe
         * @param {string} email
         * @param {string} password
         * @returns {Promise<{ok: boolean, status: number, data: object}>}
         */
        async login(email, password) {
            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password }),
                    credentials: 'include'
                });
                const data = await response.json();
                return { ok: response.ok, status: response.status, data };
            } catch (error) {
                throw error;
            }
        },
    /**
     * Déconnecte l'utilisateur en appelant l'endpoint de logout
     * qui supprimera le cookie côté serveur
     */
    async logout() {
        try {
            const response = await fetch('/api/auth/logout', {
                method: 'POST',
                credentials: 'include'
            });
            if (!response.ok) {
                throw new Error(`Le serveur a répondu avec le statut ${response.status}`);
            }
            return true; // succès
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
            throw error; // laisse l'appelant gérer l'erreur
        }
    },

    /**
     * Retourne les options fetch standard avec credentials
     * @param {Object} options - Options fetch additionnelles
     * @returns {Object}
     */
    getFetchOptions(options = {}) {
        return {
            ...options,
            credentials: 'include',  // Envoie automatiquement les cookies
            headers: {
                'Content-Type': 'application/json',
                ...(options.headers || {})
            }
        };
    },

    /**
     * Vérifie si l'utilisateur est authentifié en appelant un endpoint protégé
     * @returns {Promise<boolean>}
     */
    async isAuthenticated() {
        try {
            const response = await fetch('/api/auth/check', {
                credentials: 'include'
            });
            if (!response.ok) return null;
            const data = await response.json();
            return data;
        } catch (error) {
            return null;
        }
    },

    /**
     * Récupère les infos utilisateur si connecté, sinon null
     * @returns {Promise<Object|null>}
     */
    async getUser() {
        const auth = await this.isAuthenticated();
        if (auth && auth.isAuthenticated && auth.user) {
            return auth.user;
        }
        return null;
    }
};

// L'objet AuthService est directement disponible pour les autres scripts inclus dans la page.
