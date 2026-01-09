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

        if (newPassword.length < 8) {
            showMessage('Le mot de passe doit faire au moins 8 caractères.');
            return;
        }
        if (newPassword !== confirmPassword) {
            showMessage('Les mots de passe ne correspondent pas.');
            return;
        }

        try {
            const response = await fetch('/api/auth/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    password: newPassword
                })
            });
            const data = await response.json();
            if (response.ok && data.success) {
                showMessage('Mot de passe modifié avec succès. Vous pouvez vous connecter.', 'success');
                form.reset();
                // Optionnel : rediriger vers la page de connexion après quelques secondes
                setTimeout(() => {
                    window.location.href = '/connexion';
                }, 2500);
            } else {
                showMessage(data.message || 'Erreur lors de la réinitialisation du mot de passe.');
            }
        } catch (error) {
            showMessage('Erreur réseau. Veuillez réessayer plus tard.');
        }
    });

    // Password show/hide toggles for reset page
    document.querySelectorAll('.password-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const input = btn.closest('.password-field').querySelector('input');
            if (!input) return;
            const isPwd = input.type === 'password';
            input.type = isPwd ? 'text' : 'password';
            btn.setAttribute('aria-pressed', isPwd ? 'true' : 'false');
        });
    });
});
