/**
 * Guard pour protéger les pages d'administration/employé
 * Vérifie l'authentification et le rôle de l'utilisateur.
 */
const AdminGuard = {
    /**
     * Vérifie si l'utilisateur a accès à l'espace admin/employé.
     * Redirige automatiquement si non autorisé.
     * @returns {Promise<Object>} L'objet utilisateur si autorisé
     */
    async checkAccess() {
        try {
            // 1. Vérifier si connecté
            const authStatus = await AuthService.isAuthenticated();
            
            if (!authStatus || !authStatus.isAuthenticated || !authStatus.user) {
                // Non connecté -> Redirection vers login avec URL de retour
                const currentUrl = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = `/frontend/pages/connexion.html?redirect=${currentUrl}&msg=auth_required`;
                throw new Error('Non authentifié');
            }

            const user = authStatus.user;

            // 2. Vérifier le rôle (EMPLOYE ou ADMINISTRATEUR)
            const allowedRoles = ['EMPLOYE', 'ADMINISTRATEUR'];
            if (!allowedRoles.includes(user.role)) {
                // Rôle insuffisant -> Redirection vers accueil
                window.location.href = '/?msg=forbidden';
                throw new Error('Accès interdit');
            }

            // Tout est OK
            return user;

        } catch (error) {
            console.warn("AdminGuard blocked access:", error);
            // On laisse le throw pour arrêter l'exécution des scripts appelants si besoin
            throw error; 
        }
    }
};
