document.addEventListener('componentsLoaded', async () => {
    // Sélection main containers
    const desktopActions = document.querySelector('.navbar__actions');
    const navbarMenu = document.querySelector('.navbar__menu');

    try {
        // Récupération de l'état authentifié
        const auth = await AuthService.isAuthenticated();

        if (auth && auth.isAuthenticated) {
            const user = auth.user;

            // --- 1. Boutons Navigation (Logout & Welcome) ---
            if (desktopActions) {
                desktopActions.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span class="navbar__user-name" style="font-weight: 500; font-size: 0.9rem;">
                            Bonjour, ${user.prenom || 'Utilisateur'}
                        </span>
                        <button class="button button--ghost" id="logoutBtn">
                            Déconnexion
                        </button>
                    </div>
                `;
            }

            // --- 2. Lien Espace Gestion (Admin/Employé) ---
            // On vérifie le rôle
            if (user.role === 'ADMINISTRATEUR' || user.role === 'EMPLOYE') {
                if (navbarMenu) {
                    const li = document.createElement('li');
                    // Style inline pour le mettre en évidence (ou classe utilitaire)
                    li.innerHTML = `
                        <a href="/frontend/frontend/pages/admin/dashboard.html" class="navbar__link" style="color: #e67e22; font-weight: bold;">
                            Espace Gestion
                        </a>
                    `;
                    // Ajout à la fin de la liste existante
                    navbarMenu.appendChild(li);
                }
            }

            // --- 3. Event Listener Logout ---
            const btnLogout = document.getElementById('logoutBtn');
            if (btnLogout) {
                btnLogout.addEventListener('click', async (e) => {
                    e.preventDefault();
                    await AuthService.logout();
                    window.location.href = '/'; 
                });
            }

        }
    } catch (e) {
        console.error("Erreur lors de la mise à jour de la navbar :", e);
    }
});
