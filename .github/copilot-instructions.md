# Copilot Instructions — Vite & Gourmand

## 1. Présentation du projet

**Vite & Gourmand** est une application web de traiteur/catering basée à Bordeaux.
Elle permet aux clients de consulter des menus, passer des commandes, laisser des avis,
et aux administrateurs/employés de gérer menus, plats, commandes, avis et statistiques.

- **Stack backend :** PHP 8+ (vanilla, sans framework), architecture MVC/Service/Repository, conteneur DI (PHP-DI), API REST JSON.
- **Stack frontend :** HTML statique + CSS pur (architecture CSS @layer) + JavaScript vanilla (aucun framework JS).
- **Base de données :** MySQL 8 (données relationnelles) + MongoDB 4.4 (logs, matériel).
- **Infra :** Docker Compose (Apache + PHP-FPM + MySQL + MongoDB), déploiement Azure App Service.
- **Tests backend :** PHPUnit.
- **Tests frontend :** Vitest (fichiers dans `frontend/tests/`).

---

## 2. Arborescence du projet

```
vite_et_gourmand/
├── backend/                   # Code serveur PHP
│   ├── api/                   # Définitions des routes (routes.*.php)
│   ├── config/                # config.php, container.php (DI)
│   ├── database/              # Scripts SQL (schema, fixtures) + MongoDB
│   ├── src/
│   │   ├── Controllers/       # Contrôleurs (MenuController, AuthController…)
│   │   ├── Core/              # Router, Request, Response
│   │   ├── Exceptions/        # Exceptions métier
│   │   ├── Middlewares/       # CORS, CSRF, RateLimit, SecurityHeaders
│   │   ├── Models/            # Entités (User, Menu, Commande…)
│   │   ├── Repositories/      # Accès BDD (MySQL queries)
│   │   ├── Services/          # Logique métier (AuthService, MailerService…)
│   │   └── Validators/        # Validation des données entrantes
│   ├── templates/emails/      # Templates HTML pour les emails
│   ├── tests/                 # Tests PHPUnit
│   └── vendor/                # Dépendances Composer (ignoré par git)
│
├── frontend/                  # Code client (HTML/CSS/JS)
│   ├── pages/                 # Pages HTML
│   │   ├── home.html
│   │   ├── connexion.html
│   │   ├── inscription.html
│   │   ├── profil.html
│   │   ├── commande.html
│   │   ├── menu-detail.html
│   │   ├── motdepasse-oublie.html
│   │   ├── admin/
│   │   │   └── dashboard.html
│   │   └── components/        # Composants HTML réutilisables
│   │       ├── navbar.html
│   │       └── footer.html
│   ├── styles/                # Feuilles de style CSS
│   │   ├── _tokens.css        # Design tokens (variables CSS)
│   │   ├── base.css           # Reset + typographie + @layer order
│   │   ├── utilities.css      # Classes utilitaires
│   │   ├── components/        # CSS par composant (navbar, footer, button…)
│   │   ├── layouts/           # CSS layouts (auth-layout…)
│   │   ├── pages/             # CSS spécifique par page
│   │   └── admin/             # CSS admin (dashboard…)
│   ├── js/                    # Scripts JavaScript
│   │   ├── core/              # Infrastructure de l'app
│   │   │   ├── components.js  # Chargement dynamique navbar/footer
│   │   │   └── navbar.js      # Logique menu mobile
│   │   ├── pages/             # Scripts de page (1 fichier = 1 page)
│   │   │   ├── home-menus.js  # Affichage menus page d'accueil
│   │   │   ├── connexion.js   # Logique page connexion
│   │   │   ├── inscription.js # Logique page inscription
│   │   │   ├── profil.js      # Logique page profil (commandes, avis)
│   │   │   ├── commande.js    # Logique page commande
│   │   │   ├── menu-detail.js # Logique page détail menu
│   │   │   └── motdepasse-oublie.js # Logique reset mot de passe
│   │   ├── widgets/           # Composants UI réutilisables
│   │   │   ├── avis-carousel.js   # Carousel des avis clients
│   │   │   ├── menus-carousel.js  # Carousel des menus
│   │   │   └── demo-cube.js       # Animation Rubik's Cube 3D
│   │   ├── auth/
│   │   │   └── auth-navbar.js # Mise à jour navbar selon état auth
│   │   ├── guards/
│   │   │   └── adminGuard.js  # Protection pages admin (rôle)
│   │   └── services/          # Services API (fetch wrappers)
│   │       ├── authService.js
│   │       ├── menuService.js
│   │       ├── commandeService.js
│   │       ├── avisService.js
│   │       ├── platService.js
│   │       └── adminService.js
│   ├── tests/                 # Tests Vitest
│   ├── package.json
│   └── vitest.config.js
│
├── public/                    # Document root Apache
│   ├── index.php              # Front controller (point d'entrée unique)
│   └── assets/                # Images, logos, fichiers statiques
│
├── docker/                    # Configuration Docker
│   ├── apache/                # vite.conf, vite-ssl.conf, Dockerfile
│   ├── php/                   # Dockerfile PHP-FPM, php.ini
│   ├── mysql/                 # my.cnf
│   ├── mongodb/               # mongod.conf
│   └── certs/                 # Certificats SSL auto-signés (dev)
│
├── docker-compose.yml
├── Dockerfile.azure           # Build production Azure
├── scripts/                   # Scripts utilitaires
├── Docs/                      # Documentation technique
└── fichiers_perso/            # Notes internes et roadmap
```

---

## 3. Conventions de chemins (CRITIQUE)

### Chemins dans les fichiers HTML (pages/)

Les pages HTML utilisent des chemins **absolus depuis la racine du serveur** :

```html
<!-- CSS -->
<link rel="stylesheet" href="/frontend/styles/_tokens.css">
<link rel="stylesheet" href="/frontend/styles/components/button.css">

<!-- JS -->
<script src="/frontend/js/components.js"></script>
<script src="/frontend/js/services/authService.js"></script>

<!-- Liens entre pages -->
<a href="/frontend/pages/home.html#menus">Menu</a>
<a href="/frontend/pages/connexion.html">Connexion</a>
<a href="/frontend/pages/admin/dashboard.html">Espace Gestion</a>

<!-- Images (assets publics) -->
<img src="/assets/images/logo.png">
```

### Chemins dans les fichiers JS

```javascript
// Navigation / redirections
window.location.href = '/frontend/pages/connexion.html';
window.location.href = '/frontend/pages/profil.html';

// Chargement composants HTML
const basePath = '/frontend/pages/components/';

// Appels API (chemins relatifs depuis la racine)
fetch('/api/auth/login', { ... });
fetch('/api/menus', { ... });
```

### Chemins dans le backend PHP

```php
// index.php — pages statiques
$staticPagePath = __DIR__ . '/../frontend/pages/home.html';

// MailerService — liens dans les emails
$orderLink = rtrim($frontendUrl, '/') . '/frontend/pages/profil.html?orderId=' . $id;
```

### Pourquoi ça fonctionne

Apache (vite.conf) définit un alias :
```apache
Alias /frontend /var/www/vite_gourmand/frontend
```
Toute requête `/frontend/*` est servie depuis le dossier `frontend/` du projet.

> **IMPORTANT :** Ne jamais utiliser `frontend/frontend/` dans les chemins. Le sous-dossier redondant a été éliminé.

---

## 4. Architecture CSS

### Système de layers

Le CSS utilise `@layer` pour gérer la spécificité de manière prévisible (déclaré dans `base.css`) :

```css
@layer base, utilities, components, layouts, pages;
```

Ordre de priorité (du moins prioritaire au plus prioritaire) :
1. **base** — Reset, typographie, éléments HTML natifs
2. **utilities** — Classes utilitaires (`u-hidden`, `u-text-center`, `mt-lg`…)
3. **components** — Composants réutilisables (`.button`, `.navbar`, `.footer`, `.form-group`…)
4. **layouts** — Mises en page (`.auth-section`, `.auth-container`…)
5. **pages** — Styles spécifiques à une page

### Design tokens (`_tokens.css`)

Toutes les valeurs de design sont centralisées dans des variables CSS :

```css
:root {
  --color-primary: #FC7200;      /* Orange CTA */
  --color-primary-600: #E65A00;  /* Hover CTA */
  --color-secondary: #2C3E50;    /* Navbar, footer, textes */
  --color-bg: #F5F5F5;
  --font-family: 'Inter', sans-serif;
  --radius-md: 0.5rem;
  /* ... etc */
}
```

### Convention de nommage CSS

- **BEM** : `.block__element--modifier` (ex: `.navbar__link--admin`, `.button--primary`)
- **Préfixes utilitaires** : `u-` (ex: `u-hidden`, `u-mr-sm`)
- **États** : `is-` (ex: `is-visible`, `is-fading`, `is-disabled`)

### Règles CSS

- Chaque composant a son propre fichier CSS dans `styles/components/`.
- Chaque page a son propre fichier CSS dans `styles/pages/`.
- Ne jamais mettre de styles inline dans le HTML.
- Toujours utiliser les variables de `_tokens.css` pour couleurs, espacements, etc.
- Chaque fichier CSS doit wraper ses styles dans le `@layer` correspondant.

---

## 5. Architecture JavaScript

### Pattern de chargement

Les pages HTML chargent les scripts en bas de `<body>` dans cet ordre :

```html
<!-- 1. Loader de composants (navbar/footer) -->
<script src="/frontend/js/core/components.js"></script>
<!-- 2. Services API nécessaires -->
<script src="/frontend/js/services/authService.js"></script>
<!-- 3. Auth navbar (met à jour la navbar selon l'état connecté) -->
<script src="/frontend/js/auth/auth-navbar.js"></script>
<!-- 4. Script spécifique à la page -->
<script src="/frontend/js/pages/connexion.js"></script>
<!-- 5. Navbar mobile -->
<script src="/frontend/js/core/navbar.js"></script>
```

### Événements personnalisés

- `componentsLoaded` : émis par `core/components.js` après chargement du header/footer. Les scripts qui dépendent de la navbar (ex: `auth-navbar.js`, `core/navbar.js`) écoutent cet événement.

### Services (frontend/js/services/)

Les services sont des objets/classes globaux qui encapsulent les appels API :

| Service | Variable globale | Description |
|---|---|---|
| `authService.js` | `AuthService` | Auth (login, register, logout, check, CSRF) |
| `menuService.js` | `MenuService` | Menus (CRUD, thèmes, régimes) |
| `commandeService.js` | `CommandeService` | Commandes (create, list, calculate) |
| `avisService.js` | `AvisService` | Avis clients |
| `platService.js` | `PlatService` | Plats (CRUD) |
| `adminService.js` | `AdminService` | Fonctions admin (employés, stats) |

### Sécurité CSRF

Toutes les requêtes mutantes (POST, PUT, DELETE) doivent inclure le header `X-CSRF-Token` via `AuthService.addCsrfHeader()`. Le token est lu depuis le cookie `csrfToken`.

### Conventions JS

- Pas de framework (vanilla JS uniquement).
- Les scripts de page écoutent `DOMContentLoaded`.
- Les scripts dépendant de la navbar écoutent `componentsLoaded`.
- Utiliser `AuthService.getFetchOptions()` ou `AuthService.addCsrfHeader()` pour les requêtes authentifiées.
- Utiliser `credentials: 'include'` sur tous les `fetch` vers l'API.

---

## 6. Architecture Backend

### Routing

Le front controller `public/index.php` :
1. Charge Composer autoloader + `.env`
2. Initialise le conteneur DI
3. Exécute les middlewares globaux (CORS, CSP, CSRF)
4. Route les requêtes API via `Router` (préfixe `/api`)
5. Sert les pages HTML statiques pour les routes frontend (`/`, `/inscription`, `/connexion`, `/reset-password`)

Les routes API sont définies dans `backend/api/routes.*.php`.

### Pattern des contrôleurs

```php
class MenuController {
    public function __construct(
        private MenuService $menuService,
        private MenuValidator $validator
    ) {}

    public function index(Request $request): Response { ... }
    public function show(Request $request): Response { ... }
}
```

### Authentification

- Cookies `httpOnly` (pas de localStorage/JWT côté client)
- Middleware CSRF sur toutes les requêtes mutantes
- Guard côté frontend (`adminGuard.js`) pour protéger les pages admin

### Base de données

- MySQL : Utilisateurs, menus, plats, commandes, avis, thèmes, régimes, allergènes
- MongoDB : Logs applicatifs, gestion matériel

---

## 7. Infrastructure Docker

### Services

| Service | Container | Port |
|---|---|---|
| PHP-FPM | `vite-php-app` | 9000 (interne) |
| Apache | `vite-apache` | 8000 (HTTP), 8443 (HTTPS) |
| MySQL | `vite-mysql` | 3306 |
| MySQL Test | `vite-mysql-test` | 3307 |
| MongoDB | `vite-mongodb` | 27017 |

### Commandes utiles

```bash
docker compose up -d          # Démarrer tous les services
docker compose down           # Arrêter
docker compose logs -f apache # Logs Apache
docker exec -it vite-php-app bash  # Shell PHP
```

### Accès local

- Site : `http://localhost:8000` ou `https://localhost:8443`
- API : `http://localhost:8000/api/...`

---

## 8. Règles pour Copilot

### À faire

- Toujours vérifier l'arborescence avant de créer un fichier.
- Utiliser les chemins absolus `/frontend/...` (jamais relatifs) dans le HTML et JS.
- Respecter l'architecture CSS @layer : chaque nouveau style doit être dans le bon layer.
- Utiliser les design tokens de `_tokens.css` pour toute valeur de style.
- Suivre le nommage BEM pour les classes CSS.
- Ajouter `credentials: 'include'` et le header CSRF sur les requêtes mutantes.
- Créer les fichiers CSS de page dans `styles/pages/`, les composants dans `styles/components/`.
- Documenter les fonctions JS et PHP avec des JSDoc/PHPDoc.

### À ne pas faire

- **NE JAMAIS** utiliser le chemin `frontend/frontend/` (ancien chemin obsolète).
- Ne pas ajouter de framework JS (React, Vue, etc.) — le projet est en vanilla JS.
- Ne pas utiliser de CSS-in-JS ou de préprocesseur CSS (Sass, Less) — CSS pur avec @layer.
- Ne pas stocker de tokens/sessions dans `localStorage` — utiliser les cookies httpOnly.
- Ne pas modifier `base.css` pour des styles spécifiques à une page.
- Ne pas créer de fichiers en dehors de l'arborescence définie sans demander.

### Tests

- **Backend :** `docker exec vite-php-app ./vendor/bin/phpunit` depuis la racine du backend.
- **Frontend :** `cd frontend && npx vitest` pour les tests JS.

### Langue

- Le code (variables, fonctions, commentaires techniques) est en **anglais**.
- L'interface utilisateur (textes, labels, messages d'erreur affichés) est en **français**.
- Les commentaires explicatifs dans le code peuvent être en français.

---

## 9. Audit JS — Dette technique & améliorations planifiées

> Audit réalisé le 12/02/2026 sur l'ensemble des fichiers `js/`. Les items ci-dessous constituent la roadmap de refactoring à appliquer progressivement.

### 9.1 Nommage incohérent des services

**Problème :** Le fichier `admin.service.js` (avec point) ne respecte pas la convention `camelCase.js` utilisée par tous les autres services (`authService.js`, `menuService.js`, etc.).

**Action :** Renommer `admin.service.js` → `adminService.js` et mettre à jour toutes les balises `<script>` qui le référencent (dans `dashboard.html` et tout fichier qui le charge).

### 9.2 Pattern mixte class / objet littéral pour les services

**Problème :** `CommandeService` et `AvisService` sont définis avec `class` + instanciation (`new CommandeService()`), tandis que tous les autres (`AuthService`, `MenuService`, `PlatService`, `AdminService`) sont des objets littéraux `const XService = { ... }`.

**Pattern cible (objet littéral) :**
```javascript
const CommandeService = {
    async getAll() { ... },
    async create(data) { ... },
};
```
**Action :** Convertir `CommandeService` et `AvisService` du pattern `class` vers un objet littéral pour unifier le style.

### 9.3 Code dupliqué — `escapeHtml()`

**Problème :** La fonction `escapeHtml()` est copiée-collée dans **4 fichiers** : `pages/profil.js`, `pages/home-menus.js`, `pages/menu-detail.js`, `admin/dashboard.js`. Toute correction (ex: ajout de l'échappement de `"` et `'`) doit être propagée manuellement.

**Action :** Créer `js/utils/helpers.js` contenant une version unique et complète :
```javascript
/**
 * Échappe les caractères HTML dangereux pour prévenir les XSS.
 * @param {string} str - Chaîne à échapper
 * @returns {string} Chaîne sécurisée
 */
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}
```
Charger ce fichier **avant** les scripts de page dans chaque HTML :
```html
<script src="/frontend/js/utils/helpers.js"></script>
```
Supprimer toutes les copies locales de `escapeHtml()`.

### 9.4 Code dupliqué — `formatPrice()`

**Problème :** La fonction `formatPrice()` est dupliquée dans `pages/commande.js` et `pages/menu-detail.js` avec des implémentations légèrement différentes.

**Action :** Ajouter `formatPrice()` dans `js/utils/helpers.js` :
```javascript
/**
 * Formate un nombre en prix EUR (ex: "12,50 €").
 * @param {number} price
 * @returns {string}
 */
function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}
```

### 9.5 Code dupliqué — toggle visibilité mot de passe

**Problème :** Le code de toggle password (icône œil) est dupliqué quasi identique dans `pages/connexion.js` et `pages/inscription.js`.

**Action :** Créer `js/utils/password-toggle.js` :
```javascript
/**
 * Initialise le toggle de visibilité sur un champ mot de passe.
 * @param {string} inputId - ID du champ input
 * @param {string} toggleId - ID du bouton/icône toggle
 */
function initPasswordToggle(inputId, toggleId) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    if (!input || !toggle) return;
    toggle.addEventListener('click', () => {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        toggle.classList.toggle('fa-eye');
        toggle.classList.toggle('fa-eye-slash');
    });
}
```
Charger ce fichier dans les pages connexion et inscription, puis appeler `initPasswordToggle('password', 'togglePassword')`.

### 9.6 Monolithe `dashboard.js` (1 525 lignes)

**Problème :** Le fichier `dashboard.js` concentre la logique de **tous les onglets admin** (menus, plats, commandes, avis, employés, stats) dans un seul fichier de 1 525 lignes. Cela rend la maintenance et le débogage très difficiles.

**Action (refactoring progressif) :** Découper en modules par onglet :
```
js/admin/
├── dashboard.js           # Orchestrateur (init, gestion onglets, fonctions communes)
├── dashboard-menus.js     # Gestion des menus
├── dashboard-plats.js     # Gestion des plats
├── dashboard-commandes.js # Gestion des commandes
├── dashboard-avis.js      # Gestion des avis
├── dashboard-employes.js  # Gestion des employés
└── dashboard-stats.js     # Statistiques et graphiques
```
Charger chaque module dans `dashboard.html`. Ce refactoring est **haute priorité** mais **haut effort**.

### 9.7 Arborescence cible de `js/`

Structure finale organisée — **aucun fichier orphelin à la racine de `js/`** :

```
js/
├── core/                      # Infrastructure de l'app
│   ├── components.js          # Chargement dynamique navbar/footer
│   └── navbar.js              # Logique menu mobile
│
├── pages/                     # Scripts de page (1 fichier = 1 page)
│   ├── home-menus.js
│   ├── connexion.js
│   ├── inscription.js
│   ├── profil.js
│   ├── commande.js
│   ├── menu-detail.js
│   └── motdepasse-oublie.js
│
├── widgets/                   # Composants UI réutilisables
│   ├── avis-carousel.js
│   ├── menus-carousel.js
│   └── demo-cube.js
│
├── auth/
│   └── auth-navbar.js
├── guards/
│   └── adminGuard.js
├── services/
│   ├── authService.js
│   ├── menuService.js
│   ├── commandeService.js
│   ├── avisService.js
│   ├── platService.js
│   └── adminService.js
├── utils/
│   ├── helpers.js             # escapeHtml, formatPrice, formatDate
│   ├── logger.js              # Logging conditionnel (dev/prod)
│   ├── password-toggle.js     # Toggle visibilité mot de passe
│   └── toast.js               # Notifications toast
└── admin/                     # Découpage dashboard par onglet
    ├── dashboard.js
    ├── dashboard-menus.js
    ├── dashboard-plats.js
    ├── dashboard-commandes.js
    ├── dashboard-avis.js
    ├── dashboard-employes.js
    └── dashboard-stats.js
```

### 9.8 Réorganisation de `js/` — déplacement vers `core/`, `pages/`, `widgets/`

**Problème :** Les 12 fichiers à la racine de `js/` mélangent 3 catégories (infrastructure, pages, widgets) sans organisation. Pour un projet professionnel, chaque fichier doit avoir sa place dans un sous-dossier par responsabilité.

**Déplacements à effectuer :**

| Fichier actuel (racine `js/`) | Destination | Catégorie |
|---|---|---|
| `components.js` | `core/components.js` | Infrastructure |
| `navbar.js` | `core/navbar.js` | Infrastructure |
| `home-menus.js` | `pages/home-menus.js` | Script de page |
| `connexion.js` | `pages/connexion.js` | Script de page |
| `inscription.js` | `pages/inscription.js` | Script de page |
| `profil.js` | `pages/profil.js` | Script de page |
| `commande.js` | `pages/commande.js` | Script de page |
| `menu-detail.js` | `pages/menu-detail.js` | Script de page |
| `motdepasse-oublie.js` | `pages/motdepasse-oublie.js` | Script de page |
| `avis-carousel.js` | `widgets/avis-carousel.js` | Widget UI réutilisable |
| `menus-carousel.js` | `widgets/menus-carousel.js` | Widget UI réutilisable |
| `demo-cube.js` | `widgets/demo-cube.js` | Widget UI réutilisable |

**Fichiers HTML à mettre à jour (balises `<script src>`) :**

| Page HTML | Anciens chemins → Nouveaux chemins |
|---|---|
| `home.html` | `/frontend/js/components.js` → `/frontend/js/core/components.js` |
| | `/frontend/js/navbar.js` → `/frontend/js/core/navbar.js` |
| | `/frontend/js/demo-cube.js` → `/frontend/js/widgets/demo-cube.js` |
| | `/frontend/js/avis-carousel.js` → `/frontend/js/widgets/avis-carousel.js` |
| | `/frontend/js/menus-carousel.js` → `/frontend/js/widgets/menus-carousel.js` |
| | `/frontend/js/home-menus.js` → `/frontend/js/pages/home-menus.js` |
| `connexion.html` | `components.js` → `core/components.js`, `navbar.js` → `core/navbar.js`, `connexion.js` → `pages/connexion.js` |
| `inscription.html` | `components.js` → `core/components.js`, `navbar.js` → `core/navbar.js`, `inscription.js` → `pages/inscription.js` |
| `profil.html` | `components.js` → `core/components.js`, `navbar.js` → `core/navbar.js`, `profil.js` → `pages/profil.js` |
| `commande.html` | `components.js` → `core/components.js`, `navbar.js` → `core/navbar.js`, `commande.js` → `pages/commande.js` |
| `menu-detail.html` | `components.js` → `core/components.js`, `navbar.js` → `core/navbar.js`, `menu-detail.js` → `pages/menu-detail.js` |
| `motdepasse-oublie.html` | `components.js` → `core/components.js`, `navbar.js` → `core/navbar.js`, `motdepasse-oublie.js` → `pages/motdepasse-oublie.js` |
| `admin/dashboard.html` | `components.js` → `core/components.js`, `navbar.js` → `core/navbar.js` |

**Test Vitest à mettre à jour :**
- `frontend/tests/dom/reset-form.test.js` : modifier l'import `../../js/motdepasse-oublie.js` → `../../js/pages/motdepasse-oublie.js`

**Documentation à mettre à jour :**
- `Docs/fichiers_perso/avancement.md` (référence à `components.js`)
- `Docs/fichiers_perso/README_CSRF.md` (référence à `components.js`)
- `Docs/fichiers_perso/ROADMAP.md` (référence à `commande.js`)

**Objectif :** Zéro fichier orphelin à la racine de `js/`. Chaque fichier est catégorisé dans un sous-dossier sémantique.

---

## 10. Audit JS — Vulnérabilités & corrections de sécurité

> Classées par sévérité. À corriger **avant toute mise en production**.

### 10.1 XSS via `innerHTML` avec données non échappées (HAUTE)

**Fichiers affectés :**
- `pages/profil.js` (ligne ~32) : `innerHTML` avec `error.message` brut
- `pages/home-menus.js` (ligne ~82) : `innerHTML` avec `error.message` brut
- `pages/menu-detail.js` (ligne ~74) : `escapeHtml()` incomplète (ne gère pas `"` et `'`)
- `admin/dashboard.js` (ligne ~1119) : `innerHTML` avec données potentiellement non échappées

**Risque :** Un attaquant peut injecter du HTML/JS via les messages d'erreur renvoyés par l'API si celle-ci est compromise ou si un proxy intermédiaire modifie la réponse.

**Correction :**
```javascript
// ❌ AVANT (dangereux)
container.innerHTML = `<p class="error">${error.message}</p>`;

// ✅ APRÈS (sécurisé)
container.innerHTML = `<p class="error">${escapeHtml(error.message)}</p>`;
```
Utiliser `escapeHtml()` de `utils/helpers.js` systématiquement sur toute donnée dynamique injectée dans le DOM.

### 10.2 `escapeHtml()` incomplète dans `pages/menu-detail.js` (HAUTE)

**Problème :** L'implémentation locale de `escapeHtml()` dans `pages/menu-detail.js` ne gère que `&`, `<`, `>` mais **pas** `"` ni `'`. Cela laisse une porte ouverte aux injections via les attributs HTML.

**Implémentation vulnérable :**
```javascript
// ❌ Incomplète
function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;' };
    return text.replace(/[&<>]/g, m => map[m]);
}
```

**Correction :** Remplacer par la version centralisée dans `utils/helpers.js` (voir §9.3) qui utilise `document.createTextNode()` — méthode infaillible.

### 10.3 Redirections 401 cassées (MOYENNE)

**Fichiers affectés :**
- `menuService.js` (ligne ~21) : redirige vers `/connexion.html?error=session_expired`
- `platService.js` (ligne ~11) : redirige vers `/connexion.html?error=session_expired`

**Problème :** Ces chemins manquent le préfixe `/frontend/pages/` → la redirection aboutit sur une 404.

**Correction :**
```javascript
// ❌ AVANT (404)
window.location.href = '/connexion.html?error=session_expired';

// ✅ APRÈS
window.location.href = '/frontend/pages/connexion.html?error=session_expired';
```

### 10.4 `credentials: 'include'` manquant sur certains `fetch` (MOYENNE)

**Problème :** Certaines requêtes GET dans les services n'incluent pas `credentials: 'include'`. Sans ce flag, les cookies de session ne sont pas envoyés, et les endpoints protégés échouent silencieusement.

**Règle :** **Tous** les `fetch()` vers `/api/*` doivent inclure `credentials: 'include'` :
```javascript
const response = await fetch('/api/menus', {
    credentials: 'include'
});
```

### 10.5 `console.error` en production (BASSE)

**Problème :** De nombreux `catch` contiennent `console.error(...)` qui expose des détails techniques dans la console du navigateur en production.

**Action :** Remplacer par un logging conditionnel ou supprimer :
```javascript
// Option 1 : Conditionnel
if (window.location.hostname === 'localhost') {
    console.error('Debug:', error);
}

// Option 2 : Supprimer et afficher un message utilisateur à la place
showErrorToast('Une erreur est survenue. Réessayez plus tard.');
```

### 10.6 Utilisation de `alert()` natif (BASSE)

**Fichiers :** `pages/connexion.js`, `pages/inscription.js`, `pages/profil.js`, `pages/commande.js`

**Problème :** `alert()` est bloquant, non stylable, et donne un aspect non professionnel.

**Action :** Remplacer par un système de toast/notification CSS :
```javascript
// ❌ AVANT
alert('Inscription réussie !');

// ✅ APRÈS
showToast('Inscription réussie !', 'success');
```
Créer un composant `js/utils/toast.js` + `styles/components/toast.css` pour centraliser les notifications.

---

## 11. Priorités d'implémentation

| # | Action | Sévérité | Effort | Fichiers |
|---|--------|----------|--------|----------|
| 1 | Corriger redirections 401 cassées (§10.3) | **Bug** | Faible | `menuService.js`, `platService.js` |
| 2 | Échapper `error.message` dans innerHTML (§10.1) | **Haute** | Faible | `pages/profil.js`, `pages/home-menus.js` |
| 3 | Compléter `escapeHtml` dans menu-detail (§10.2) | **Haute** | Faible | `pages/menu-detail.js` |
| 4 | Créer `utils/helpers.js` + supprimer doublons (§9.3, §9.4) | Moyenne | Moyen | 6+ fichiers |
| 5 | Ajouter `credentials: 'include'` manquants (§10.4) | Moyenne | Faible | Services |
| 6 | Renommer `admin.service.js` → `adminService.js` (§9.1) | Cosmétique | Faible | 2 fichiers |
| 7 | Créer `utils/password-toggle.js` (§9.5) | Cosmétique | Faible | 3 fichiers |
| 8 | Unifier pattern class → objet (§9.2) | Cosmétique | Moyen | 2 fichiers |
| 9 | Remplacer `alert()` par toasts (§10.6) | UX | Moyen | 4+ fichiers |
| 10 | Découper `dashboard.js` (§9.6) | Maintenabilité | **Élevé** | 7+ fichiers |
| 11 | Réorganiser `js/` en `core/`, `pages/`, `widgets/` (§9.8) | Architecture | Moyen | 12 fichiers + 8 HTML + 1 test |

> **Stratégie recommandée :** Corriger les items 1-3 immédiatement (bugs/sécu), puis 4-7 (qualité rapide), puis 8-10 (refactoring profond).
