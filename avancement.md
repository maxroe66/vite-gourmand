# 📝 Suivi d'avancement - Vite & Gourmand

## 🗓️ Date: 4 janvier 2026
**Branche:** `feat/header-footer`

---

## ✅ Travaux réalisés

### 1. 🔧 Fix: Autoloader Composer dans index.php
**Fichier:** `/public/index.php`

**Problème résolu:**
- Erreur `Fatal error: Class "Dotenv\Dotenv" not found`
- Ajout de `require_once __DIR__ . '/../backend/vendor/autoload.php';` avant l'utilisation de Dotenv

---

### 2. 🎨 Création: Header/Footer avec composants réutilisables

**Architecture frontend mise en place:**
- **Composants HTML réutilisables:**
  - `/frontend/frontend/pages/components/navbar.html` - Header avec menu de navigation
  - `/frontend/frontend/pages/components/footer.html` - Footer avec liens et copyright
  
- **Chargement dynamique (JavaScript):**
  - `/frontend/js/components.js` - Loader de composants via Fetch API
  - Fonction `loadComponent()` pour charger header et footer
  - Gestion du menu mobile (toggle, fermeture auto)

**Fonctionnalités navbar:**
- Logo cliquable
- Menu de navigation (Accueil, Menu, Contact)
- Boutons CTA (Inscription, Connexion)
- Menu mobile responsive avec toggle button
- Classes CSS correctes (`button`, `button--primary`, `button--ghost`, `navbar__link`)

**Fonctionnalités footer:**
- Grille responsive 3 colonnes (Info, Navigation, Légal)
- Année dynamique en JavaScript
- Design avec fond sombre et liens stylisés

---

### 3. 📄 Page d'accueil
**Fichier:** `/frontend/frontend/pages/home.html`

**Structure:**
- Placeholders pour header et footer (chargés dynamiquement)
- Section hero avec titre et description
- Tous les CSS importés correctement

---

### 4. 🎨 Styles CSS créés

**Fichiers CSS:**
- `/frontend/frontend/styles/components/hero.css` - Section hero avec gradient
- `/frontend/frontend/styles/components/footer.css` - Footer stylisé
- **Modification de `/frontend/frontend/styles/base.css`:**
  - Body avec `min-height: 100vh`, `display: flex`, `flex-direction: column`
  - Main avec `flex: 1` pour pousser le footer en bas
  - Footer reste toujours en bas de page (sticky footer)

---

### 5. 🔄 Configuration du routeur PHP
**Fichier:** `/public/index.php`

**Fonctionnalités:**
- **Routes frontend:**
  - `/`, `/home`, `/accueil` → `home.html`
  - `/inscription` → `inscription.html` (à créer)
  - `/connexion` → `connexion.html` (à créer)
  
- **Routes API:** `/api/*` continuent de fonctionner normalement

- **Gestion des fichiers statiques:**
  - Extensions `.css`, `.js`, `.jpg`, `.png`, `.svg`, `.html`, etc. servis directement
  - Exclusion des composants (`/components/`) du routage des pages
  - Header `Content-Type: application/json` uniquement pour l'API

---

### 6. ⚙️ Configuration Apache
**Fichier:** `/docker/apache/vite.conf`

**Modifications:**
- **Alias ajoutés:**
  - `/frontend` → `/var/www/vite_gourmand/frontend`
  - `/assets` → `/var/www/vite_gourmand/public/assets`
  
- Permissions pour servir les fichiers en dehors de `public/`
- DocumentRoot reste `/var/www/vite_gourmand/public`

**Fichier:** `/public/.htaccess`
- Conditions de réécriture pour vérifier les fichiers dans `DOCUMENT_ROOT/../`

---

## 📍 Accès

- **Page d'accueil:** `http://localhost:8000/`
- **API Backend:** `http://localhost:8000/api/`
- **Healthcheck:** `http://localhost:8000/api/health`

---

## 🎯 Prochaines étapes

1. ✅ Header et Footer → **TERMINÉ**
2. **Page d'inscription (frontend)**
   - Créer le formulaire HTML
   - Intégrer avec l'API backend `/api/auth/register`
   - Validation côté client
3. **Page de connexion (frontend)**
   - Formulaire de connexion
   - Gestion du JWT
4. **Tests d'intégration** frontend ↔ backend
5. **Améliorer le header** pour afficher les infos utilisateur connecté

---

## 📝 Notes techniques

- **Structure frontend:** `frontend/frontend/` (double dossier à considérer pour simplification future)
- **Chargement des composants:** Système de composants HTML vanilla (sans framework)
- **CSS:** Architecture avec `@layer` (tokens, base, components)
- **Responsive:** Menu mobile fonctionnel avec toggle
- **Sticky footer:** Flexbox layout sur body/main/footer
