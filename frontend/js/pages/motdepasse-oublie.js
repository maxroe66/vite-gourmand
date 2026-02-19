// motdepasse-oublie.js
// JS pour la page de réinitialisation du mot de passe

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('forgotPasswordForm');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    // Fonction utilitaire pour afficher un message
    function showMessage(message, type = 'error') {
        let msgDiv = document.querySelector('.general-error, .success-message');
        if (msgDiv) msgDiv.remove();
        msgDiv = document.createElement('div');
        msgDiv.className = type === 'success' ? 'success-message' : 'general-error';
        msgDiv.textContent = message;
        form.prepend(msgDiv);
    }

    // Récupérer le token dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    if (!token) {
        showMessage('Lien de réinitialisation invalide ou expiré.');
        form.querySelector('button[type="submit"]').disabled = true;
        return;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        // Nettoyer les messages précédents
        let msgDiv = document.querySelector('.general-error, .success-message');
        if (msgDiv) msgDiv.remove();

        const newPassword = newPasswordInput.value.trim();
        const confirmPassword = confirmPasswordInput.value.trim();

        if (newPassword.length < 10 || !/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d])/.test(newPassword)) {
            showMessage('Le mot de passe doit faire au moins 10 caractères avec 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.');
            return;
        }
        if (newPassword !== confirmPassword) {
            showMessage('Les mots de passe ne correspondent pas.');
            return;
        }

        try {
            const result = await AuthService.resetPassword(token, newPassword);
            if (result.ok && result.data.success) {
                showMessage('Mot de passe modifié avec succès. Vous pouvez vous connecter.', 'success');
                form.reset();
                setTimeout(() => {
                    window.location.href = '/connexion';
                }, 2500);
            } else {
                showMessage(result.data.message || 'Erreur lors de la réinitialisation du mot de passe.');
            }
        } catch (error) {
            showMessage('Erreur réseau. Veuillez réessayer plus tard.');
        }
    });

    // Password show/hide toggles
    initPasswordToggles();
});
