# Gestion de Projet — Vite & Gourmand

> **Version :** 1.0.0  
> **Date :** 18 février 2026  
> **Auteur :** Maxime ROE  
> **Durée du projet :** ~2,5 mois (mi-novembre 2025 → février 2026)

---

## Table des matières

1. [Présentation du projet](#1-présentation-du-projet)
2. [Méthodologie de gestion de projet](#2-méthodologie-de-gestion-de-projet)
3. [Outil de gestion de projet — Trello](#3-outil-de-gestion-de-projet--trello)
4. [Découpage en phases](#4-découpage-en-phases)
5. [Planning et jalons](#5-planning-et-jalons)
6. [Organisation Git](#6-organisation-git)
7. [Stratégie de tests](#7-stratégie-de-tests)
8. [Intégration continue et déploiement (CI/CD)](#8-intégration-continue-et-déploiement-cicd)
9. [Difficultés rencontrées et solutions](#9-difficultés-rencontrées-et-solutions)
10. [Bilan et perspectives](#10-bilan-et-perspectives)

---

## 1. Présentation du projet

**Vite & Gourmand** est une application web développée pour une entreprise de traiteur/catering basée à Bordeaux. L'objectif est de permettre aux clients de consulter les menus, passer des commandes en ligne, et aux employés/administrateurs de gérer l'ensemble de l'activité (menus, plats, commandes, avis, matériel, statistiques).

### Périmètre fonctionnel

| Domaine | Fonctionnalités principales |
|---------|----------------------------|
| **Vitrine** | Page d'accueil, présentation entreprise, avis clients validés, horaires |
| **Catalogue** | Vue globale des menus, filtres (prix, thème, régime, personnes), vue détaillée |
| **Authentification** | Inscription, connexion, réinitialisation mot de passe, gestion profil |
| **Commandes** | Passage de commande, calcul prix (livraison, réduction), suivi, modification/annulation |
| **Avis** | Dépôt d'avis après commande terminée, validation par employé/admin |
| **Gestion matériel** | Prêt de matériel, suivi retour (10 jours ouvrés), relance automatique |
| **Admin/Employé** | Dashboard avec onglets (menus, plats, commandes, avis, horaires, matériel, employés, statistiques) |
| **Contact** | Formulaire de contact avec envoi email |
| **Légal** | Mentions légales, CGV, politique cookies |

### Stack technique retenue

| Couche | Technologies |
|--------|-------------|
| **Frontend** | HTML5, CSS pur (`@layer`), JavaScript vanilla |
| **Backend** | PHP 8+ vanilla, architecture MVC/Service/Repository, PHP-DI |
| **BDD relationnelle** | MySQL 8 (20 tables, 3 vues, 2 triggers) |
| **BDD NoSQL** | MongoDB 4.4 (avis, statistiques) |
| **Infrastructure** | Docker Compose (8 services), Apache + PHP-FPM |
| **CI/CD** | GitHub Actions (4 workflows) |
| **Déploiement** | Azure App Service + Azure Blob Storage + Cosmos DB |
| **Tests** | PHPUnit (backend), Vitest (frontend), Postman/Newman (API) |

---

## 2. Méthodologie de gestion de projet

### Approche Kanban

Le projet a été géré avec une **approche Kanban**, adaptée au contexte d'un développeur unique travaillant sur un projet avec des livrables définis. Ce choix se justifie par :

- **Flexibilité** : pas de sprints fixes, les tâches avancent selon leur priorité et les contraintes techniques découvertes en cours de route
- **Visualisation** : le tableau Trello offre une vue d'ensemble permanente de l'avancement
- **Flux continu** : les tâches passent de « À faire » → « En cours » → « Terminé » sans cérémonie superflue
- **Priorisation dynamique** : possibilité de réordonner les tâches selon les découvertes (ex: audit de sécurité qui fait remonter des urgences)

### Principes appliqués

1. **Limiter le travail en cours (WIP)** : 1 à 2 tâches maximum en parallèle pour maintenir la qualité
2. **Livraison incrémentale** : chaque fonctionnalité est développée, testée et mergée avant de passer à la suivante
3. **Documentation continue** : chaque module est documenté au fil du développement (guides, READMEs techniques)
4. **Audit régulier** : audit de sécurité et audit de qualité JS réalisés pour identifier la dette technique

---

## 3. Outil de gestion de projet — Trello

### Organisation du tableau

Le tableau Trello est organisé en colonnes représentant le flux Kanban :

| Colonne | Description |
|---------|-------------|
| **📋 Backlog** | Toutes les tâches identifiées, non encore priorisées |
| **📌 À faire** | Tâches priorisées pour la phase en cours |
| **🔄 En cours** | Tâches actuellement en développement (limite WIP : 2) |
| **🔍 En revue / Test** | Tâches terminées en attente de validation (tests, relecture) |
| **✅ Terminé** | Tâches livrées et validées |

### Labels utilisés

| Label | Signification |
|-------|---------------|
| 🟣 **Analyse** | Modélisation, diagrammes, règles de gestion |
| 🔵 **Backend** | Développement PHP (controllers, services, repositories) |
| 🟢 **Frontend** | Développement HTML/CSS/JS |
| 🟡 **Tests** | Écriture de tests unitaires, d'intégration, E2E |
| 🔴 **Bug** | Correction de bug |
| 🟠 **Infra** | Docker, CI/CD, déploiement Azure |
| ⚪ **Documentation** | Rédaction de docs techniques, diagrammes |
| 🟤 **Sécurité** | Audit, corrections de vulnérabilités |

---

## 4. Découpage en phases

Le projet a été découpé en **7 phases séquentielles** avec des chevauchements naturels entre certaines phases :

### Phase 1 — Analyse et modélisation (novembre — 11 décembre 2025)

**Objectif :** Comprendre les besoins, modéliser les données et documenter l'architecture cible.

| Livrable | Description | Statut |
|----------|-------------|--------|
| Analyse de l'énoncé | Identification des acteurs, cas d'utilisation, règles de gestion | ✅ |
| MCD | Modèle Conceptuel de Données — 12 entités, 38 règles de gestion | ✅ |
| MLD | Modèle Logique de Données — 20 tables MySQL | ✅ |
| Diagramme de classes UML | Architecture OOP — Controllers, Services, Repositories, Models | ✅ |
| Diagramme de cas d'utilisation | 35 cas d'utilisation (4 acteurs : Visiteur, Utilisateur, Employé, Admin) | ✅ |
| Diagrammes de séquence | 5 diagrammes (inscription/connexion, commande, statuts, avis, suivi) | ✅ |
| Scripts SQL | `database_creation.sql` (schéma) + `database_fixtures.sql` (données test) | ✅ |
| Setup MongoDB | Collections `avis` + `statistiques_commandes` avec validation JSON | ✅ |

**Outils :** Mermaid (diagrammes intégrés au Markdown), MySQL Workbench, documentation Markdown.

### Phase 2 — Documentation technique initiale (décembre 2025)

**Objectif :** Produire la documentation technique, de déploiement et les choix technologiques.

| Livrable | Description | Statut |
|----------|-------------|--------|
| Documentation technique | Choix technologiques, architecture, sécurité, flux métier | ✅ |
| Documentation de déploiement | Installation locale, Docker, configuration | ✅ |
| README.md | Guide de démarrage rapide | ✅ |

### Phase 3 — Design et UX (mi-décembre 2025 — début janvier 2026)

**Objectif :** Définir l'identité visuelle et concevoir les interfaces.

| Livrable | Description | Statut |
|----------|-------------|--------|
| Charte graphique | Palette couleurs (#FC7200 orange CTA, #2C3E50 navy), police Inter | ✅ |
| Design tokens | Variables CSS centralisées (`_tokens.css`) | ✅ |
| Architecture CSS | Système `@layer` (base, utilities, components, layouts, pages), convention BEM | ✅ |
| Wireframes | 3 pages × 2 formats (desktop + mobile) : Accueil, Inscription, Commande | ✅ |
| Maquettes | 3 pages × 2 formats correspondant aux wireframes | ✅ |

**Outils :** Figma (wireframes et maquettes), CSS natif avec design tokens.

### Phase 4 — Développement backend (janvier 2026)

**Objectif :** Implémenter l'API REST complète avec toutes les règles métier.

| Module | Composants développés | Statut |
|--------|----------------------|--------|
| **Core** | `Router`, `Request`, `Response`, `Database`, `MongoDB` | ✅ |
| **Authentification** | `AuthController`, `AuthService`, `AuthMiddleware`, JWT cookie HttpOnly, CSRF Double-Submit | ✅ |
| **Menus & Plats** | `MenuController`, `PlatController`, `MenuService`, `PlatService`, `MenuValidator`, `PlatValidator` | ✅ |
| **Commandes** | `CommandeController`, `CommandeService`, `CommandeValidator`, calcul prix, cycle de vie 8 statuts | ✅ |
| **Avis** | `AvisController`, `AvisService`, dual MySQL/MongoDB avec fallback | ✅ |
| **Admin** | `AdminController`, `StatsController`, gestion employés, statistiques | ✅ |
| **Contacts** | `ContactController`, `ContactService`, `ContactValidator` | ✅ |
| **Horaires** | `HoraireController`, `HoraireRepository`, `HoraireValidator` | ✅ |
| **Matériel** | `MaterielController`, `MaterielRepository`, `MaterielValidator`, prêt/retour matériel | ✅ |
| **Upload** | `UploadController`, `StorageService` (local + Azure Blob) | ✅ |
| **Emails** | `MailerService` (PHPMailer) — bienvenue, confirmation commande, caution matériel, reset mot de passe | ✅ |
| **Sécurité** | 6 middlewares (Auth, CORS, CSRF, RateLimit, Role, SecurityHeaders) | ✅ |
| **Conteneur DI** | PHP-DI avec injection de PDO, MongoDB, Monolog, Google Maps, config | ✅ |

**Bilan backend :** 11 Controllers, 11 Services, 12 Repositories, 10 Validators, 6 Middlewares, 6 Exceptions, 7 Models — **46 endpoints API**.

### Phase 5 — Développement frontend (janvier — février 2026)

**Objectif :** Construire toutes les pages et interactions en HTML/CSS/JS vanilla.

| Module | Composants développés | Statut |
|--------|----------------------|--------|
| **Infrastructure** | `components.js` (chargement dynamique navbar/footer), `navbar.js` (mobile) | ✅ |
| **Pages publiques** | `home.html`, `menu-detail.html`, `contact.html`, `mentions-legales.html` | ✅ |
| **Pages auth** | `connexion.html`, `inscription.html`, `motdepasse-oublie.html` | ✅ |
| **Pages utilisateur** | `profil.html` (commandes, avis, infos personnelles), `commande.html` | ✅ |
| **Dashboard admin** | `dashboard.html` + 8 modules JS (menus, plats, commandes, avis, horaires, matériel, employés, stats) | ✅ |
| **Services JS** | 6 services (Auth, Menu, Commande, Avis, Plat, Admin) | ✅ |
| **Widgets** | Carousel avis, carousel menus, cube 3D, scroll-parallax, scroll-reveal, counter-animate, skeleton screens | ✅ |
| **Utilitaires** | `helpers.js`, `toast.js`, `logger.js`, `password-toggle.js`, `skeleton.js` | ✅ |
| **Sécurité frontend** | `adminGuard.js` (protection pages admin), `auth-navbar.js` (état connecté), CSRF header automatique | ✅ |

**Bilan frontend :** 10 pages HTML, 41 fichiers JS organisés en 8 dossiers, 26 fichiers CSS.

### Phase 6 — Tests (janvier — février 2026)

**Objectif :** Assurer la fiabilité du code avec des tests automatisés.

| Type de tests | Outil | Couverture |
|---------------|-------|-----------|
| **Backend unitaires** | PHPUnit | 32 fichiers : 10 Controllers, 7 Services, 7 Validators, 3 Middlewares, 3 Core, 2 Exceptions |
| **Frontend unitaires** | Vitest | 20 fichiers : 6 services, 3 widgets, 4 DOM, 7 unit |
| **API / intégration** | Postman/Newman | 10 collections : commande, inscription, login, logout, contact, menus/plats E2E, password reset |
| **Emails** | GitHub Actions | Workflow dédié `email-integration.yml` |
| **BDD de test** | Docker | MySQL test isolé (:3307), MongoDB test isolé (:27018) |

### Phase 7 — Infrastructure et déploiement (février 2026)

**Objectif :** Conteneuriser l'application et déployer en production.

| Livrable | Description | Statut |
|----------|-------------|--------|
| Docker Compose | 8 services (PHP-FPM, Apache, MySQL×2, MongoDB×2, phpMyAdmin, Mongo Express) | ✅ |
| CI/CD | 4 workflows GitHub Actions (tests backend, tests frontend, publication image, déploiement) | ✅ |
| Déploiement Azure | App Service + Blob Storage + Cosmos DB (MongoDB) | ✅ |
| SSL/HTTPS | Certificats auto-signés (dev), Azure-managed (prod) | ✅ |

---

## 5. Planning et jalons

### Chronologie du projet

```
Nov. 2025 ─────── Déc. 2025 ─────── Jan. 2026 ─────── Fév. 2026 ──────
│                  │                  │                  │
│  Début analyse   │                  │                  │
│  des besoins     │                  │                  │
│                  ├─ 11 déc          ├─ 4 jan           ├─ 12 fév
│                  │  Phase 1+2 ✅    │  Début frontend  │  Audit JS
│                  │  Diagrammes      │  (header/footer) │  (dette technique)
│                  │  + Doc technique  │                  │
│                  │                  ├─ 25 jan          ├─ 16 fév
│                  ├─ Mi-déc          │  Module commande │  Bug modales
│                  │  Phase 3 Design  │  backend v1.2    │  (refactoring CSS)
│                  │  (maquettes)     │                  │
│                  │                  ├─ Jan-fév         ├─ 18 fév
│                  │                  │  Tests PHPUnit   │  Mise à jour docs
│                  │                  │  + Vitest + CI   │  techniques
│                  │                  │                  │
│                  │                  ├─ Fév             │
│                  │                  │  Docker + Azure  │
│                  │                  │  déploiement     │
```

### Jalons clés

| Date | Jalon |
|------|-------|
| Nov. 2025 | Démarrage de l'analyse des besoins et modélisation |
| 11 déc. 2025 | Phases 1 et 2 terminées (diagrammes, SQL, documentation initiale) |
| Mi-déc. 2025 | Charte graphique et maquettes terminées |
| Début jan. 2026 | Premiers développements frontend (navbar, footer, page d'accueil) |
| 25 jan. 2026 | Module commande backend terminé (v1.2 avec matériel) |
| Jan.–fév. 2026 | Tests automatisés mis en place (PHPUnit + Vitest) |
| Fév. 2026 | Docker Compose opérationnel, CI/CD GitHub Actions, déploiement Azure |
| 12 fév. 2026 | Audit JS : identification de la dette technique et roadmap de corrections |
| 16 fév. 2026 | Identification et correction du bug modales dashboard |
| 18 fév. 2026 | Mise à jour complète de la documentation technique |

---

## 6. Organisation Git

### Stratégie de branches

Le projet suit un modèle **Git Flow simplifié** avec 3 niveaux de branches :

```
main (production)
 └── develop (intégration)
      ├── feat/header-footer
      ├── feat/auth
      ├── feat/commandes
      ├── feat/dashboard-admin
      ├── feat/...
      └── fix/...
```

| Branche | Rôle | Protection |
|---------|------|-----------|
| `main` | Version stable de production | Protégée — merge uniquement via Pull Request depuis `develop` |
| `develop` | Branche d'intégration | CI automatique — déclenche le déploiement Azure au push |
| `feat/*` | Branches de fonctionnalité | Créées depuis `develop`, mergées vers `develop` après tests |
| `fix/*` | Corrections de bugs | Même flux que `feat/*` |

### Flux de travail Git

1. **Créer** une branche `feat/nom-fonctionnalité` depuis `develop`
2. **Développer** la fonctionnalité avec des commits descriptifs
3. **Tester** localement (PHPUnit, Vitest, tests manuels)
4. **Push** vers le dépôt distant → les workflows CI s'exécutent automatiquement
5. **Créer une Pull Request** vers `develop`
6. **Merge** après validation des tests CI
7. **Merge periodique** de `develop` vers `main` pour les releases stables

### Déclencheurs CI

| Événement | Workflows déclenchés |
|-----------|---------------------|
| Push sur `main`, `develop`, `feat/*` | `test-backend.yml`, `frontend-tests.yml` |
| Pull Request vers `main`, `develop` | `test-backend.yml`, `frontend-tests.yml` |
| Push sur `develop` | `deploy-azure.yml` (build → GHCR → Azure) |
| Changements dans `frontend/**` | `frontend-tests.yml` |

---

## 7. Stratégie de tests

### Pyramide de tests

Le projet applique une pyramide de tests classique :

```
         ╱ ╲
        ╱ E2E ╲          ← Postman/Newman (10 collections)
       ╱───────╲
      ╱ Intégr.  ╲       ← PHPUnit Controllers (10 fichiers)
     ╱─────────────╲
    ╱   Unitaires    ╲    ← PHPUnit Services/Validators (14) + Vitest (20)
   ╱───────────────────╲
```

### Tests backend — PHPUnit

**32 fichiers** de tests couvrant toutes les couches :

| Couche | Fichiers | Exemples |
|--------|----------|----------|
| Controllers | 10 | `AuthControllerTest`, `CommandeControllerTest`, `MenuControllerTest`, `AccessControlTest` |
| Services | 7 | `AuthServiceTest`, `CommandeServiceTest`, `GoogleMapsServiceTest`, `MailerServiceTest` |
| Validators | 7 | `CommandeValidatorTest`, `MenuValidatorTest`, `UserValidatorTest`, `ContactValidatorTest` |
| Middlewares | 3 | `CorsMiddlewareTest`, `RateLimitMiddlewareTest`, `SecurityHeadersMiddlewareTest` |
| Core | 3 | `RouterTest`, `RequestTest`, `ResponseTest` |
| Exceptions | 2 | `AuthExceptionTest`, `InvalidCredentialsExceptionTest` |

**Commande :** `docker exec vite-php-app ./vendor/bin/phpunit`

### Tests frontend — Vitest

**20 fichiers** de tests + 4 helpers :

| Catégorie | Fichiers | Exemples |
|-----------|----------|----------|
| Services API | 6 | `authService.test.js`, `commandeService.test.js`, `menuService.test.js` |
| Widgets | 3 | `avis-carousel.test.js`, `demo-cube.test.js`, `menus-carousel.test.js` |
| DOM (formulaires) | 4 | `inscription-form.test.js`, `connexion-form.test.js`, `contact-form.test.js` |
| Unit (utilitaires) | 7 | `helpers.test.js`, `toast.test.js`, `logger.test.js`, `adminGuard.test.js` |

**Commande :** `cd frontend && npx vitest`

### Tests API — Postman/Newman

**10 collections** couvrant les parcours critiques :

| Collection | Scénario couvert |
|------------|-----------------|
| `inscription` | Inscription utilisateur + validation |
| `login` | Connexion + récupération cookies |
| `logout` | Déconnexion + suppression cookies |
| `commande` | Cycle complet de commande |
| `contact` | Envoi formulaire de contact |
| `e2e_menus_plats` | CRUD menus et plats |
| `e2e_password_reset` | Flux de réinitialisation mot de passe |

### Base de données de test isolée

Les tests s'exécutent sur des instances de BDD dédiées pour ne pas polluer les données de développement :

| Service | Port | Usage |
|---------|------|-------|
| `vite-mysql-test` | 3307 | MySQL de test (PHPUnit, Postman) |
| `vite-mongodb-test` | 27018 | MongoDB de test |

Configuration via `.env.test` — schéma identique à la production, données réinitialisées entre les suites.

---

## 8. Intégration continue et déploiement (CI/CD)

### Vue d'ensemble des workflows

Le projet utilise **4 workflows GitHub Actions** :

| Workflow | Déclencheur | Rôle |
|----------|------------|------|
| `test-backend.yml` | Push/PR sur main, develop, feat/* | Exécute PHPUnit + Newman avec MySQL 8 et MongoDB 4.4 en services |
| `frontend-tests.yml` | Changements dans `frontend/**` | Exécute Vitest avec Node.js 18 |
| `email-integration.yml` | Manuel / planifié | Tests d'intégration des emails transactionnels |
| `deploy-azure.yml` | Push sur `develop` | Build Docker multi-stage → publication GHCR → déploiement Azure App Service |

### Pipeline de déploiement

```
Push sur develop
     │
     ▼
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Tests backend   │────▶│  Build Docker     │────▶│  Push vers GHCR  │
│  (PHPUnit +      │     │  (multi-stage     │     │  (GitHub         │
│   Newman)        │     │   Dockerfile.     │     │   Container      │
│                  │     │   azure)          │     │   Registry)      │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                           │
┌─────────────────┐                                        │
│  Tests frontend  │                                       ▼
│  (Vitest)        │                              ┌─────────────────┐
└─────────────────┘                              │  Déploiement     │
                                                  │  Azure App       │
                                                  │  Service         │
                                                  └─────────────────┘
```

---

## 9. Difficultés rencontrées et solutions

### Difficultés techniques

| # | Difficulté | Impact | Solution apportée |
|---|-----------|--------|-------------------|
| 1 | **Incohérence JWT dans la documentation** — Les diagrammes de séquence mentionnaient `localStorage` pour le JWT alors que le code utilise des cookies HttpOnly | Documentation trompeuse pour le jury | Audit de sécurité (CRIT-01) : correction de 6 fichiers de documentation. Le JWT est bien utilisé côté serveur (HS256) mais stocké dans un cookie HttpOnly inaccessible au JavaScript |
| 2 | **Migration bcrypt → Argon2ID** — Les fixtures SQL utilisaient des hash bcrypt, incohérent avec la doc qui mentionnait Argon2ID | Mots de passe de test invalides | Régénération complète des fixtures avec `PASSWORD_ARGON2ID` (CRIT-02) |
| 3 | **Mot de passe admin en clair dans les commentaires SQL** — Le fichier de fixtures versionné contenait un commentaire exposant le mot de passe | Faille de sécurité | Migration vers un script d'initialisation utilisant une variable d'environnement (CRIT-03) |
| 4 | **Conflit CSS modales dashboard** — Deux systèmes de modales (pages classiques vs dashboard admin) partageaient la même classe `.modal` | Modales du dashboard inopérantes | Refactoring CSS avec namespacing des classes modales par contexte (16/02/2026) |
| 5 | **Stock matériel en boucle décroissante** — Pas de mécanisme de retour matériel, le stock descendait sans jamais remonter | Données incohérentes | Implémentation de `CommandeService::returnMaterial()` avec endpoints dédiés pour le prêt et la restitution |
| 6 | **Rate limiting non persistant** — Données stockées dans `/tmp`, perdues à chaque redémarrage du conteneur | Protection inefficace | Migration vers `backend/var/rate_limit/` avec volume Docker persistant et fallback en cas d'erreur d'écriture |

### Difficultés d'architecture

| # | Difficulté | Solution |
|---|-----------|---------|
| 7 | **Dual database MySQL/MongoDB** — Synchroniser les avis entre MySQL et MongoDB sans ORM | Pattern fallback : stockage primaire MongoDB, table `AVIS_FALLBACK` MySQL en cas d'indisponibilité MongoDB. Synchronisation gérée par `AvisService` |
| 8 | **CSRF cross-origin** — Les cookies `SameSite=Strict` bloquent les requêtes API en cross-origin (nécessaire pour le déploiement Azure) | Passage à `SameSite=None; Secure` en production avec CSRF Double-Submit Cookie (cookie non-HttpOnly `csrfToken` comparé au header `X-CSRF-Token`) |
| 9 | **Monolithe `dashboard.js`** (1 525 lignes) — Un seul fichier JS pour 8 onglets admin | Découpage en 9 modules : `dashboard.js` (orchestrateur) + 8 modules par onglet (`dashboard-menus.js`, `dashboard-commandes.js`, etc.) |
| 10 | **Code JS dupliqué** — `escapeHtml()` copié dans 4 fichiers, `formatPrice()` dupliqué | Centralisation dans `js/utils/helpers.js`, suppression de toutes les copies locales |

---

## 10. Bilan et perspectives

### Bilan quantitatif

| Métrique | Valeur |
|----------|--------|
| Durée du projet | ~2,5 mois |
| Pages HTML | 10 (+2 composants) |
| Endpoints API | 46 |
| Tables MySQL | 20 (+3 vues, +2 triggers) |
| Collections MongoDB | 2 |
| Fichiers JS frontend | 41 |
| Fichiers CSS | 26 |
| Fichiers PHP backend | ~60 (src/) |
| Tests backend (PHPUnit) | 32 fichiers |
| Tests frontend (Vitest) | 20 fichiers |
| Collections Postman | 10 |
| Workflows CI/CD | 4 |
| Services Docker | 8 |

### Points forts du projet

- **Architecture propre** : séparation stricte des responsabilités (MVC/Service/Repository), injection de dépendances via PHP-DI
- **Sécurité multicouche** : JWT en cookie HttpOnly, CSRF Double-Submit, CORS, CSP, Rate Limiting, Argon2ID, validation en entrée
- **Tests automatisés** : couverture sur toutes les couches (unitaires, intégration, E2E API)
- **CI/CD complet** : du push Git au déploiement Azure automatique
- **Documentation exhaustive** : documentation technique, de déploiement, manuel utilisateur, guides internes

### Axes d'amélioration identifiés

- Migration vers PHP 8.2+ (PHP 8.1 est en EOL depuis novembre 2024)
- Migration vers MongoDB 6+ (MongoDB 4.4 est en EOL)
- Ajout de headers de sécurité supplémentaires (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`)
- Amélioration de l'accessibilité RGAA (audit RGAA complet à prévoir)
- Ajout de tests E2E navigateur (Playwright ou Cypress)
- Monitoring applicatif en production (logs centralisés, alertes)

---

> **Document rédigé le 18 février 2026** dans le cadre du TP Développeur Web et Web Mobile.
