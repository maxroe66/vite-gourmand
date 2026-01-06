/**
 * Service de gestion de l'authentification côté client
 * Utilise des cookies httpOnly gérés automatiquement par le navigateur
 */
const AuthService = {
    /**
     * Déconnecte l'utilisateur en appelant l'endpoint de logout
     * qui supprimera le cookie côté serveur
     */
    async logout() {
        try {
            await fetch('/api/auth/logout', {
                method: 'POST',
                credentials: 'include'  // Envoie le cookie
            });
            // Redirection après déconnexion
            window.location.href = '/';
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
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
            return response.ok;
        } catch (error) {
            return false;
        }
    }
};

// Export pour utilisation dans d'autres fichiers
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthService;
}
