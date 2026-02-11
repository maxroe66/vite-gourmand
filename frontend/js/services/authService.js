/**
 * Service de gestion de l'authentification côté client
 * Utilise des cookies httpOnly gérés automatiquement par le navigateur
 */

function getCookieValue(name) {
    const parts = document.cookie.split(';');
    for (const part of parts) {
        const [key, ...rest] = part.trim().split('=');
        if (key === name) {
            return decodeURIComponent(rest.join('='));
        }
    }
    return null;
}

const AuthService = {
        getCsrfToken() {
            return getCookieValue('csrfToken');
        },

        addCsrfHeader(headers = {}) {
            const token = this.getCsrfToken();
            if (!token) return headers;
            return {
                ...headers,
                'X-CSRF-Token': token
            };
        },
        /**
         * Réinitialise le mot de passe avec le token et le nouveau mot de passe
         * @param {string} token
         * @param {string} password
         * @returns {Promise<{ok: boolean, status: number, data: object}>}
         */
        async resetPassword(token, password) {
            try {
                const response = await fetch('/api/auth/reset-password', this.getFetchOptions({
                    method: 'POST',
                    body: JSON.stringify({ token, password })
                }));
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
                const response = await fetch('/api/auth/register', this.getFetchOptions({
                    method: 'POST',
                    body: JSON.stringify(userData)
                }));
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
                const response = await fetch('/api/auth/login', this.getFetchOptions({
                    method: 'POST',
                    body: JSON.stringify({ email, password })
                }));
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
            const response = await fetch('/api/auth/logout', this.getFetchOptions({
                method: 'POST'
            }));
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
        const headers = this.addCsrfHeader({
            'Content-Type': 'application/json',
            ...(options.headers || {})
        });

        return {
            ...options,
            credentials: 'include',
            headers
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
