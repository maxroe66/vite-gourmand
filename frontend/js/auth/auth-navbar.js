document.addEventListener('componentsLoaded', async () => {
    try {
        // Récupération de l'état authentifié
        const auth = await AuthService.isAuthenticated();

        // Si non authentifié, on ne fait rien (la navbar garde les boutons par défaut)
        if (!auth || !auth.isAuthenticated) {
            return;
        }

        const user = auth.user;

        // --- 1. Desktop Actions (.navbar__actions) ---
        const desktopActions = document.querySelector('.navbar__actions');
        if (desktopActions) {
            // Remplacement des boutons Connexion/Inscription par Déconnexion uniquement
            // Suppression de l'affichage Username (demandé par user)
            desktopActions.innerHTML = `
                <button class="button button--ghost" id="logoutBtnDesktop">
                    Déconnexion
                </button>
            `;
        }

        // --- 2. Mobile Actions existantes ---
        // On masque les li "connexion" et "inscription" du mobile (qui ont la classe .navbar__mobile-action)
        const mobileActions = document.querySelectorAll('.navbar__mobile-action');
        mobileActions.forEach(el => el.style.display = 'none');

        // --- 3. Lien "Espace Gestion" (Admin/Employé) ---
        // On l'ajoute sur TOUS les menus trouvés (Desktop et Mobile)
        if (user.role === 'ADMINISTRATEUR' || user.role === 'EMPLOYE') {
            const allMenus = document.querySelectorAll('.navbar__menu');
            allMenus.forEach(menu => {
                const li = document.createElement('li');
                // On ajoute une classe pour pouvoir le cibler si besoin
                li.className = 'navbar__management-link';
                li.innerHTML = `
                    <a href="/frontend/frontend/pages/admin/dashboard.html" class="navbar__link" style="color: #e67e22; font-weight: bold;">
                        Espace Gestion
                    </a>
                `;
                menu.appendChild(li);
            });
        }

        // --- 4. Ajout du bouton Déconnexion sur Mobile ---
        const mobileMenu = document.querySelector('.navbar__menu--mobile');
        if (mobileMenu) {
            const liLogout = document.createElement('li');
            liLogout.style.textAlign = 'center';
            liLogout.innerHTML = `
                <button class="button button--ghost" id="logoutBtnMobile">
                    Déconnexion
                </button>
            `;
            mobileMenu.appendChild(liLogout);
        }

        // --- 5. Event Listener Logout (Commun) ---
        const handleLogout = async (e) => {
            e.preventDefault();
            await AuthService.logout();
            window.location.href = '/'; 
        };

        const btnLogoutDesktop = document.getElementById('logoutBtnDesktop');
        if (btnLogoutDesktop) btnLogoutDesktop.addEventListener('click', handleLogout);

        const btnLogoutMobile = document.getElementById('logoutBtnMobile');
        if (btnLogoutMobile) btnLogoutMobile.addEventListener('click', handleLogout);

    } catch (e) {
        console.error("Erreur lors de la mise à jour de la navbar :", e);
    }
});
