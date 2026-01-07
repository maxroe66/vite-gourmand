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
            const response = await fetch('/api/auth/logout', {
                method: 'POST',
                credentials: 'include' // Envoie le cookie
            });
            if (!response.ok) {
                throw new Error(`Le serveur a répondu avec le statut ${response.status}`);
            }
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
            alert("Une erreur est survenue lors de la déconnexion. Votre session n'a peut-être pas été correctement terminée côté serveur. Vous allez être redirigé.");
        } finally {
            // Dans tous les cas, on redirige l'utilisateur pour qu'il quitte la zone authentifiée
            window.location.href = '/';
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

// L'objet AuthService est directement disponible pour les autres scripts inclus dans la page.
