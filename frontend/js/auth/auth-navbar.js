document.addEventListener('componentsLoaded', () => {
    // Sélectionne la zone des boutons à droite du navbar (desktop)
    const desktopActions = document.querySelector('.navbar__actions');
    // Sélectionne les slots d’actions dans le menu mobile
    const mobileActionSlots = document.querySelectorAll('.navbar__mobile-action');
    // On suppose que AuthService est déjà chargé
    AuthService.isAuthenticated().then(auth => {
        // Affichage pour utilisateur connecté
        if (auth && auth.isAuthenticated) {
            // Desktop
            if (desktopActions) {
                desktopActions.innerHTML = `<button class="button button--ghost" id="logoutBtn">Déconnexion</button>`;
            }
            // Mobile
            mobileActionSlots.forEach((slot, i) => {
                slot.innerHTML = `<button class="button button--ghost" id="logoutBtnMobile${i}">Déconnexion</button>`;
            });
            // Ajoute le handler logout
            const addLogoutHandler = btn => {
                if (btn) btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    try {
                        await AuthService.logout();
                        window.location.reload();
                    } catch {
                        window.location.reload();
                    }
                });
            };
            addLogoutHandler(document.getElementById('logoutBtn'));
            mobileActionSlots.forEach((_, i) => {
                addLogoutHandler(document.getElementById(`logoutBtnMobile${i}`));
            });
        } else {
            // Affichage pour utilisateur non connecté
            if (desktopActions) {
                desktopActions.innerHTML = `
                    <a href="/inscription" class="button button--ghost">Inscription</a>
                    <a href="/connexion" class="button button--primary">Connexion</a>
                `;
            }
            if (mobileActionSlots[0]) {
                mobileActionSlots[0].innerHTML = `<a href="/inscription" class="button button--ghost">Inscription</a>`;
            }
            if (mobileActionSlots[1]) {
                mobileActionSlots[1].innerHTML = `<a href="/connexion" class="button button--primary">Connexion</a>`;
            }
        }
    });
});