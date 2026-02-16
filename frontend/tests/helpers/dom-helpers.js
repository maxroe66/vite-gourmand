import { fireEvent } from '@testing-library/dom';
import { expect } from 'vitest';
import fs from 'fs';
import path from 'path';

/**
 * Charge le HTML d'une page dans `document.body`.
 * @param {string} pageName - Nom du fichier dans pages/ (ex: 'contact.html', 'admin/dashboard.html')
 * @returns {string} Le HTML chargé
 */
export function loadPage(pageName) {
    const htmlPath = path.resolve(process.cwd(), 'pages', pageName);
    const html = fs.readFileSync(htmlPath, 'utf-8');
    document.body.innerHTML = html;
    return html;
}

/**
 * Remplit un champ par son ID et déclenche l'événement `input`.
 * @param {string} id - ID du champ
 * @param {string} value - Valeur à saisir
 */
export function fillField(id, value) {
    const input = document.getElementById(id);
    if (!input) throw new Error(`Champ #${id} introuvable dans le DOM`);
    input.value = value;
    fireEvent.input(input);
}

/**
 * Vérifie qu'un champ affiche une erreur correspondant au pattern.
 * @param {string} fieldId - ID du champ
 * @param {RegExp|string} messagePattern - Pattern ou texte attendu dans le message d'erreur
 */
export function expectFieldError(fieldId, messagePattern) {
    const errorEl = document.getElementById(fieldId + '-error');
    expect(errorEl, `L'erreur pour #${fieldId} devrait exister`).not.toBeNull();
    if (messagePattern instanceof RegExp) {
        expect(errorEl.textContent).toMatch(messagePattern);
    } else {
        expect(errorEl.textContent).toContain(messagePattern);
    }
}

/**
 * Vérifie qu'un champ n'a pas d'erreur affichée.
 * @param {string} fieldId - ID du champ
 */
export function expectNoFieldError(fieldId) {
    const errorEl = document.getElementById(fieldId + '-error');
    expect(errorEl, `Aucune erreur ne devrait exister pour #${fieldId}`).toBeNull();
}

/**
 * Vérifie qu'un champ a l'attribut aria-invalid="true".
 * @param {string} fieldId - ID du champ
 */
export function expectAriaInvalid(fieldId) {
    const field = document.getElementById(fieldId);
    expect(field.getAttribute('aria-invalid')).toBe('true');
}

/**
 * Simule un blur sur un champ (marque comme "touched").
 * @param {string} id - ID du champ
 */
export function blurField(id) {
    const input = document.getElementById(id);
    if (!input) throw new Error(`Champ #${id} introuvable dans le DOM`);
    fireEvent.blur(input);
}
