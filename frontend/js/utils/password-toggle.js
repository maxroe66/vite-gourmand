/**
 * Initialise les toggles de visibilité mot de passe sur la page.
 * Cible tous les boutons avec la classe .password-toggle.
 * Structure HTML attendue :
 *   <div class="password-field">
 *     <input type="password">
 *     <button class="password-toggle" aria-pressed="false">…</button>
 *   </div>
 */
function initPasswordToggles() {
    document.querySelectorAll('.password-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const input = btn.closest('.password-field').querySelector('input');
            if (!input) return;
            const isPwd = input.type === 'password';
            input.type = isPwd ? 'text' : 'password';
            btn.setAttribute('aria-pressed', isPwd ? 'true' : 'false');
        });
    });
}
