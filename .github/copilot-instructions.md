# Instructions Copilot — Refonte CSS Vite & Gourmand

> **Objectif** : Éliminer `'unsafe-inline'` de la CSP `style-src` tout en nettoyant l'architecture CSS.
> **Contexte sécurité** : HIGH-03 du `SECURITY_AUDIT.md` — CSP affaiblie par `'unsafe-inline'` dans `style-src`.

---

## 📊 État des lieux (audit du 12/02/2026)

### Chiffres clés

| Métrique | Valeur |
|---|---|
| Fichiers CSS | 18 |
| Total lignes CSS | ~2 558 |
| Lignes dupliquées | ~350 (~14%) |
| Couleurs hardcodées uniques | ~45+ |
| Tokens inexistants référencés | 13 |
| Breakpoints distincts | 11 (au lieu de 4 définis) |
| `!important` | 12 (4 justifiés) |
| `style=""` dans HTML | 22 |
| `style=""` dans JS (innerHTML) | 54 |
| `.style.xxx` dans JS | 65 |
| **Total inline styles à migrer** | **76** (HTML + innerHTML) |

### Fichier le plus problématique

`frontend/js/admin/dashboard.js` concentre **~80%** des inline styles (51 `style=""` dans innerHTML + 23 `.style.xxx`). C'est la priorité.

### Architecture actuelle

- **Aucun bundler** — chargement par `<link>` individuels par page
- **Système de tokens** dans `_tokens.css` (couleurs, spacing, radius, shadows, breakpoints)
- **@layer CSS** : utilisé partiellement (base.css, navbar, footer, button, hero-home) — 3 composants et toutes les pages manquants
- **`utilities.css` : VIDE** (0 lignes) — fichier prévu mais jamais rempli

---

## 🔴 Bugs CSS critiques à corriger en premier

1. **Sélecteur global `h3`** dans `avis-clients-home.css` ligne 83 — affecte TOUS les h3 du site
2. **Thème sombre cassé** dans `_tokens.css` ligne 91 — erreur de syntaxe (`--shadow-200` coupé)
3. **`border: 5px solid red`** dans `connexion.css` sur `.general-error` — style de debug en prod
4. **`.signup-success-message` défini 2 fois** dans `inscription.css` (lignes 19 et 50)
5. **`.avis-clients` défini 2 fois** dans `avis-clients-home.css` (lignes 3 et 16)
6. **`var(--primary-color)` au lieu de `var(--color-primary)`** dans `home.css` et `dashboard.css`

---

## 🏗️ Plan de refonte en 8 phases

### Phase 0 : Corriger les bugs critiques CSS
> **Fichiers** : `avis-clients-home.css`, `_tokens.css`, `connexion.css`, `inscription.css`, `home.css`
> **Effort** : ~15 min

- [ ] Scoper le `h3` global dans `avis-clients-home.css` → `.avis-clients h3`
- [ ] Corriger la syntaxe du thème sombre dans `_tokens.css` ligne 91
- [ ] Supprimer `border: 5px solid red` de `.general-error` dans `connexion.css`
- [ ] Supprimer le doublon `.signup-success-message` dans `inscription.css`
- [ ] Supprimer le doublon `.avis-clients` dans `avis-clients-home.css`
- [ ] Corriger `var(--primary-color)` → `var(--color-primary)` dans `home.css`

---

### Phase 1 : Compléter les design tokens manquants
> **Fichier** : `frontend/frontend/styles/_tokens.css`
> **Effort** : ~20 min

Ajouter les tokens référencés dans le code mais non définis :

```css
:root {
  /* Couleurs manquantes */
  --color-border: #CBD5E1;
  --color-surface: #F8FAFC;
  --color-text-light: #95A5A6;
  --color-primary-dark: #E56600;

  /* Radius manquant */
  --radius-xs: 4px;

  /* Ombre manquante */
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.06);

  /* Z-index échelle organisée */
  --z-dropdown: 10;
  --z-sticky: 20;
  --z-fixed: 50;
  --z-modal-backdrop: 900;
  --z-modal: 1000;
  --z-toast: 1100;

  /* Couleurs feedback (badges, messages) */
  --color-warning-bg: #fff3cd;
  --color-warning-text: #856404;
  --color-success-bg: #d4edda;
  --color-success-text: #155724;
  --color-error-bg: #f8d7da;
  --color-error-text: #721c24;
  --color-info-bg: #d1ecf1;
  --color-info-text: #0c5460;
  --color-neutral-bg: #e2e3e5;
  --color-neutral-text: #383d41;

  /* Couleurs UI supplémentaires */
  --color-muted: #666;
  --color-star: #fbbf24;
  --color-star-empty: #cbd5e1;
}
```

- [ ] Supprimer le doublon `--navbar-height` dans `navbar.css` (garder celui de `_tokens.css`)
- [ ] Aligner `--navbar-bg` : soit l'ajouter aux tokens, soit utiliser une variable existante

---

### Phase 2 : Créer les classes utilitaires (`utilities.css`)
> **Fichier** : `frontend/frontend/styles/utilities.css`
> **Effort** : ~30 min

Ce fichier est actuellement **VIDE**. Créer les classes nécessaires pour remplacer les inline styles :

```css
@layer utilities {
  /* ── Accessibilité ── */
  .visually-hidden { /* déplacer depuis motdepasse-oublie.css */ }
  .sr-only { /* alias */ }

  /* ── Affichage ── */
  .u-hidden       { display: none !important; }
  .is-visible     { display: flex; }  /* pour les modals */
  .u-block        { display: block; }
  .u-flex         { display: flex; }
  .u-grid         { display: grid; }

  /* ── Layout flex ── */
  .u-flex-col     { flex-direction: column; }
  .u-flex-center  { justify-content: center; align-items: center; }
  .u-flex-between { justify-content: space-between; }
  .u-items-center { align-items: center; }
  .u-gap-xs       { gap: var(--space-1); }
  .u-gap-sm       { gap: var(--space-2); }
  .u-gap-md       { gap: var(--space-3); }

  /* ── Texte ── */
  .u-text-center  { text-align: center; }
  .u-text-left    { text-align: left; }
  .u-text-right   { text-align: right; }
  .u-text-bold    { font-weight: 700; }
  .u-text-italic  { font-style: italic; }

  /* ── Couleurs texte ── */
  .u-text-muted   { color: var(--color-muted); }
  .u-text-error   { color: var(--color-error-text); }
  .u-text-success { color: var(--color-success-text); }

  /* ── Largeur ── */
  .u-w-full       { width: 100%; }

  /* ── Espacement ── */
  .u-mb-sm        { margin-bottom: var(--space-2); }
  .u-mb-md        { margin-bottom: var(--space-3); }
  .u-p-sm         { padding: var(--space-2); }
  .u-p-md         { padding: var(--space-3); }

  /* ── Bordures ── */
  .u-border-dashed { border: 1px dashed var(--color-border); }
  .u-border-bottom { border-bottom: 1px solid var(--color-border); }

  /* ── Scrollbar cachée ── */
  .u-scrollbar-hidden {
    scrollbar-width: none;
    -ms-overflow-style: none;
  }
  .u-scrollbar-hidden::-webkit-scrollbar { display: none; }
}
```

---

### Phase 3 : Extraire les composants partagés
> **Nouveaux fichiers** : `forms.css`, `modals.css`, `auth-layout.css`
> **Effort** : ~45 min

#### 3a — `frontend/frontend/styles/components/forms.css`

Extraire les patterns dupliqués dans 6 fichiers :
- `.form-group`, `.form-group label`, `.form-group input`
- `.form-row`
- `.error-message`, `.success-message`, `.general-error`
- `input.error`
- `.password-field`, `.password-toggle`

**Ensuite supprimer** ces blocs de : `connexion.css`, `inscription.css`, `motdepasse-oublie.css`, `commande.css`, `profil.css`, `dashboard.css`

#### 3b — `frontend/frontend/styles/components/modals.css`

Unifier les 3 modals différentes :
- `.modal-overlay` : fond sombre semi-transparent (connexion, profil, dashboard)
- `.modal-content` : boîte blanche centrée
- `.close-modal` : bouton de fermeture
- `.modal-header`, `.modal-body`, `.modal-footer` : structure standard

**Ensuite supprimer** les styles modal de : `connexion.css`, `profil.css`, `dashboard.css`

#### 3c — `frontend/frontend/styles/layouts/auth-layout.css`

Le layout split image/formulaire est identique entre connexion, inscription et motdepasse-oublie :
- `.auth-container` : grid 2 colonnes
- `.auth-form-wrapper` : colonne formulaire
- `.auth-image-wrapper` : colonne image décorative

**Réduction estimée** : ~200 lignes supprimées des 3 fichiers auth

---

### Phase 4 : Migrer les `style=""` HTML → classes CSS
> **Fichiers HTML** : `profil.html` (9), `dashboard.html` (8), `commande.html` (2), `menu-detail.html` (2), `home.html` (1)
> **Effort** : ~30 min

#### Règles de migration

| Pattern inline | Classe de remplacement |
|---|---|
| `style="display:none"` | `class="u-hidden"` |
| `style="display:flex"` | `class="u-flex"` |
| `style="display:flex; flex-direction:column; gap:0.5rem"` | `class="u-flex u-flex-col u-gap-sm"` |
| `style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem"` | Classe spécifique dans le CSS de la page |
| `style="margin-bottom: 20px"` | `class="u-mb-md"` |
| `style="width:100%"` | `class="u-w-full"` |
| `style="display:block; margin-bottom:10px; font-weight:bold"` | `class="u-block u-mb-sm u-text-bold"` |
| `style="color:#666; font-style:italic"` | `class="u-text-muted u-text-italic"` |
| `style="border:1px dashed #ccc; padding:10px; background:#fafafa"` | Classe spécifique ou combinaison utilitaires |

#### Ordre de migration
1. `profil.html` — 9 occurrences (le plus d'inline styles)
2. `dashboard.html` — 8 occurrences
3. `commande.html` — 2 occurrences
4. `menu-detail.html` — 2 occurrences
5. `home.html` — 1 occurrence

---

### Phase 5 : Migrer les innerHTML `style=""` dans le JS → classes CSS
> **Fichiers JS** : `dashboard.js` (51), `auth-navbar.js` (2), `home-menus.js` (1)
> **Effort** : ~1h30 (le plus long, surtout dashboard.js)

#### dashboard.js — 51 occurrences

Catégories à traiter :

| Catégorie | Nb | Remplacement |
|---|---|---|
| `text-align:center` | 12 | `class="u-text-center"` |
| `color:red` / `color:#888` | 8 | `class="u-text-error"` / `class="u-text-muted"` |
| `font-weight:bold` | 4 | `class="u-text-bold"` |
| `display:none` / `display:flex` | 4 | `class="u-hidden"` / `class="u-flex"` |
| Layout (flex, gap, margin) | 11 | Classes utilitaires ou CSS dashboard |
| Bordures | 3 | `class="u-border-bottom"` |
| Dimensions (width) | 3 | `class="u-w-full"` ou CSS spécifique |
| Autres (background, padding) | 6 | Classes CSS spécifiques dans dashboard.css |

**Stratégie** : Ajouter les classes nécessaires dans `dashboard.css` ou `utilities.css`, puis remplacer chaque `style="..."` par `class="..."` dans les template literals JS.

#### auth-navbar.js — 2 occurrences
- `margin-right:8px` → classe utilitaire
- `color:#e67e22; font-weight:bold` → `.navbar__admin-link` dans navbar.css

#### home-menus.js — 1 occurrence
- `width:100%; text-align:center; padding:2rem` → `class="u-w-full u-text-center u-p-md"`

---

### Phase 6 : Migrer les `.style.xxx` JS → `classList.toggle()`
> **Fichiers JS** : `dashboard.js` (23), `profil.js` (11), `menu-detail.js` (10), `commande.js` (5), `connexion.js` (4), `menus-carousel.js` (4), `demo-cube.js` (4), `auth-navbar.js` (2)
> **Effort** : ~1h

#### Patterns de migration

**Display toggle (41 occurrences — 63% du total)** :
```js
// AVANT
modal.style.display = 'flex';
modal.style.display = 'none';

// APRÈS
modal.classList.add('is-visible');
modal.classList.remove('is-visible');
```

Avec en CSS :
```css
.modal-overlay { display: none; }
.modal-overlay.is-visible { display: flex; }
```

Pour les éléments non-modal :
```js
// AVANT
element.style.display = 'none';
element.style.display = 'block';

// APRÈS
element.classList.add('u-hidden');
element.classList.remove('u-hidden');
```

**Opacity + cursor (7 occurrences)** :
```js
// AVANT
btn.style.opacity = '0.5';
btn.style.cursor = 'not-allowed';

// APRÈS
btn.classList.add('is-disabled');
```

Avec en CSS :
```css
.is-disabled {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}
```

**Color dynamique (4 occurrences dans connexion.js)** :
```js
// AVANT
forgotMsg.style.color = '#dc3545';
forgotMsg.style.color = '#28a745';

// APRÈS
forgotMsg.classList.remove('u-text-success', 'u-text-error');
forgotMsg.classList.add('u-text-error');
```

**Background dynamique (1 occurrence)** :
```js
// AVANT
btn.style.backgroundColor = '#ccc';

// APRÈS — inclure dans la classe .is-disabled
```

---

### Phase 7 : Aligner `dashboard.css` sur le design system
> **Fichier** : `frontend/frontend/styles/admin/dashboard.css` (556 lignes)
> **Effort** : ~45 min

1. **Supprimer les variables fantômes** : `--primary-color`, `--secondary-color`, `--border-color`, `--text-color`, `--surface-bg` → remplacer par les tokens officiels (`--color-primary`, `--color-secondary`, `--color-border`, `--color-text`, `--color-surface`)
2. **Supprimer les `!important`** abusifs (`.dashboard-main` margin/padding)
3. **Scoper les sélecteurs globaux** : `label`, `input[type="text"]` → `.dashboard label`, `.dashboard input[type="text"]`
4. **Supprimer `@keyframes spin` dupliqué** (garder dans `forms.css` ou `utilities.css`)
5. **Harmoniser les breakpoints** : `769px` → `768px`, `650px` → `600px` ou token
6. **Supprimer les couleurs hardcodées** : remplacer les 20+ par des tokens

---

### Phase 8 : Retirer `'unsafe-inline'` de la CSP
> **Fichiers** : `SecurityHeadersMiddleware.php`, `SecurityHeadersMiddlewareTest.php`
> **Effort** : ~15 min (une fois les phases 0-7 terminées)

1. Retirer `'unsafe-inline'` de la directive `style-src` dans `SecurityHeadersMiddleware.php`
2. Mettre à jour les tests dans `SecurityHeadersMiddlewareTest.php` :
   - `testDefaultPolicyContainsAllDirectives` : retirer `'unsafe-inline'` de l'assertion `style-src`
   - `testUnsafeInlineRequiredForStyleSrc` : **supprimer ce test** ou le transformer en `testNoUnsafeInlineInStyleSrc`
3. Lancer les tests PHPUnit → valider 248/248
4. Tester manuellement le site dans le navigateur → vérifier qu'aucun style n'est cassé
5. Mettre à jour `SECURITY_AUDIT.md` : marquer HIGH-03 comme ✅ CORRIGÉ
6. Commit final

---

## ⚠️ Nettoyages additionnels à faire pendant la refonte

Ces éléments ne sont pas liés à `unsafe-inline` mais doivent être corrigés en même temps :

- [ ] **Normaliser les breakpoints** : n'utiliser que les 4 tokens (`--bp-sm: 480px`, `--bp-md: 768px`, `--bp-lg: 1024px`, `--bp-xl: 1200px`)
- [ ] **Compléter `@layer`** : ajouter `@layer components` à `menus-home.css`, `avis-clients-home.css`, `carousel-split-home.css`
- [ ] **Créer `@layer pages`** pour les fichiers de page
- [ ] **Supprimer les vendor prefixes obsolètes** : `-webkit-overflow-scrolling: touch`, `-ms-overflow-style: none`
- [ ] **Organiser les z-index** via tokens au lieu de valeurs magiques
- [ ] **Corriger le scroll-snap global** dans `base.css` → le conditionner via `.page--snap`
- [ ] **Supprimer la surcharge globale `html, body`** dans `menu-detail.css` ligne 10

---

## 📁 Structure CSS cible (après refonte)

```
styles/
├── _tokens.css                  ← Design tokens (complets)
├── base.css                     ← Reset + typo (@layer base)
├── utilities.css                ← Classes utilitaires (@layer utilities)
├── components/
│   ├── navbar.css               ← @layer components
│   ├── footer.css               ← @layer components
│   ├── button.css               ← @layer components
│   ├── forms.css                ← NOUVEAU — @layer components
│   ├── modals.css               ← NOUVEAU — @layer components
│   ├── hero-home.css            ← @layer components
│   ├── menus-home.css           ← @layer components (à ajouter)
│   ├── avis-clients-home.css    ← @layer components (à ajouter)
│   └── carousel-split-home.css  ← @layer components (à ajouter)
├── layouts/
│   └── auth-layout.css          ← NOUVEAU — @layer layouts
├── pages/
│   ├── home.css                 ← @layer pages (épuré)
│   ├── connexion.css            ← @layer pages (réduit à ~30 lignes)
│   ├── inscription.css          ← @layer pages (réduit à ~20 lignes)
│   ├── motdepasse-oublie.css    ← @layer pages (réduit à ~15 lignes)
│   ├── profil.css               ← @layer pages
│   ├── commande.css             ← @layer pages
│   └── menu-detail.css          ← @layer pages
└── admin/
    └── dashboard.css            ← @layer pages (aligné sur tokens)
```

**Ordre des layers** : `@layer base, utilities, components, layouts, pages;`

---

## 🧪 Validation à chaque phase

Avant de passer à la phase suivante :

1. **Test visuel** : ouvrir chaque page dans le navigateur, vérifier qu'aucun style n'est cassé
2. **Console navigateur** : vérifier l'absence d'erreurs CSP (une fois `unsafe-inline` retiré)
3. **Tests PHPUnit** : `cd backend && ./vendor/bin/phpunit` → 248/248
4. **Commit en français** avec le format : `refacto(css): phase N — description`

---

## 📌 Conventions à respecter

- **Nommage CSS** : BEM pour les composants (`.block__element--modifier`), préfixe `u-` pour les utilitaires
- **Pas de `style=""`** dans le HTML — utiliser des classes CSS
- **Pas de `.style.xxx`** dans le JS pour du styling statique — utiliser `classList`
- **Pas de couleurs hardcodées** — utiliser les tokens `var(--color-xxx)`
- **Pas de tailles hardcodées** — utiliser les tokens spacing/font
- **Breakpoints** : uniquement `--bp-sm` (480px), `--bp-md` (768px), `--bp-lg` (1024px), `--bp-xl` (1200px)
- **Z-index** : uniquement via tokens `--z-xxx`
- **Pas de `!important`** sauf pour `.visually-hidden` et `.u-hidden`
