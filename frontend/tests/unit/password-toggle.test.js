import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadScript } from '../helpers/load-script.js';

/**
 * Tests unitaires — password-toggle.js
 * Couvre : initPasswordToggles (toggle visibilité, aria-pressed)
 */
describe('password-toggle.js', () => {

    beforeEach(() => {
        loadScript('js/utils/password-toggle.js');
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    /**
     * Crée un champ mot de passe avec bouton toggle dans le DOM.
     * @param {string} [id='pw1'] - ID unique pour le champ
     * @returns {{ input: HTMLInputElement, btn: HTMLButtonElement }}
     */
    function createPasswordField(id = 'pw1') {
        const wrapper = document.createElement('div');
        wrapper.classList.add('password-field');

        const input = document.createElement('input');
        input.type = 'password';
        input.id = id;

        const btn = document.createElement('button');
        btn.classList.add('password-toggle');
        btn.setAttribute('aria-pressed', 'false');
        btn.textContent = '👁';

        wrapper.appendChild(input);
        wrapper.appendChild(btn);
        document.body.appendChild(wrapper);

        return { input, btn };
    }

    it('bascule le type de password à text au clic', () => {
        const { input, btn } = createPasswordField();
        initPasswordToggles();

        btn.click();
        expect(input.type).toBe('text');
    });

    it('rebascule de text à password au second clic', () => {
        const { input, btn } = createPasswordField();
        initPasswordToggles();

        btn.click(); // → text
        btn.click(); // → password
        expect(input.type).toBe('password');
    });

    it('met à jour aria-pressed à true quand visible', () => {
        const { btn } = createPasswordField();
        initPasswordToggles();

        btn.click();
        expect(btn.getAttribute('aria-pressed')).toBe('true');
    });

    it('remet aria-pressed à false quand masqué', () => {
        const { btn } = createPasswordField();
        initPasswordToggles();

        btn.click(); // → visible
        btn.click(); // → masqué
        expect(btn.getAttribute('aria-pressed')).toBe('false');
    });

    it('gère plusieurs champs mot de passe indépendamment', () => {
        const field1 = createPasswordField('pw1');
        const field2 = createPasswordField('pw2');
        initPasswordToggles();

        field1.btn.click(); // toggle pw1 seulement
        expect(field1.input.type).toBe('text');
        expect(field2.input.type).toBe('password');
    });

    it('ne crashe pas si pas de champ .password-toggle', () => {
        document.body.innerHTML = '<div>Pas de toggle</div>';
        expect(() => initPasswordToggles()).not.toThrow();
    });
});
