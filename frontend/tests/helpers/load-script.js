import fs from 'fs';
import path from 'path';
import vm from 'vm';

/**
 * Charge un script vanilla JS dans le contexte global du test.
 * Simule le comportement d'une balise <script> dans le navigateur :
 * - Les `function` declarations deviennent globales
 * - Les `const`/`let` au top-level sont transformés en `var` pour être accessibles globalement
 *
 * @param {string} relativePath - Chemin relatif depuis le dossier `frontend/` (ex: 'js/utils/helpers.js')
 */
export function loadScript(relativePath) {
    const absolutePath = path.resolve(process.cwd(), relativePath);
    let code = fs.readFileSync(absolutePath, 'utf-8');
    // Transformer const/let en début de ligne (top-level) en var
    // pour imiter le scope script du navigateur (non-module)
    code = code.replace(/^const\s+/gm, 'var ');
    code = code.replace(/^let\s+/gm, 'var ');
    vm.runInThisContext(code, { filename: relativePath });
}
