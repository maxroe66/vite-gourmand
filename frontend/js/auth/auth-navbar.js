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
            // Ajout du bouton Mon Profil + Déconnexion
            desktopActions.innerHTML = `
                <a href="/frontend/pages/profil.html" class="button button--ghost u-mr-sm">
                    Mon Profil
                </a>
                <button class="button button--primary" id="logoutBtnDesktop">
                    Déconnexion
                </button>
            `;
        }

        // --- 2. Mobile Actions existantes ---
        // On masque les li "connexion" et "inscription" du mobile (qui ont la classe .navbar__mobile-action)
        const mobileActions = document.querySelectorAll('.navbar__mobile-action');
        mobileActions.forEach(el => el.classList.add('u-hidden'));

        // Ajout Lien Profil Mobile
        const mobileMenu = document.querySelector('.navbar__menu--mobile');
        if (mobileMenu) {
            const liProfil = document.createElement('li');
            liProfil.innerHTML = `
                <a href="/frontend/pages/profil.html" class="navbar__link">
                    Mon Profil
                </a>
            `;
            // Insérer avant les actions (optionnel, mais append à la fin est OK car les actions sont masquées)
            mobileMenu.appendChild(liProfil);
        }

        // --- 3. Lien "Espace Gestion" (Admin/Employé) ---
        // On l'ajoute sur TOUS les menus trouvés (Desktop et Mobile)
        if (user.role === 'ADMINISTRATEUR' || user.role === 'EMPLOYE') {
            const allMenus = document.querySelectorAll('.navbar__menu');
            allMenus.forEach(menu => {
                const li = document.createElement('li');
                // On ajoute une classe pour pouvoir le cibler si besoin
                li.className = 'navbar__management-link';
                li.innerHTML = `
                    <a href="/frontend/pages/admin/dashboard.html" class="navbar__link navbar__link--admin">
                        Espace Gestion
                    </a>
                `;
                menu.appendChild(li);
            });
        }

        // --- 4. Ajout du bouton Déconnexion sur Mobile ---
        if (mobileMenu) {
            const liLogout = document.createElement('li');
            liLogout.classList.add('u-text-center');
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
        Logger.error("Erreur lors de la mise à jour de la navbar :", e);
    }
});
