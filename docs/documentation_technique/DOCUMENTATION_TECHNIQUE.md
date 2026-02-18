# Documentation Technique — Vite & Gourmand

> **Version :** 2.0.0  
> **Date :** 18 février 2026  
> **Auteur :** Maxime Roé  
> **Projet :** Application web de traiteur/catering — Bordeaux

---

## Table des matières

1. [Réflexions initiales technologiques](#1-réflexions-initiales-technologiques)
2. [Architecture générale](#2-architecture-générale)
3. [Architecture backend détaillée](#3-architecture-backend-détaillée)
4. [Architecture frontend détaillée](#4-architecture-frontend-détaillée)
5. [Modèle de données](#5-modèle-de-données)
6. [API REST — Référence complète](#6-api-rest--référence-complète)
7. [Sécurité](#7-sécurité)
8. [Flux métier](#8-flux-métier)
9. [Tests](#9-tests)
10. [Performance et accessibilité](#10-performance-et-accessibilité)

---

## 1. Réflexions initiales technologiques

### 1.1 Contexte et contraintes du projet

L'énoncé du TP DWWM exige la réalisation d'une application web complète pour un traiteur bordelais (« Vite & Gourmand »), avec :

- Un **front-end** responsive (consultation menus, commande, espaces utilisateur/employé/admin)
- Un **back-end** sécurisé (API REST, gestion des rôles, envoi d'emails)
- Une **base de données relationnelle** (MySQL) et une **base NoSQL** (MongoDB)
- Un **déploiement** en ligne fonctionnel

Aucune technologie n'est imposée hormis l'obligation d'utiliser une BDD relationnelle et une BDD NoSQL. Les choix ci-dessous sont justifiés par les besoins métier, la maintenabilité et les bonnes pratiques professionnelles.

### 1.2 Choix du langage backend — PHP 8 (vanilla)

| Critère | PHP vanilla | Laravel / Symfony | Node.js (Express) |
|---|---|---|---|
| **Maîtrise du langage** | Contrôle total du code, compréhension profonde | Abstractions masquent le fonctionnement interne | Asynchrone par nature, complexe pour un MVC classique |
| **Architecture** | Libre : MVC/Service/Repository personnalisé | Imposée par le framework (conventions strictes) | Libre mais nécessite beaucoup de configuration |
| **Poids du projet** | Léger, seules les dépendances nécessaires (Composer) | Lourd (~80+ packages, 40 Mo+ de vendor) | Moyen (~node_modules volumineux) |
| **Déploiement** | Simple (PHP-FPM + Apache) | Identique mais + de configuration | Nécessite runtime Node.js |
| **Valeur pédagogique** | Démontre la maîtrise des fondamentaux (PDO, routing, DI) | Démontre la maîtrise d'un framework | Démontre la maîtrise d'un autre écosystème |
| **Performance** | Optimale pour ce volume | Overhead framework léger | Excellente en I/O, surdimensionnée ici |

**Choix retenu : PHP vanilla avec architecture MVC/Service/Repository.**

**Justification :** Dans le cadre d'un TP DWWM, il est pertinent de démontrer la capacité à construire une architecture propre sans s'appuyer sur les conventions d'un framework. L'utilisation de Composer pour les dépendances (PHP-DI, PHPUnit, firebase/php-jwt, PHPMailer, Monolog) apporte les outils industriels nécessaires sans masquer la logique applicative. Cette approche prouve la compréhension des patterns (Dependency Injection, Repository, Service Layer) que les frameworks implémentent en interne.

### 1.3 Choix des bases de données

#### MySQL 8 — Base relationnelle

| Critère | MySQL 8 | PostgreSQL | MariaDB |
|---|---|---|---|
| **Popularité / écosystème** | Leader du marché web, documentation abondante | Excellente mais moins répandue en hébergement mutualisé | Fork de MySQL, très compatible |
| **Fonctionnalités utilisées** | ENUM, triggers, vues, FK, JSON | Types avancés (JSONB, arrays), surdimensionnés ici | Quasi-identique à MySQL |
| **Outils admin** | phpMyAdmin (standard, intégré Docker) | pgAdmin | phpMyAdmin compatible |
| **Compatibilité Azure** | Azure Database for MySQL — service managé natif | Azure Database for PostgreSQL — supporté | Moins de support natif Azure |

**Choix retenu : MySQL 8** — standard de l'industrie web, parfaitement adapté au modèle relationnel du projet (20 tables, FK, triggers, vues), excellente intégration avec PHP/PDO et Azure.

#### MongoDB 4.4 — Base NoSQL

| Critère | MongoDB | Redis | Firebase Firestore |
|---|---|---|---|
| **Type** | Document (JSON/BSON) | Clé-valeur (in-memory) | Document (cloud) |
| **Cas d'usage projet** | Stockage avis (primaire), statistiques commandes | Cache uniquement, pas de persistance complexe | Dépendance cloud Google, vendor lock-in |
| **Requêtes** | Agrégation puissante, indexation flexible | Pas de requêtes complexes | Requêtes limitées |
| **Compatibilité Azure** | Azure Cosmos DB (API MongoDB) — migration transparente | Azure Cache for Redis | Non natif Azure |

**Choix retenu : MongoDB 4.4** — utilisé pour le stockage primaire des avis clients et les statistiques de commandes. Le pattern de dual database (MySQL pour les données relationnelles, MongoDB pour les documents flexibles) est un standard en production. Un mécanisme de fallback vers MySQL (`AVIS_FALLBACK`) assure la résilience en cas d'indisponibilité de MongoDB.

### 1.4 Choix de l'architecture backend — MVC / Service / Repository

```
┌─────────────────────────────────────────────────────────────┐
│                    Couche Présentation                       │
│              (11 Controllers — routage REST)                 │
├─────────────────────────────────────────────────────────────┤
│                    Couche Métier                             │
│         (11 Services — logique business)                    │
│         (10 Validators — validation données)                │
├─────────────────────────────────────────────────────────────┤
│                    Couche Accès Données                      │
│      (12 Repositories — requêtes SQL/NoSQL)                 │
├─────────────────────────────────────────────────────────────┤
│                    Infrastructure                            │
│   Router, Request, Response, Database, MongoDB, DI (PHP-DI) │
│   6 Middlewares, 6 Exceptions, 7 Models, Monolog            │
└─────────────────────────────────────────────────────────────┘
```

**Pourquoi cette architecture en couches ?**

| Pattern | Rôle | Bénéfice |
|---|---|---|
| **Controller** | Réception HTTP, délégation au service, retour Response JSON | Séparation requête / logique |
| **Service** | Logique métier (calcul prix, envoi email, auth JWT) | Testable unitairement, réutilisable |
| **Repository** | Requêtes SQL/NoSQL pures, mapping des résultats | Changement de BDD sans toucher la logique |
| **Validator** | Validation des données entrantes (format, contraintes) | Sécurité en entrée, messages d'erreur clairs |
| **Middleware** | Traitements transversaux (auth, CSRF, CORS, rate limit) | Code DRY, chaîne configurable par route |
| **Model** | Entités du domaine (User, Menu, Commande…) | Typage fort, documentation du domaine |
| **DI Container** (PHP-DI) | Injection automatique des dépendances | Découplage, testabilité (injection de mocks) |

### 1.5 Choix frontend — HTML/CSS/JS vanilla

| Critère | Vanilla JS | React / Vue | Angular |
|---|---|---|---|
| **Complexité** | Faible pour 10 pages | Surdimensionné (SPA pour 10 pages statiques) | Très surdimensionné |
| **Performance** | Aucun runtime JS supplémentaire, chargement rapide | Virtual DOM + bundle JS (100+ Ko min) | Bundle lourd (200+ Ko) |
| **SEO / Accessibilité** | HTML sémantique natif, pas de rendering côté client | SSR nécessaire pour le SEO | SSR complexe |
| **Valeur pédagogique** | Démontre la maîtrise du DOM, fetch API, événements | Démontre la maîtrise d'un framework | Idem |
| **Maintenance** | 41 fichiers JS bien organisés en 8 dossiers | Composants réutilisables natifs | Composants + services |

**Choix retenu : HTML statique + CSS pur (@layer) + JavaScript vanilla.**

**Justification :** Avec 10 pages HTML et des interactions modérées (filtres, formulaires, carrousels), un framework SPA serait surdimensionné. L'approche vanilla permet un chargement ultra-rapide, un HTML sémantique natif (favorable au SEO et RGAA), et démontre la maîtrise des fondamentaux web. L'organisation en 8 dossiers JS (`core/`, `pages/`, `widgets/`, `services/`, `utils/`, `admin/`, `auth/`, `guards/`) maintient une architecture claire et scalable.

### 1.6 Choix CSS — Architecture @layer

| Critère | CSS @layer | Bootstrap | Sass/SCSS | Tailwind CSS |
|---|---|---|---|---|
| **Spécificité** | Contrôle total via layers ordonnés | Classes utilitaires avec `!important` fréquents | Variables/mixins mais spécificité classique | Classes utilitaires atomiques |
| **Personnalisation** | 100% — design tokens dans `_tokens.css` | Thème limité, look « Bootstrap » | Très personnalisable | Très personnalisable |
| **Poids** | Minimal (uniquement le CSS nécessaire) | ~150 Ko min | Dépend de l'utilisation | Purge nécessaire |
| **Modernité** | Standard CSS natif (2022+) | Framework mature | Préprocesseur (compilation requise) | Framework utilitaire |

**Choix retenu : CSS pur avec architecture `@layer`** — 5 niveaux de spécificité (`base`, `utilities`, `components`, `layouts`, `pages`), design tokens centralisés, convention BEM pour le nommage des classes.

### 1.7 Choix de l'authentification — JWT en cookie HttpOnly

| Critère | JWT cookie HttpOnly | JWT localStorage | Sessions PHP classiques |
|---|---|---|---|
| **Protection XSS** | ✅ Cookie inaccessible au JS | ❌ `localStorage` lisible par tout script | ✅ Session ID en cookie HttpOnly |
| **Stateless** | ✅ Pas de stockage session serveur | ✅ Idem | ❌ Stockage session côté serveur |
| **Scalabilité** | ✅ Aucun état serveur, load balancer OK | ✅ Idem | ❌ Sessions sticky ou stockage partagé |
| **Protection CSRF** | ⚠️ Nécessite CSRF (cookie automatique) | ✅ Pas de CSRF (header manuel) | ⚠️ Nécessite CSRF |
| **Compatibilité API** | ✅ Cookie auto + fallback Bearer pour Postman | ✅ Header Bearer natif | ❌ Limité aux navigateurs |

**Choix retenu : JWT HS256 (`firebase/php-jwt`) stocké en cookie `authToken` HttpOnly + Secure + SameSite, complété par CSRF Double-Submit Cookie.**

**Justification :** Ce choix combine la nature **stateless** du JWT (pas de table sessions, scalabilité horizontale) et la **sécurité du cookie HttpOnly** (le JavaScript ne peut pas lire le token, protection XSS). Le coût est la nécessité d'une protection CSRF, implémentée via Double-Submit Cookie (`csrfToken` non-HttpOnly lu par JS + header `X-CSRF-Token`, comparés via `hash_equals()`). Un fallback Bearer dans `AuthMiddleware` supporte les clients API/Postman.

### 1.8 Choix de l'infrastructure — Docker Compose

| Critère | Docker Compose | XAMPP / WAMP | Vagrant |
|---|---|---|---|
| **Reproductibilité** | ✅ `docker-compose.yml` = environnement identique partout | ❌ Configuration manuelle par machine | ✅ VM complète mais lourde |
| **Isolation** | ✅ Conteneurs isolés, versions fixes | ❌ Versions système partagées | ✅ VM isolée |
| **Multi-services** | ✅ 8 services en 1 commande | ⚠️ Pas de MongoDB natif | ✅ Mais scripts complexes |
| **CI/CD** | ✅ Même image en dev/CI/prod | ❌ Pas de pipeline | ❌ Trop lourd pour CI |
| **Déploiement** | ✅ Image Docker → Azure App Service | ❌ Migration manuelle | ❌ Non adapté |

**Choix retenu : Docker Compose** avec 8 services (PHP-FPM, Apache, MySQL, MySQL-test, MongoDB, MongoDB-test, phpMyAdmin, Mongo Express). Le même Dockerfile sert en développement et en production (Azure App Service via GHCR).

---

## 2. Architecture générale

### 2.1 Vue d'ensemble du système

```
┌──────────────────────────────────────────────────────────────────────┐
│                         CLIENT (Navigateur)                          │
│                                                                      │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │  HTML Pages  │  │  CSS @layer  │  │  JS Services │               │
│  │  (10 pages)  │  │  (35 files)  │  │  (41 files)  │               │
│  └──────┬───────┘  └──────────────┘  └──────┬───────┘               │
│         │           fetch() + credentials: 'include'                 │
│         │           + header X-CSRF-Token (requêtes mutantes)        │
└─────────┼────────────────────────────────────┼───────────────────────┘
          │ HTML                               │ JSON API
          ▼                                    ▼
┌──────────────────────────────────────────────────────────────────────┐
│                    APACHE (vite-apache :8000/:8443)                   │
│                                                                      │
│  ┌─────────────────────┐    ┌──────────────────────────┐            │
│  │  Alias /frontend/*  │    │  ProxyPass /api/* → PHP  │            │
│  │  (fichiers statiques)│    │  (PHP-FPM :9000)         │            │
│  └─────────────────────┘    └──────────┬───────────────┘            │
└──────────────────────────────────────────┼───────────────────────────┘
                                           │
                                           ▼
┌──────────────────────────────────────────────────────────────────────┐
│                   PHP-FPM (vite-php-app :9000)                       │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────┐       │
│  │  public/index.php (Front Controller)                      │       │
│  │  ├── Routes statiques → fichiers HTML (6 URL aliases)     │       │
│  │  └── /api/* → Router                                      │       │
│  │       ├── CorsMiddleware                                   │       │
│  │       ├── SecurityHeadersMiddleware (CSP)                  │       │
│  │       ├── CsrfMiddleware (POST/PUT/PATCH/DELETE)           │       │
│  │       ├── RateLimitMiddleware (endpoints sensibles)        │       │
│  │       ├── AuthMiddleware (JWT cookie HttpOnly)             │       │
│  │       ├── RoleMiddleware (ADMIN/EMPLOYE)                   │       │
│  │       │                                                    │       │
│  │       ▼                                                    │       │
│  │  Controllers (11) → Services (11) → Repositories (12)     │       │
│  └───────┼──────────────────────────────────┼────────────────┘       │
└──────────┼──────────────────────────────────┼────────────────────────┘
           │                                  │
           ▼                                  ▼
┌────────────────────┐           ┌─────────────────────┐
│  MySQL 8 (:3306)   │           │  MongoDB 4.4 (:27017)│
│  20 tables          │           │  Collections :       │
│  3 vues, 2 triggers │           │  - avis              │
│                     │           │  - statistiques      │
└────────────────────┘           └─────────────────────┘
```

### 2.2 Flux d'une requête API

Prenons l'exemple d'une requête `POST /api/commandes` (création de commande) :

1. **Le navigateur** envoie la requête avec `credentials: 'include'` (cookie `authToken` automatique) et le header `X-CSRF-Token`
2. **Apache** reçoit la requête sur le port 8000 (HTTP) ou 8443 (HTTPS), la proxifie vers PHP-FPM via `ProxyPassMatch`
3. **`public/index.php`** (front controller) :
   - Charge l'autoloader Composer et les variables d'environnement (`.env`)
   - Initialise le conteneur DI (`container.php`)
   - Exécute les middlewares globaux (`CorsMiddleware`, `SecurityHeadersMiddleware`)
   - Détecte le préfixe `/api` → délègue au `Router`
4. **Le `Router`** résout la route `POST /commandes` → exécute les middlewares de route :
   - `RateLimitMiddleware(10, 60)` — max 10 commandes/minute
   - `CsrfMiddleware` — compare cookie `csrfToken` avec header `X-CSRF-Token` via `hash_equals()`
   - `AuthMiddleware` — décode le JWT du cookie `authToken`, attache les données `user` à la `Request`
5. **`CommandeController::store()`** reçoit la `Request` enrichie
6. **`CommandeValidator::validateCreation()`** valide les données entrantes (menu, date, adresse, nombre de personnes)
7. **`CommandeService::createCommande()`** exécute la logique métier :
   - Vérifie le stock du menu
   - Calcule le prix (réduction 10% si applicable, frais de livraison via `GoogleMapsService`)
   - Appelle `CommandeRepository::create()` pour l'insertion SQL
   - Envoie l'email de confirmation via `MailerService`
8. **Le `Controller`** retourne un objet `Response` (201 Created + JSON)
9. **Le `Router`** envoie la réponse HTTP au client

### 2.3 Front Controller — `public/index.php`

Le point d'entrée unique de l'application sert deux types de contenus :

**Routes statiques (HTML)** — URL « propres » sans extension :

| URL | Page servie |
|---|---|
| `/`, `/home`, `/accueil` | `frontend/pages/home.html` |
| `/inscription` | `frontend/pages/inscription.html` |
| `/connexion` | `frontend/pages/connexion.html` |
| `/reset-password` | `frontend/pages/motdepasse-oublie.html` |
| `/contact` | `frontend/pages/contact.html` |
| `/mentions-legales` | `frontend/pages/mentions-legales.html` |

**Routes API** — préfixe `/api/*`, gérées par le `Router` :

Toute requête commençant par `/api` est interceptée et traitée par le routeur PHP. Les routes sont définies dans les 11 fichiers `backend/api/routes.*.php`.

### 2.4 Conteneur d'injection de dépendances — PHP-DI

Le fichier `backend/config/container.php` configure l'injection automatique de toutes les dépendances :

| Dépendance | Type | Description |
|---|---|---|
| `PDO` | Infrastructure | Connexion MySQL avec SSL conditionnel (Azure) |
| `MongoDB\Client` | Infrastructure | Connexion MongoDB / Azure Cosmos DB |
| `LoggerInterface` (Monolog) | Infrastructure | Logging fichier (`backend/logs/app.log`) |
| `array $config` | Configuration | Fusion `config.php` + `.env` (JWT, SMTP, Google Maps, Azure) |
| 11 Services | Métier | Logique business injectable |
| 12 Repositories | Données | Accès BDD injectable |
| 6 Middlewares | Transversal | Traitements de requêtes |
| 10 Validators | Validation | Contrôle des entrées |
| `GoogleMapsService` | Externe | Calcul distance pour livraison hors Bordeaux |
| `StorageService` | Fichiers | Upload local (dev) ou Azure Blob Storage (prod) |

---

## 3. Architecture backend détaillée

### 3.1 Arborescence du backend

```
backend/
├── api/                        # Définitions des routes (11 fichiers)
│   ├── routes.php              # Orchestrateur principal + route /upload
│   ├── routes.auth.php         # Authentification (register, login, logout, reset)
│   ├── routes.menus.php        # Menus + Plats (CRUD complet)
│   ├── routes.commandes.php    # Commandes (cycle de vie complet)
│   ├── routes.avis.php         # Avis clients (CRUD + validation)
│   ├── routes.admin.php        # Administration (employés, stats)
│   ├── routes.contact.php      # Formulaire de contact
│   ├── routes.horaires.php     # Horaires d'ouverture
│   ├── routes.materiel.php     # Gestion du matériel
│   ├── routes.diagnostic.php   # Diagnostic MongoDB
│   └── routes.test.php         # Routes de test (dev/test uniquement)
│
├── config/
│   ├── config.php              # Configuration centralisée (JWT, SMTP, BDD, Azure)
│   └── container.php           # Conteneur DI (PHP-DI) — câblage des dépendances
│
├── database/
│   ├── sql/
│   │   ├── database_creation.sql   # Schéma complet (20 tables, 3 vues, 2 triggers)
│   │   └── database_fixtures.sql   # Données de test (13 comptes, menus, plats…)
│   └── mongoDB/
│       ├── database_mongodb_setup.js       # Setup local
│       ├── database_mongodb_setup_azure.js # Setup Azure
│       └── database_mongodb_setup_cosmosdb.js # Setup Cosmos DB
│
├── src/
│   ├── Controllers/            # 11 contrôleurs
│   │   ├── Auth/AuthController.php
│   │   ├── AdminController.php
│   │   ├── AvisController.php
│   │   ├── CommandeController.php
│   │   ├── ContactController.php
│   │   ├── HoraireController.php
│   │   ├── MaterielController.php
│   │   ├── MenuController.php
│   │   ├── PlatController.php
│   │   ├── StatsController.php
│   │   └── UploadController.php
│   │
│   ├── Core/                   # Infrastructure du framework maison
│   │   ├── Database.php        # Wrapper PDO avec gestion erreurs
│   │   ├── MongoDB.php         # Wrapper MongoDB Client
│   │   ├── Request.php         # Objet requête (params, body, attributes)
│   │   ├── Response.php        # Objet réponse JSON (status, headers, body)
│   │   └── Router.php          # Routeur REST (méthode + pattern → handler + middlewares)
│   │
│   ├── Middlewares/            # 6 middlewares
│   │   ├── AuthMiddleware.php          # Validation JWT (cookie + fallback Bearer)
│   │   ├── CorsMiddleware.php          # Cross-Origin Resource Sharing
│   │   ├── CsrfMiddleware.php          # Double Submit Cookie validation
│   │   ├── RateLimitMiddleware.php     # Limitation de débit (fichier PHP)
│   │   ├── RoleMiddleware.php          # Contrôle de rôle (ADMIN, EMPLOYE)
│   │   └── SecurityHeadersMiddleware.php # Content-Security-Policy
│   │
│   ├── Services/               # 11 services
│   │   ├── AuthService.php             # Hash Argon2ID, génération JWT, vérification
│   │   ├── AvisService.php             # CRUD avis (MongoDB primaire + MySQL fallback)
│   │   ├── CommandeService.php         # Cycle de vie commande, calcul prix
│   │   ├── ContactService.php          # Traitement formulaire contact
│   │   ├── CsrfService.php             # Génération/rotation token CSRF
│   │   ├── GoogleMapsService.php       # Calcul distance livraison (API + fallback)
│   │   ├── MailerService.php           # Envoi emails (PHPMailer + templates HTML)
│   │   ├── MenuService.php             # CRUD menus, gestion stock
│   │   ├── PlatService.php             # CRUD plats, allergènes
│   │   ├── StorageService.php          # Upload fichiers (local / Azure Blob Storage)
│   │   └── UserService.php             # CRUD utilisateurs, gestion profil
│   │
│   ├── Repositories/           # 12 repositories
│   │   ├── AllergeneRepository.php
│   │   ├── AvisRepository.php
│   │   ├── CommandeRepository.php
│   │   ├── ContactRepository.php
│   │   ├── HoraireRepository.php
│   │   ├── MaterielRepository.php
│   │   ├── MenuRepository.php
│   │   ├── PlatRepository.php
│   │   ├── RegimeRepository.php
│   │   ├── ResetTokenRepository.php
│   │   ├── ThemeRepository.php
│   │   └── UserRepository.php
│   │
│   ├── Validators/             # 10 validateurs
│   │   ├── CommandeValidator.php
│   │   ├── ContactValidator.php
│   │   ├── EmployeeValidator.php
│   │   ├── HoraireValidator.php
│   │   ├── LoginValidator.php
│   │   ├── MaterielValidator.php
│   │   ├── MenuValidator.php
│   │   ├── PlatValidator.php
│   │   ├── ResetPasswordValidator.php
│   │   └── UserValidator.php
│   │
│   ├── Models/                 # 7 modèles (entités du domaine)
│   │   ├── Avis.php
│   │   ├── Commande.php
│   │   ├── CommandeStatut.php
│   │   ├── Horaire.php
│   │   ├── Materiel.php
│   │   ├── Menu.php
│   │   └── User.php
│   │
│   └── Exceptions/             # 6 exceptions personnalisées
│       ├── AuthException.php
│       ├── CommandeException.php
│       ├── ForbiddenException.php
│       ├── InvalidCredentialsException.php
│       ├── TooManyRequestsException.php
│       └── UserServiceException.php
│
├── templates/emails/           # Templates HTML pour les emails transactionnels
├── tests/                      # Tests PHPUnit (32 fichiers)
├── logs/                       # Logs applicatifs (Monolog)
└── vendor/                     # Dépendances Composer
```

### 3.2 Contrôleurs — Rôle et responsabilité

Chaque contrôleur reçoit ses dépendances par injection (PHP-DI) et retourne un objet `Response` JSON.

| Contrôleur | Responsabilité | Services utilisés |
|---|---|---|
| `AuthController` | Inscription, connexion, déconnexion, reset mot de passe, mise à jour profil, vérification session | `AuthService`, `UserService`, `CsrfService`, `MailerService` |
| `AdminController` | Création comptes employés, listing employés, désactivation comptes | `UserService`, `MailerService` |
| `MenuController` | CRUD menus (titre, description, prix, stock, thème, régime, conditions, images) | `MenuService` |
| `PlatController` | CRUD plats (libellé, type ENTREE/PLAT/DESSERT, allergènes) | `PlatService` |
| `CommandeController` | Création, modification, annulation, changement statut, suivi, gestion matériel, calcul prix | `CommandeService`, `GoogleMapsService`, `MailerService` |
| `AvisController` | Création avis (note + commentaire), listing, validation/refus par employé | `AvisService` |
| `ContactController` | Réception formulaire contact, envoi email à l'entreprise | `ContactService`, `MailerService` |
| `HoraireController` | Listing horaires, modification par employé/admin | `HoraireRepository` |
| `MaterielController` | CRUD matériel (libellé, description, valeur, stock) | `MaterielRepository` |
| `StatsController` | Statistiques commandes par menu, chiffre d'affaires avec filtres | `CommandeRepository`, MongoDB |
| `UploadController` | Upload d'images (menus) | `StorageService` |

### 3.3 Services — Logique métier

| Service | Responsabilité clé |
|---|---|
| `AuthService` | Hash Argon2ID des mots de passe, génération JWT HS256 (payload : `iss`, `sub`, `role`, `iat`, `exp` — TTL 1h), vérification des credentials |
| `AvisService` | Stockage primaire dans MongoDB, fallback dans MySQL (`AVIS_FALLBACK`), validation par employé/admin |
| `CommandeService` | Calcul prix (réduction 10% si ≥ nombre_min + 5 personnes), vérification stock, gestion des 8 statuts, annulation avec motif obligatoire |
| `ContactService` | Enregistrement demande en BDD (table `CONTACT`) + envoi email à l'entreprise |
| `CsrfService` | Génération token 64 chars hex (cookie non-HttpOnly, TTL 2h), rotation à chaque login, comparaison `hash_equals()` |
| `GoogleMapsService` | Calcul distance depuis Bordeaux via API Google Maps, fallback estimation si API indisponible, tarification : 5€ base + 0,59€/km hors Bordeaux |
| `MailerService` | Envoi emails transactionnels via PHPMailer (SMTP) : bienvenue à l'inscription, confirmation commande, reset password, notification matériel à restituer, invitation à donner un avis. Templates HTML dans `templates/emails/` |
| `MenuService` | CRUD menus avec gestion des relations (thèmes, régimes, plats, images, matériel associé), contrôle de stock |
| `PlatService` | CRUD plats avec gestion des allergènes (table de jonction `PLAT_ALLERGENE`) |
| `StorageService` | Upload conditionnel — stockage local (`public/assets/`) en dev, Azure Blob Storage en production |
| `UserService` | CRUD utilisateurs, mise à jour profil, création/désactivation comptes employés |

### 3.4 Middlewares — Chaîne de traitements

Les middlewares sont exécutés dans l'ordre avant d'atteindre le contrôleur. Certains sont globaux (toutes les requêtes API), d'autres spécifiques à certaines routes.

| Middleware | Portée | Fonctionnement |
|---|---|---|
| `CorsMiddleware` | **Global** | Headers `Access-Control-Allow-Origin`, `Allow-Methods`, `Allow-Headers`, `Allow-Credentials`. Gère les requêtes preflight `OPTIONS`. Whitelist configurable dans `config.php` |
| `SecurityHeadersMiddleware` | **Global** | Header `Content-Security-Policy` avec 10 directives (`default-src 'self'`, `script-src`, `style-src`, `img-src`, `font-src`, `connect-src`, `frame-src 'none'`, `object-src 'none'`, `base-uri 'self'`, `form-action 'self'`). Configurable via `$config['csp']` |
| `CsrfMiddleware` | **Routes mutantes** (POST/PUT/PATCH/DELETE) | Compare le cookie `csrfToken` avec le header `X-CSRF-Token` via `hash_equals()`. Lève `ForbiddenException` si invalide |
| `RateLimitMiddleware` | **Routes sensibles** | Limitation par IP, stockage fichier dans `backend/var/rate_limit/`. Configurable par route (ex: login 5 req/15min, register 5/1h, commande 10/1min, contact 5/1h). Lève `TooManyRequestsException` |
| `AuthMiddleware` | **Routes authentifiées** | Décode le JWT depuis le cookie `authToken` (priorité) ou le header `Authorization: Bearer` (fallback Postman). Attache `sub` (userId) et `role` à l'objet `Request`. Lève `AuthException` si token invalide/expiré |
| `RoleMiddleware` | **Routes restreintes** | Vérifie le rôle utilisateur vs rôles autorisés (ex: `ADMIN`, `EMPLOYE`). Paramétrable par route. Lève `ForbiddenException` si rôle insuffisant |

### 3.5 Repositories — Accès aux données

Chaque repository encapsule les requêtes SQL (prepared statements PDO) pour un domaine fonctionnel.

| Repository | Tables gérées | Opérations principales |
|---|---|---|
| `UserRepository` | `UTILISATEUR` | findByEmail, findById, create, update, disable |
| `MenuRepository` | `MENU`, `IMAGE_MENU`, `MENU_MATERIEL`, `PROPOSE` | findAll (avec filtres prix/thème/régime/personnes), findById (avec plats/images), create, update, delete, updateStock |
| `PlatRepository` | `PLAT`, `PLAT_ALLERGENE` | findAll, findByType, create, update, delete |
| `CommandeRepository` | `COMMANDE`, `COMMANDE_STATUT`, `COMMANDE_ANNULATION`, `COMMANDE_MODIFICATION`, `COMMANDE_MATERIEL` | create, findByUser, findAll (filtres statut/client), updateStatus, cancel, modify |
| `AvisRepository` | `AVIS_FALLBACK` + MongoDB `avis` | create (dual write), findPublic, findAll, validate, delete |
| `AllergeneRepository` | `ALLERGENE` | findAll |
| `ThemeRepository` | `THEME` | findAll |
| `RegimeRepository` | `REGIME` | findAll |
| `HoraireRepository` | `HORAIRE` | findAll, update |
| `ContactRepository` | `CONTACT` | create |
| `MaterielRepository` | `MATERIEL` | findAll, findById, create, update, delete |
| `ResetTokenRepository` | `RESET_TOKEN` | create, findByToken, markUsed |

### 3.6 Validators — Contrôle des entrées

Chaque validator vérifie les données de la requête et retourne un tableau d'erreurs descriptives en français.

| Validator | Règles principales |
|---|---|
| `UserValidator` | Nom/prénom (2-50 chars), email (format valide, unicité), GSM (format FR), adresse complète (adresse, ville, code postal), mot de passe (≥10 chars, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial) |
| `LoginValidator` | Email requis (format valide), mot de passe requis |
| `MenuValidator` | Titre (3-100 chars), description requise, prix (> 0), nombre_personne_min (> 0), stock (≥ 0), thème/régime (existence en BDD via FK) |
| `PlatValidator` | Libellé (2-100 chars), type (ENUM : ENTREE, PLAT, DESSERT), allergènes (existence en BDD via FK) |
| `CommandeValidator` | Menu (existence + stock > 0), date prestation (future), heure livraison, adresse complète, nombre personnes (≥ minimum du menu) |
| `ContactValidator` | Titre (5-100 chars), description (10-2000 chars), email (format valide) |
| `HoraireValidator` | Jour (ENUM 7 jours), heures (format HH:MM), cohérence ouverture < fermeture |
| `MaterielValidator` | Libellé requis, valeur unitaire (> 0), stock (≥ 0) |
| `EmployeeValidator` | Email (format, unicité), mot de passe (mêmes critères de sécurité que `UserValidator`) |
| `ResetPasswordValidator` | Token requis, nouveau mot de passe (mêmes critères de sécurité) |

### 3.7 Exceptions personnalisées

| Exception | Code HTTP | Cas d'utilisation |
|---|---|---|
| `AuthException` | 401 | Token JWT manquant, invalide ou expiré ; erreur configuration JWT |
| `InvalidCredentialsException` | 401 | Email ou mot de passe incorrect lors de la connexion |
| `ForbiddenException` | 403 | Rôle insuffisant ; token CSRF invalide |
| `TooManyRequestsException` | 429 | Rate limit dépassé sur un endpoint sensible |
| `CommandeException` | 400 / 409 | Stock insuffisant, transition de statut invalide, modification interdite |
| `UserServiceException` | 400 / 409 | Email déjà existant, compte désactivé |

### 3.8 Modèles du domaine

| Modèle | Propriétés principales | Usage |
|---|---|---|
| `User` | id, nom, prenom, email, gsm, adresse, ville, code_postal, role (ADMINISTRATEUR / EMPLOYE / UTILISATEUR), actif, date_creation | Entité utilisateur avec système de rôles |
| `Menu` | id, titre, description, prix, nombre_personne_min, stock_disponible, conditions, thème, régime, plats[], images[], materiel[] | Offre catalogue du traiteur |
| `Commande` | id, utilisateur, menu, date_prestation, heure_livraison, adresse, prix_menu, prix_livraison, prix_total, statut, has_avis, materiel_pret, statuts[] | Commande client complète avec historique |
| `CommandeStatut` | id, commande_id, statut, date_changement, modifie_par, commentaire | Entrée d'historique de changement de statut |
| `Avis` | id, note (1-5), commentaire, statut_validation, utilisateur, commande, menu, modere_par | Avis client post-commande terminée |
| `Horaire` | id, jour, heure_ouverture, heure_fermeture, ferme | Horaire d'ouverture de l'entreprise (affiché dans le footer) |
| `Materiel` | id, libelle, description, valeur_unitaire, stock_disponible | Matériel prêtable aux clients lors des prestations |

---

## 4. Architecture frontend détaillée

### 4.1 Arborescence frontend

```
frontend/
├── pages/                      # 10 pages HTML + 2 composants
│   ├── home.html               # Page d'accueil (présentation, menus, avis)
│   ├── connexion.html          # Formulaire de connexion
│   ├── inscription.html        # Formulaire d'inscription
│   ├── profil.html             # Espace utilisateur (commandes, profil)
│   ├── commande.html           # Formulaire de commande
│   ├── menu-detail.html        # Vue détaillée d'un menu
│   ├── motdepasse-oublie.html  # Reset mot de passe
│   ├── contact.html            # Formulaire de contact
│   ├── mentions-legales.html   # Mentions légales + CGV
│   ├── admin/
│   │   └── dashboard.html      # Dashboard admin/employé (onglets)
│   └── components/
│       ├── navbar.html          # Barre de navigation (chargée dynamiquement)
│       └── footer.html          # Pied de page avec horaires (chargé dynamiquement)
│
├── js/                         # 41 fichiers JavaScript (8 dossiers)
│   ├── core/                   # Infrastructure de l'application
│   │   ├── components.js       # Chargement dynamique navbar/footer → émet 'componentsLoaded'
│   │   └── navbar.js           # Logique menu mobile (hamburger, fermeture au scroll)
│   │
│   ├── pages/                  # Scripts de page (1 fichier = 1 page)
│   │   ├── home-menus.js       # Affichage menus sur la page d'accueil
│   │   ├── connexion.js        # Logique formulaire connexion
│   │   ├── inscription.js      # Logique formulaire inscription
│   │   ├── profil.js           # Espace utilisateur (commandes, avis, profil)
│   │   ├── commande.js         # Logique commande (calcul prix, validation)
│   │   ├── menu-detail.js      # Affichage détail menu + filtres
│   │   ├── motdepasse-oublie.js # Reset mot de passe
│   │   ├── contact.js          # Formulaire de contact
│   │   └── mentions-legales.js # Navigation mentions légales
│   │
│   ├── widgets/                # Composants UI réutilisables
│   │   ├── avis-carousel.js    # Carrousel des avis clients (page d'accueil)
│   │   ├── menus-carousel.js   # Carrousel des menus (page d'accueil)
│   │   ├── demo-cube.js        # Animation Rubik's Cube 3D
│   │   ├── counter-animate.js  # Animation compteurs (chiffres clés)
│   │   ├── scroll-parallax.js  # Effet parallax au scroll
│   │   ├── scroll-progress.js  # Barre de progression de scroll
│   │   └── scroll-snap-controller.js # Contrôleur scroll snap
│   │
│   ├── services/               # Services API (wrappers fetch)
│   │   ├── authService.js      # Auth (login, register, logout, check, CSRF)
│   │   ├── menuService.js      # Menus (getAll, getById, themes, regimes, CRUD)
│   │   ├── commandeService.js  # Commandes (create, list, calculatePrice)
│   │   ├── avisService.js      # Avis (create, getPublic, validate)
│   │   ├── platService.js      # Plats (CRUD, allergènes)
│   │   └── adminService.js     # Admin (employés, stats, dashboard)
│   │
│   ├── utils/                  # Utilitaires partagés
│   │   ├── helpers.js          # escapeHtml(), formatPrice(), formatDate()
│   │   ├── logger.js           # Logging conditionnel (dev/prod)
│   │   ├── password-toggle.js  # Toggle visibilité mot de passe (icône œil)
│   │   ├── toast.js            # Notifications toast (succès, erreur, info)
│   │   ├── skeleton.js         # Skeleton screens (chargement)
│   │   └── scroll-reveal.js    # Animations d'apparition au scroll
│   │
│   ├── admin/                  # Dashboard découpé en modules (9 fichiers)
│   │   ├── dashboard.js        # Orchestrateur (init, gestion onglets, fonctions communes)
│   │   ├── dashboard-menus.js  # Onglet Menus
│   │   ├── dashboard-plats.js  # Onglet Plats
│   │   ├── dashboard-commandes.js # Onglet Commandes
│   │   ├── dashboard-avis.js   # Onglet Avis
│   │   ├── dashboard-employes.js # Onglet Employés (admin uniquement)
│   │   ├── dashboard-horaires.js # Onglet Horaires
│   │   ├── dashboard-materiel.js # Onglet Matériel
│   │   └── dashboard-stats.js  # Onglet Statistiques (admin uniquement)
│   │
│   ├── auth/
│   │   └── auth-navbar.js      # Mise à jour navbar selon état connecté/déconnecté
│   │
│   └── guards/
│       └── adminGuard.js       # Protection pages admin (vérification rôle)
│
├── styles/                     # Feuilles de style CSS (architecture @layer)
│   ├── _tokens.css             # Design tokens (variables CSS globales)
│   ├── base.css                # Reset, typographie, déclaration @layer order
│   ├── utilities.css           # Classes utilitaires (u-hidden, mt-lg, u-text-center…)
│   ├── components/             # 16 fichiers composants
│   │   ├── animations.css      ├── carousel-split-home.css
│   │   ├── avis-clients-home.css ├── footer.css
│   │   ├── button.css          ├── forms.css
│   │   ├── hero-home.css       ├── menus-home.css
│   │   ├── modals.css          ├── navbar.css
│   │   ├── password-strength.css ├── scroll-dots.css
│   │   ├── scroll-progress.css ├── scroll-reveal.css
│   │   ├── skeleton.css        └── toast.css
│   ├── layouts/
│   │   └── auth-layout.css     # Mise en page formulaires auth
│   ├── pages/                  # 9 fichiers spécifiques par page
│   │   ├── home.css            ├── connexion.css
│   │   ├── inscription.css     ├── profil.css
│   │   ├── commande.css        ├── menu-detail.css
│   │   ├── motdepasse-oublie.css ├── contact.css
│   │   └── mentions-legales.css
│   └── admin/
│       └── dashboard.css       # Styles du dashboard admin
│
├── tests/                      # Tests Vitest (20 fichiers + 4 helpers)
├── package.json                # Dépendances frontend (Vitest)
└── vitest.config.js            # Configuration Vitest
```

### 4.2 Pattern de chargement des scripts

Les pages HTML chargent les scripts en bas de `<body>` dans un ordre strict :

```html
<!-- 1. Infrastructure : chargement dynamique navbar/footer -->
<script src="/frontend/js/core/components.js"></script>

<!-- 2. Utilitaires partagés -->
<script src="/frontend/js/utils/helpers.js"></script>
<script src="/frontend/js/utils/toast.js"></script>

<!-- 3. Services API nécessaires à la page -->
<script src="/frontend/js/services/authService.js"></script>
<script src="/frontend/js/services/menuService.js"></script>

<!-- 4. Auth navbar (met à jour la navbar selon l'état connecté) -->
<script src="/frontend/js/auth/auth-navbar.js"></script>

<!-- 5. Script spécifique à la page (1 fichier = 1 page) -->
<script src="/frontend/js/pages/home-menus.js"></script>

<!-- 6. Widgets UI si nécessaires -->
<script src="/frontend/js/widgets/avis-carousel.js"></script>

<!-- 7. Navbar mobile (en dernier, après que le DOM est prêt) -->
<script src="/frontend/js/core/navbar.js"></script>
```

### 4.3 Événements personnalisés

| Événement | Émetteur | Écouteurs | Description |
|---|---|---|---|
| `componentsLoaded` | `core/components.js` | `auth-navbar.js`, `core/navbar.js` | Émis après le chargement dynamique du header et du footer via `fetch()`. Les scripts qui dépendent de la navbar (menu mobile, état auth) écoutent cet événement avant de s'initialiser |
| `DOMContentLoaded` | Navigateur | Scripts de page (`pages/*.js`) | Les scripts de page écoutent cet événement natif pour s'initialiser |

### 4.4 Services frontend — Wrappers API

Les services sont des **objets littéraux globaux** qui encapsulent les appels `fetch()` vers l'API :

| Service | Variable globale | Endpoints couverts | Méthodes principales |
|---|---|---|---|
| `authService.js` | `AuthService` | `/api/auth/*`, `/api/csrf` | `login()`, `register()`, `logout()`, `check()`, `getCsrfToken()`, `addCsrfHeader()`, `getFetchOptions()`, `updateProfile()`, `forgotPassword()`, `resetPassword()` |
| `menuService.js` | `MenuService` | `/api/menus/*`, `/api/plats/*` | `getAll(filters)`, `getById(id)`, `getThemes()`, `getRegimes()`, `create()`, `update()`, `delete()` |
| `commandeService.js` | `CommandeService` | `/api/commandes/*`, `/api/my-orders` | `create()`, `getMyOrders()`, `calculatePrice()`, `modify()`, `cancel()` |
| `avisService.js` | `AvisService` | `/api/avis/*` | `create()`, `getPublic()`, `getAll()`, `validate()`, `delete()` |
| `platService.js` | `PlatService` | `/api/plats/*` | `getAll()`, `getByType()`, `getAllergenes()`, `create()`, `update()`, `delete()` |
| `adminService.js` | `AdminService` | `/api/admin/*`, `/api/commandes/*`, `/api/menues-commandes-stats` | `getEmployees()`, `createEmployee()`, `disableUser()`, `getAllCommandes()`, `updateStatus()`, `getStats()` |

**Convention critique :** Tous les `fetch()` incluent `credentials: 'include'` pour l'envoi automatique des cookies. Les requêtes mutantes (POST/PUT/PATCH/DELETE) ajoutent le header `X-CSRF-Token` via `AuthService.addCsrfHeader()`.

### 4.5 Architecture CSS — Système @layer

Le CSS utilise le standard `@layer` pour gérer la spécificité de manière prévisible. L'ordre des layers est déclaré dans `base.css` :

```css
@layer base, utilities, components, layouts, pages;
```

| Layer | Priorité | Contenu | Fichiers |
|---|---|---|---|
| `base` | 1 (plus faible) | Reset CSS, typographie, styles éléments HTML natifs | `base.css` |
| `utilities` | 2 | Classes utilitaires (`u-hidden`, `u-text-center`, `mt-lg`, `u-mr-sm`…) | `utilities.css` |
| `components` | 3 | Composants réutilisables (`.button`, `.navbar`, `.footer`, `.form-group`…) | `styles/components/*.css` (16 fichiers) |
| `layouts` | 4 | Mises en page (`.auth-section`, `.auth-container`…) | `styles/layouts/*.css` |
| `pages` | 5 (plus forte) | Styles spécifiques à une seule page | `styles/pages/*.css` (9 fichiers) |

**Avantage :** Un style de page override toujours un composant sans `!important`. Un composant override toujours une utilité. La spécificité devient prévisible et maintenable.

### 4.6 Design tokens — `_tokens.css`

Toutes les valeurs de design sont centralisées dans des variables CSS :

```css
:root {
  /* Couleurs principales */
  --color-primary: #FC7200;        /* Orange CTA */
  --color-primary-600: #E65A00;    /* Hover CTA */
  --color-secondary: #2C3E50;      /* Navbar, footer, textes foncés */
  --color-bg: #F5F5F5;             /* Fond de page */
  --color-white: #FFFFFF;
  --color-success: #28A745;
  --color-error: #DC3545;
  --color-warning: #FFC107;

  /* Typographie */
  --font-family: 'Inter', sans-serif;
  --font-size-base: 1rem;

  /* Espacements */
  --spacing-xs: 0.25rem;
  --spacing-sm: 0.5rem;
  --spacing-md: 1rem;
  --spacing-lg: 1.5rem;
  --spacing-xl: 2rem;

  /* Bordures */
  --radius-sm: 0.25rem;
  --radius-md: 0.5rem;
  --radius-lg: 1rem;

  /* Ombres, transitions… */
}
```

**Règle stricte :** Toute valeur de couleur, espacement, rayon, ombre, ou transition doit utiliser une variable de `_tokens.css`. Aucun « magic number » dans les fichiers CSS de composants ou de pages.

### 4.7 Convention de nommage CSS — BEM

Le nommage des classes suit la convention **BEM** (Block Element Modifier) :

```css
/* Block */
.navbar { }

/* Element */
.navbar__link { }
.navbar__logo { }

/* Modifier */
.navbar__link--admin { }
.navbar__link--active { }

/* Composants */
.button { }
.button--primary { }
.button--outline { }

/* États (préfixe is-) */
.is-visible { }
.is-fading { }
.is-disabled { }

/* Utilitaires (préfixe u-) */
.u-hidden { }
.u-text-center { }
```

---

## 5. Modèle de données

### 5.1 Vue d'ensemble — 20 tables MySQL

Le schéma complet est défini dans `backend/database/sql/database_creation.sql`. Il comprend **20 tables**, **3 vues**, et **2 triggers**.

```
┌─────────────────────────────────────────────────────────────────┐
│                         AUTHENTIFICATION                         │
│  ┌──────────────┐    ┌──────────────┐                           │
│  │ UTILISATEUR  │───>│ RESET_TOKEN  │                           │
│  └──────┬───────┘    └──────────────┘                           │
│         │                                                        │
├─────────┼────────────────────────────────────────────────────────┤
│         │              CATALOGUE                                 │
│         │  ┌────────┐    ┌───────┐    ┌──────────┐             │
│         │  │ THEME  │───>│ MENU  │<───│ REGIME   │             │
│         │  └────────┘    └───┬───┘    └──────────┘             │
│         │                    │                                   │
│         │           ┌────────┼────────┐                         │
│         │           ▼        ▼        ▼                         │
│         │  ┌────────────┐ ┌──────┐ ┌──────────────┐           │
│         │  │ IMAGE_MENU │ │PROPOSE│ │ MENU_MATERIEL│           │
│         │  └────────────┘ └──┬───┘ └──────┬───────┘           │
│         │                    ▼             ▼                    │
│         │               ┌───────┐   ┌──────────┐              │
│         │               │ PLAT  │   │ MATERIEL │              │
│         │               └───┬───┘   └──────────┘              │
│         │                   ▼                                   │
│         │         ┌───────────────┐   ┌───────────┐            │
│         │         │ PLAT_ALLERGENE│──>│ ALLERGENE │            │
│         │         └───────────────┘   └───────────┘            │
│         │                                                        │
├─────────┼────────────────────────────────────────────────────────┤
│         │              COMMANDES                                 │
│         ▼                                                        │
│  ┌──────────────┐                                               │
│  │  COMMANDE    │──────────────────────────┐                    │
│  └──────┬───────┘                          │                    │
│         │                                  │                    │
│    ┌────┼──────────┬─────────────┐        ▼                    │
│    ▼    ▼          ▼             ▼  ┌──────────────────┐       │
│ ┌────────┐ ┌────────────┐ ┌──────┐ │COMMANDE_MATERIEL │       │
│ │STATUT  │ │ANNULATION  │ │MODIF │ └──────────────────┘       │
│ └────────┘ └────────────┘ └──────┘                              │
│                                                                  │
├──────────────────────────────────────────────────────────────────┤
│                    AUTRES                                        │
│  ┌──────────┐  ┌──────────┐  ┌───────────────┐                │
│  │ HORAIRE  │  │ CONTACT  │  │ AVIS_FALLBACK │                │
│  └──────────┘  └──────────┘  └───────────────┘                │
└──────────────────────────────────────────────────────────────────┘
```

### 5.2 Détail des tables — Par domaine

#### Authentification

**UTILISATEUR** — Comptes utilisateurs, employés et administrateurs

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `nom` | VARCHAR(50) | NOT NULL | Nom de famille |
| `prenom` | VARCHAR(50) | NOT NULL | Prénom |
| `gsm` | VARCHAR(20) | NOT NULL | Numéro de téléphone mobile |
| `email` | VARCHAR(100) | NOT NULL, UNIQUE | Email (= username pour la connexion) |
| `adresse` | VARCHAR(255) | NOT NULL | Adresse postale |
| `ville` | VARCHAR(100) | NOT NULL | Ville |
| `code_postal` | VARCHAR(10) | NOT NULL | Code postal |
| `mot_de_passe` | VARCHAR(255) | NOT NULL | Hash Argon2ID du mot de passe |
| `role` | ENUM('UTILISATEUR', 'EMPLOYE', 'ADMINISTRATEUR') | NOT NULL, DEFAULT 'UTILISATEUR' | Rôle déterminant les permissions |
| `actif` | BOOLEAN | DEFAULT TRUE | Permet la désactivation sans suppression |
| `date_creation` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Date de création du compte |

**RESET_TOKEN** — Tokens de réinitialisation de mot de passe

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `token` | VARCHAR(255) | NOT NULL, UNIQUE | Token aléatoire envoyé par email |
| `id_utilisateur` | INT | FK → UTILISATEUR(id) | Utilisateur concerné |
| `expiration` | DATETIME | NOT NULL | Date/heure d'expiration du token |
| `utilise` | BOOLEAN | DEFAULT FALSE | Marque le token comme consommé |
| `date_creation` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Date de création |

#### Catalogue (Menus, Plats, Allergènes)

**THEME** — Thèmes des menus (Noël, Pâques, Classique, Événement)

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `libelle` | VARCHAR(50) | NOT NULL, UNIQUE |

**REGIME** — Régimes alimentaires (Classique, Végétarien, Végan, Sans Gluten…)

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `libelle` | VARCHAR(50) | NOT NULL, UNIQUE |

**MENU** — Menus proposés par le traiteur

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT | |
| `titre` | VARCHAR(100) | NOT NULL | Nom du menu |
| `description` | TEXT | | Présentation détaillée |
| `nombre_personne_min` | INT | NOT NULL | Minimum de personnes pour commander |
| `prix` | DECIMAL(10,2) | NOT NULL | Prix pour le nombre minimum de personnes |
| `stock_disponible` | INT | NOT NULL, DEFAULT 10 | Nombre de commandes restantes |
| `conditions` | TEXT | | Conditions spéciales (délai commande, stockage…) |
| `id_theme` | INT | FK → THEME(id) | Thème du menu |
| `id_regime` | INT | FK → REGIME(id) | Régime alimentaire |
| `actif` | BOOLEAN | DEFAULT TRUE | Menu visible ou masqué |
| `date_creation` | DATETIME | DEFAULT CURRENT_TIMESTAMP | |

**IMAGE_MENU** — Galerie d'images par menu

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `id_menu` | INT | FK → MENU(id) ON DELETE CASCADE |
| `url` | VARCHAR(500) | NOT NULL |
| `alt_text` | VARCHAR(200) | |
| `position` | INT | DEFAULT 0 (ordre d'affichage) |

**PLAT** — Plats (entrées, plats, desserts) — partagés entre menus

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `libelle` | VARCHAR(100) | NOT NULL |
| `type` | ENUM('ENTREE', 'PLAT', 'DESSERT') | NOT NULL |
| `description` | TEXT | |

**PROPOSE** — Table de jonction Menu ↔ Plat

| Colonne | Type | Contraintes |
|---|---|---|
| `id_menu` | INT | PK (composite), FK → MENU(id) ON DELETE CASCADE |
| `id_plat` | INT | PK (composite), FK → PLAT(id) ON DELETE CASCADE |
| `position` | INT | DEFAULT 0 (ordre d'affichage dans le menu) |

**ALLERGENE** — Référentiel des allergènes

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `libelle` | VARCHAR(100) | NOT NULL, UNIQUE |

**PLAT_ALLERGENE** — Table de jonction Plat ↔ Allergène

| Colonne | Type | Contraintes |
|---|---|---|
| `id_plat` | INT | PK (composite), FK → PLAT(id) ON DELETE CASCADE |
| `id_allergene` | INT | PK (composite), FK → ALLERGENE(id) ON DELETE CASCADE |

**MENU_MATERIEL** — Matériel associé à un menu (ex: vaisselle, équipement)

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `id_menu` | INT | FK → MENU(id) ON DELETE CASCADE |
| `id_materiel` | INT | FK → MATERIEL(id) ON DELETE CASCADE |
| `quantite_par_personne` | INT | DEFAULT 1 |

#### Matériel

**MATERIEL** — Matériel disponible pour prêt aux clients

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `libelle` | VARCHAR(100) | NOT NULL |
| `description` | TEXT | |
| `valeur_unitaire` | DECIMAL(10,2) | NOT NULL |
| `stock_disponible` | INT | NOT NULL, DEFAULT 0 |

#### Commandes

**COMMANDE** — Commandes clients

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT | |
| `id_utilisateur` | INT | FK → UTILISATEUR(id) | Client ayant passé la commande |
| `id_menu` | INT | FK → MENU(id) | Menu commandé |
| `nombre_personnes` | INT | NOT NULL | Nombre de personnes (≥ min du menu) |
| `date_prestation` | DATE | NOT NULL | Date de la prestation |
| `heure_livraison` | TIME | NOT NULL | Heure souhaitée de livraison |
| `adresse_livraison` | VARCHAR(255) | NOT NULL | Adresse de livraison |
| `ville_livraison` | VARCHAR(100) | NOT NULL | |
| `code_postal_livraison` | VARCHAR(10) | NOT NULL | |
| `prix_menu` | DECIMAL(10,2) | NOT NULL | Prix du menu (snapshot à la commande) |
| `prix_livraison` | DECIMAL(10,2) | DEFAULT 0.00 | Frais de livraison (0 si Bordeaux) |
| `prix_total` | DECIMAL(10,2) | NOT NULL | Prix menu + livraison |
| `statut` | ENUM(8 valeurs) | DEFAULT 'en_attente' | Voir cycle de vie §8.1 |
| `has_avis` | BOOLEAN | DEFAULT FALSE | L'utilisateur a laissé un avis |
| `materiel_pret` | BOOLEAN | DEFAULT FALSE | Du matériel a été prêté |
| `date_creation` | DATETIME | DEFAULT CURRENT_TIMESTAMP | |
| `date_modification` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | |

Statuts ENUM : `en_attente`, `acceptee`, `en_preparation`, `en_livraison`, `livree`, `en_attente_retour_materiel`, `terminee`, `annulee`

**COMMANDE_STATUT** — Historique des changements de statut (traçabilité)

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `id_commande` | INT | FK → COMMANDE(id) ON DELETE CASCADE |
| `statut` | ENUM(8 valeurs) | NOT NULL |
| `date_changement` | DATETIME | DEFAULT CURRENT_TIMESTAMP |
| `modifie_par` | INT | FK → UTILISATEUR(id) |
| `commentaire` | TEXT | |

**COMMANDE_ANNULATION** — Détails des annulations (motif + mode contact obligatoires)

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `id_commande` | INT | FK → COMMANDE(id) ON DELETE CASCADE |
| `annule_par` | INT | FK → UTILISATEUR(id) |
| `mode_contact` | ENUM('GSM', 'MAIL') | NOT NULL |
| `motif` | TEXT | NOT NULL |
| `date_annulation` | DATETIME | DEFAULT CURRENT_TIMESTAMP |

**COMMANDE_MODIFICATION** — Historique des modifications de commande

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `id_commande` | INT | FK → COMMANDE(id) ON DELETE CASCADE |
| `modifie_par` | INT | FK → UTILISATEUR(id) |
| `champs_modifies` | JSON | NOT NULL (liste des champs modifiés) |
| `date_modification` | DATETIME | DEFAULT CURRENT_TIMESTAMP |

**COMMANDE_MATERIEL** — Matériel prêté pour une commande

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `id_commande` | INT | FK → COMMANDE(id) ON DELETE CASCADE |
| `id_materiel` | INT | FK → MATERIEL(id) |
| `quantite` | INT | NOT NULL |
| `date_pret` | DATE | |
| `date_retour_prevu` | DATE | |
| `date_retour_effectif` | DATE | |

#### Contact et Horaires

**CONTACT** — Messages envoyés via le formulaire de contact

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `titre` | VARCHAR(100) | NOT NULL |
| `description` | TEXT | NOT NULL |
| `email` | VARCHAR(100) | NOT NULL |
| `date_envoi` | DATETIME | DEFAULT CURRENT_TIMESTAMP |
| `traite` | BOOLEAN | DEFAULT FALSE |

**HORAIRE** — Horaires d'ouverture (affichés dans le footer)

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `jour` | ENUM('LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI','SAMEDI','DIMANCHE') | NOT NULL, UNIQUE |
| `heure_ouverture` | TIME | |
| `heure_fermeture` | TIME | |
| `ferme` | BOOLEAN | DEFAULT FALSE |

#### Avis

**AVIS_FALLBACK** — Fallback MySQL pour les avis (stockage primaire dans MongoDB)

| Colonne | Type | Contraintes |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `note` | INT | NOT NULL (1-5) CHECK(note BETWEEN 1 AND 5) |
| `commentaire` | TEXT | |
| `statut_validation` | ENUM('en_attente', 'valide', 'refuse') | DEFAULT 'en_attente' |
| `id_utilisateur` | INT | FK → UTILISATEUR(id) |
| `id_commande` | INT | FK → COMMANDE(id) |
| `id_menu` | INT | FK → MENU(id) |
| `modere_par` | INT | FK → UTILISATEUR(id), NULL |
| `mongo_id` | VARCHAR(50) | Référence vers le document MongoDB |
| `date_creation` | DATETIME | DEFAULT CURRENT_TIMESTAMP |

### 5.3 Vues SQL

| Vue | Description | Tables sources |
|---|---|---|
| `v_menus_actifs` | Menus actifs avec nom du thème et du régime | MENU, THEME, REGIME |
| `v_commandes_en_cours` | Commandes non terminées/annulées avec infos client et menu | COMMANDE, UTILISATEUR, MENU |
| `v_avis_valides` | Avis validés avec infos client et menu | AVIS_FALLBACK, UTILISATEUR, MENU |

### 5.4 Triggers

| Trigger | Événement | Action |
|---|---|---|
| `after_commande_insert` | Après INSERT sur COMMANDE | Insère automatiquement une entrée dans `COMMANDE_STATUT` avec le statut initial `en_attente` |
| `after_commande_update_statut` | Après UPDATE du champ `statut` sur COMMANDE | Insère automatiquement une entrée dans `COMMANDE_STATUT` avec le nouveau statut |

### 5.5 Collections MongoDB

| Collection | Document type | Description |
|---|---|---|
| `avis` | `{ note, commentaire, utilisateur: { id, nom, prenom }, commande_id, menu: { id, titre }, statut_validation, modere_par, date_creation }` | Stockage primaire des avis clients. Les requêtes de lecture passent en priorité par MongoDB (agrégation, tri). En cas d'échec MongoDB, le système bascule sur `AVIS_FALLBACK` en MySQL |
| `statistiques_commandes` | `{ menu_id, menu_titre, total_commandes, chiffre_affaires, periode }` | Statistiques agrégées pour le dashboard admin. Alimentées par les agrégations MongoDB sur les commandes |

### 5.6 Règles de gestion principales

| # | Règle |
|---|---|
| RG01 | Un utilisateur est identifié par son email (unique) |
| RG02 | Le mot de passe doit contenir ≥ 10 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial |
| RG03 | Les rôles sont : UTILISATEUR, EMPLOYE, ADMINISTRATEUR |
| RG04 | Seul un ADMINISTRATEUR peut créer un compte EMPLOYE |
| RG05 | Il n'est pas possible de créer un compte ADMINISTRATEUR depuis l'application |
| RG06 | Un compte peut être désactivé (actif=false) mais jamais supprimé |
| RG07 | Un menu appartient à un thème et un régime |
| RG08 | Un menu contient une liste de plats (entrées, plats, desserts) |
| RG09 | Un plat peut appartenir à plusieurs menus |
| RG10 | Un plat possède zéro ou plusieurs allergènes |
| RG11 | Un menu a un stock disponible (nombre de commandes restantes) |
| RG12 | Le nombre de personnes commandé doit être ≥ au minimum du menu |
| RG13 | Réduction de 10% si nombre de personnes ≥ minimum + 5 |
| RG14 | Livraison gratuite si adresse à Bordeaux |
| RG15 | Livraison hors Bordeaux : 5€ de base + 0,59€ par kilomètre |
| RG16 | La distance est calculée via Google Maps API (avec fallback estimation) |
| RG17 | Le prix est snapshoté à la commande (indépendant des modifications ultérieures du menu) |
| RG18 | Un employé ne peut annuler une commande qu'après avoir contacté le client (motif + mode contact obligatoires) |
| RG19 | L'utilisateur peut modifier/annuler sa commande tant qu'elle n'est pas `acceptee` |
| RG20 | Le cycle de vie d'une commande suit 8 statuts ordonnés (voir §8.1) |
| RG21 | Du matériel peut être prêté au client lors d'une commande |
| RG22 | Si matériel prêté, le client a 10 jours ouvrés pour le restituer |
| RG23 | Passé ce délai, des frais de 600€ sont applicables (mentionnés dans les CGV) |
| RG24 | Un avis ne peut être donné que pour une commande terminée |
| RG25 | Un avis doit être validé par un employé/admin avant d'être visible publiquement |
| RG26 | La note d'un avis est comprise entre 1 et 5 |
| RG27 | Les horaires sont affichés du lundi au dimanche dans le footer |
| RG28 | Un email de bienvenue est envoyé automatiquement à l'inscription |
| RG29 | Un email de confirmation est envoyé à chaque nouvelle commande |
| RG30 | Un email est envoyé quand la commande passe en statut `terminee` (invitation à donner un avis) |
| RG31 | Un email est envoyé quand la commande passe en `en_attente_retour_materiel` (notification délai 10 jours) |
| RG32 | Le reset de mot de passe passe par un token envoyé par email, avec expiration |

> Pour la liste exhaustive des 38+ règles de gestion avec le détail MCD, consulter `docs/diagrammes/diagrammes_MCD_MLD/diagramme_mcd/diagramme_mcd.md`.

---

## 6. API REST — Référence complète

### 6.1 Conventions générales

- **Base URL :** `/api`
- **Format :** JSON (entrée et sortie)
- **Authentification :** Cookie `authToken` (JWT HttpOnly) envoyé automatiquement via `credentials: 'include'`
- **CSRF :** Header `X-CSRF-Token` requis sur toutes les requêtes mutantes (POST, PUT, PATCH, DELETE)
- **Codes de réponse :** 200 (OK), 201 (Created), 204 (No Content), 400 (Bad Request), 401 (Unauthorized), 403 (Forbidden), 404 (Not Found), 409 (Conflict), 429 (Too Many Requests), 500 (Server Error)

### 6.2 Authentification (10 endpoints)

| Méthode | Endpoint | Auth | CSRF | Rate Limit | Description |
|---|---|---|---|---|---|
| POST | `/api/auth/register` | — | ✅ | 5/1h | Inscription d'un nouvel utilisateur |
| POST | `/api/auth/login` | — | ✅ | 5/15min | Connexion (retourne cookie authToken) |
| POST | `/api/auth/logout` | — | ✅ | — | Déconnexion (supprime cookies) |
| GET | `/api/auth/logout` | — | — | — | Retourne 404 (protection GET accidentel) |
| POST | `/api/auth/forgot-password` | — | ✅ | 3/15min | Demande de reset (envoie email avec token) |
| POST | `/api/auth/reset-password` | — | ✅ | 5/15min | Reset du mot de passe (avec token) |
| PUT | `/api/auth/profile` | ✅ | ✅ | — | Mise à jour du profil utilisateur |
| GET | `/api/auth/check` | ✅ | — | — | Vérifie la session en cours (retourne user) |
| GET | `/api/auth/test` | — | — | — | Route de test (dev uniquement) |
| GET | `/api/csrf` | — | — | — | Pose le cookie csrfToken |

### 6.3 Menus et Plats (14 endpoints)

| Méthode | Endpoint | Auth | CSRF | Rôle | Description |
|---|---|---|---|---|---|
| GET | `/api/menus` | — | — | — | Liste tous les menus (filtres : prix, thème, régime, personnes) |
| GET | `/api/menus/{id}` | — | — | — | Détail d'un menu (avec plats, images, allergènes) |
| GET | `/api/menus/themes` | — | — | — | Liste des thèmes disponibles |
| GET | `/api/menus/regimes` | — | — | — | Liste des régimes disponibles |
| POST | `/api/menus` | ✅ | ✅ | EMPLOYE, ADMIN | Créer un menu |
| PUT | `/api/menus/{id}` | ✅ | ✅ | EMPLOYE, ADMIN | Modifier un menu |
| DELETE | `/api/menus/{id}` | ✅ | ✅ | EMPLOYE, ADMIN | Supprimer un menu |
| GET | `/api/plats` | — | — | — | Liste tous les plats |
| GET | `/api/plats/{id}` | — | — | — | Détail d'un plat |
| GET | `/api/plats/allergenes` | — | — | — | Liste des allergènes |
| GET | `/api/plats/by-type` | ✅ | — | EMPLOYE, ADMIN | Plats groupés par type |
| POST | `/api/plats` | ✅ | ✅ | EMPLOYE, ADMIN | Créer un plat |
| PUT | `/api/plats/{id}` | ✅ | ✅ | EMPLOYE, ADMIN | Modifier un plat |
| DELETE | `/api/plats/{id}` | ✅ | ✅ | EMPLOYE, ADMIN | Supprimer un plat |

### 6.4 Commandes (11 endpoints)

| Méthode | Endpoint | Auth | CSRF | Rôle | Rate Limit | Description |
|---|---|---|---|---|---|---|
| POST | `/api/commandes` | ✅ | ✅ | — | 10/1min | Créer une commande |
| POST | `/api/commandes/calculate-price` | ✅ | ✅ | — | — | Calculer le prix (réduction + livraison) |
| PATCH | `/api/commandes/{id}` | ✅ | ✅ | — | — | Modifier une commande (avant acceptation) |
| PUT | `/api/commandes/{id}/status` | ✅ | ✅ | EMPLOYE, ADMIN | — | Changer le statut d'une commande |
| GET | `/api/my-orders` | ✅ | — | — | — | Mes commandes (utilisateur connecté) |
| GET | `/api/commandes` | ✅ | — | EMPLOYE, ADMIN | — | Toutes les commandes (filtres statut/client) |
| GET | `/api/commandes/{id}` | ✅ | — | — | — | Détail d'une commande (avec historique statuts) |
| GET | `/api/commandes/overdue-materials` | ✅ | — | EMPLOYE, ADMIN | — | Commandes avec matériel en retard |
| POST | `/api/commandes/{id}/material` | ✅ | ✅ | EMPLOYE, ADMIN | — | Enregistrer le prêt de matériel |
| POST | `/api/commandes/{id}/return-material` | ✅ | ✅ | EMPLOYE, ADMIN | — | Enregistrer le retour de matériel |
| GET | `/api/menues-commandes-stats` | ✅ | — | ADMIN | — | Statistiques commandes par menu |

### 6.5 Avis (5 endpoints)

| Méthode | Endpoint | Auth | CSRF | Rôle | Rate Limit | Description |
|---|---|---|---|---|---|---|
| POST | `/api/avis` | ✅ | ✅ | — | 5/1h | Créer un avis (commande terminée) |
| GET | `/api/avis` | ✅* | — | — | — | Tous les avis (*auth optionnelle, filtrage si admin) |
| GET | `/api/avis/public` | — | — | — | — | Avis validés (page d'accueil) |
| PUT | `/api/avis/{id}/validate` | ✅ | ✅ | EMPLOYE, ADMIN | — | Valider ou refuser un avis |
| DELETE | `/api/avis/{id}` | ✅ | ✅ | EMPLOYE, ADMIN | — | Supprimer un avis |

### 6.6 Administration (3 endpoints)

| Méthode | Endpoint | Auth | CSRF | Rôle | Description |
|---|---|---|---|---|---|
| POST | `/api/admin/employees` | ✅ | ✅ | ADMIN | Créer un compte employé |
| GET | `/api/admin/employees` | ✅ | — | ADMIN | Lister les employés |
| PATCH | `/api/admin/users/{id}/disable` | ✅ | ✅ | ADMIN | Désactiver un compte |

### 6.7 Contact (1 endpoint)

| Méthode | Endpoint | Auth | CSRF | Rate Limit | Description |
|---|---|---|---|---|---|
| POST | `/api/contact` | — | ✅ | 5/1h | Envoyer un message de contact (email entreprise) |

### 6.8 Horaires (2 endpoints)

| Méthode | Endpoint | Auth | CSRF | Rôle | Description |
|---|---|---|---|---|---|
| GET | `/api/horaires` | — | — | — | Liste des horaires (lundi-dimanche) |
| PUT | `/api/horaires/{id}` | ✅ | ✅ | EMPLOYE, ADMIN | Modifier un horaire |

### 6.9 Matériel (5 endpoints)

| Méthode | Endpoint | Auth | CSRF | Rôle | Description |
|---|---|---|---|---|---|
| GET | `/api/materiels` | ✅ | — | EMPLOYE, ADMIN | Lister tout le matériel |
| GET | `/api/materiels/{id}` | ✅ | — | EMPLOYE, ADMIN | Détail d'un matériel |
| POST | `/api/materiels` | ✅ | ✅ | EMPLOYE, ADMIN | Créer un matériel |
| PUT | `/api/materiels/{id}` | ✅ | ✅ | EMPLOYE, ADMIN | Modifier un matériel |
| DELETE | `/api/materiels/{id}` | ✅ | ✅ | EMPLOYE, ADMIN | Supprimer un matériel |

### 6.10 Diagnostic et Upload (2 endpoints)

| Méthode | Endpoint | Auth | CSRF | Rôle | Description |
|---|---|---|---|---|---|
| GET | `/api/diagnostic/mongodb` | ✅ | — | ADMIN | Vérifie la connexion MongoDB |
| POST | `/api/upload` | ✅ | ✅ | EMPLOYE, ADMIN | Upload d'image (local ou Azure Blob) |

**Total : 46 endpoints API.**

---

## 7. Sécurité

### 7.1 Authentification — JWT HS256 en cookie HttpOnly

Le système d'authentification combine la nature **stateless** du JWT et la **sécurité du cookie HttpOnly** :

**Côté serveur (backend) :**

1. **Connexion** (`POST /api/auth/login`) :
   - `AuthService` valide les credentials (email + mot de passe hashé Argon2ID)
   - Génère un JWT HS256 via `firebase/php-jwt` avec payload : `iss` (vite-gourmand), `sub` (userId), `role`, `iat`, `exp` (iat + 3600s)
   - Pose le cookie `authToken` via `setcookie()` avec les flags :
     - `httponly: true` — JavaScript ne peut pas lire le cookie
     - `secure: true` — envoyé uniquement en HTTPS (en production)
     - `samesite: None` (HTTPS) ou `Lax` (HTTP local)
     - `path: /` — valable sur toutes les routes
     - `expires: time() + 3600` — durée de vie 1 heure
   - `CsrfService` pose le cookie `csrfToken` (rotation à chaque login)
   - Le JWT n'est **jamais** renvoyé dans le corps JSON de la réponse

2. **Requêtes authentifiées** :
   - `AuthMiddleware` extrait le JWT du cookie `authToken` (priorité) ou du header `Authorization: Bearer` (fallback pour Postman/API)
   - Décode et valide le token via `JWT::decode()`
   - Attache les données utilisateur (`sub`, `role`) à l'objet `Request`

3. **Déconnexion** (`POST /api/auth/logout`) :
   - Supprime le cookie `authToken` (expire dans le passé)
   - Supprime le cookie `csrfToken`

**Côté client (frontend) :**

- Le frontend n'a **aucun accès** au JWT (cookie HttpOnly)
- Toutes les requêtes `fetch()` incluent `credentials: 'include'` — le navigateur attache automatiquement le cookie
- **Aucun usage** de `localStorage`, `sessionStorage` ou header `Authorization: Bearer`

### 7.2 Protection CSRF — Double Submit Cookie

Pattern implémenté pour protéger contre les attaques Cross-Site Request Forgery :

```
┌─────────────────┐                    ┌──────────────────┐
│    NAVIGATEUR    │                    │     SERVEUR      │
│                  │                    │                  │
│  1. Login        │ ────────────────── │  Pose cookies :  │
│                  │ Set-Cookie:        │  - authToken     │
│                  │   authToken=jwt    │    (HttpOnly)    │
│                  │   csrfToken=abc    │  - csrfToken     │
│                  │                    │    (non-HttpOnly) │
│                  │                    │                  │
│  2. JS lit le    │                    │                  │
│  cookie csrf     │                    │                  │
│  (non-HttpOnly)  │                    │                  │
│                  │                    │                  │
│  3. POST avec :  │ ────────────────── │  CsrfMiddleware: │
│  Cookie: csrf=abc│                    │  Compare cookie  │
│  Header:         │                    │  et header avec  │
│  X-CSRF-Token:abc│                    │  hash_equals()   │
│                  │                    │  → OK ou 403     │
└─────────────────┘                    └──────────────────┘
```

**Paramètres du cookie `csrfToken` :**
- `httponly: false` — le JS doit pouvoir le lire via `document.cookie`
- `secure: true` (production)
- `samesite: None` (HTTPS) / `Lax` (HTTP)
- `ttl: 7200s` (2 heures)
- Régénéré à chaque login (`CsrfService::rotateToken()`)

**Côté frontend :** `AuthService.getCsrfToken()` lit la valeur du cookie, `AuthService.addCsrfHeader()` l'ajoute en header `X-CSRF-Token` sur chaque requête mutante.

### 7.3 CORS — Cross-Origin Resource Sharing

`CorsMiddleware` est exécuté sur **toutes** les requêtes API. Il :
- Vérifie l'origine de la requête contre une whitelist configurable dans `config.php`
- Définit les headers `Access-Control-Allow-Origin`, `Allow-Methods` (GET, POST, PUT, PATCH, DELETE, OPTIONS), `Allow-Headers` (Content-Type, X-CSRF-Token, Authorization), `Allow-Credentials: true`
- Gère les requêtes preflight `OPTIONS` (réponse 204 immédiate)

### 7.4 Content Security Policy (CSP)

`SecurityHeadersMiddleware` génère dynamiquement le header `Content-Security-Policy` :

| Directive | Valeur par défaut |
|---|---|
| `default-src` | `'self'` |
| `script-src` | `'self'` `https://cdn.jsdelivr.net` |
| `style-src` | `'self'` `https://cdnjs.cloudflare.com` |
| `img-src` | `'self'` `data:` |
| `font-src` | `'self'` `https://cdnjs.cloudflare.com` |
| `connect-src` | `'self'` |
| `frame-src` | `'none'` |
| `object-src` | `'none'` |
| `base-uri` | `'self'` |
| `form-action` | `'self'` |

En production, `Strict-Transport-Security` (HSTS) est défini directement dans `public/index.php`.

### 7.5 Rate Limiting

`RateLimitMiddleware` limite le nombre de requêtes par IP sur les endpoints sensibles :

| Endpoint | Limite | Fenêtre |
|---|---|---|
| `POST /api/auth/login` | 5 requêtes | 15 minutes |
| `POST /api/auth/register` | 5 requêtes | 1 heure |
| `POST /api/auth/forgot-password` | 3 requêtes | 15 minutes |
| `POST /api/auth/reset-password` | 5 requêtes | 15 minutes |
| `POST /api/commandes` | 10 requêtes | 1 minute |
| `POST /api/avis` | 5 requêtes | 1 heure |
| `POST /api/contact` | 5 requêtes | 1 heure |

**Mécanisme :** Stockage fichier dans `backend/var/rate_limit/` (un fichier par IP hashée). Pas de dépendance externe (Redis). Nettoyage automatique des fichiers expirés.

### 7.6 Hash des mots de passe — Argon2ID

Algorithme utilisé : `PASSWORD_ARGON2ID` (recommandé OWASP 2024).

```php
// Hash à l'inscription
$hash = password_hash($password, PASSWORD_ARGON2ID);

// Vérification à la connexion
$valid = password_verify($inputPassword, $storedHash);
```

Argon2ID combine résistance aux attaques par GPU (Argon2d) et par canal auxiliaire (Argon2i). Il est supérieur à bcrypt pour les applications modernes.

### 7.7 Validation des entrées

- **Backend** : 10 validators spécialisés vérifient chaque entrée avant traitement (voir §3.6)
- **Requêtes SQL** : Prepared statements PDO systématiques (`$stmt->execute($params)`) — protection injection SQL
- **Frontend** : `escapeHtml()` centralisé dans `utils/helpers.js` (méthode `document.createTextNode()`) — protection XSS sur toute donnée dynamique injectée dans le DOM

### 7.8 RGPD et données personnelles

| Mesure | Implémentation |
|---|---|
| **Minimisation des données** | Seules les données nécessaires sont collectées (nom, prénom, email, GSM, adresse) |
| **Pas de stockage client** | Aucune donnée personnelle dans `localStorage` / `sessionStorage` |
| **Cookies** | `authToken` : HttpOnly (inaccessible au JS). `csrfToken` : contient un token aléatoire, pas de données personnelles |
| **Droit de modification** | L'utilisateur peut modifier ses informations personnelles depuis son espace profil (`PUT /api/auth/profile`) |
| **Désactivation** | Les comptes sont désactivés (`actif = false`), pas supprimés — conformité avec l'obligation de conserver certaines données (commandes, facturation) |
| **Mentions légales** | Page `mentions-legales.html` avec mentions légales complètes et CGV |
| **HTTPS** | Toutes les communications sont chiffrées en production (HSTS activé) |

---

## 8. Flux métier

### 8.1 Cycle de vie d'une commande

La commande passe par **8 statuts** définis en ENUM dans la table `COMMANDE`. Chaque transition est tracée dans `COMMANDE_STATUT` (via trigger automatique).

```
                    ┌─────────────┐
                    │ en_attente  │ ← Création par le client
                    └──────┬──────┘
                           │
                    ┌──────▼──────┐
            ┌───── │  acceptee   │ ← Validée par employé/admin
            │      └──────┬──────┘
            │             │
            │      ┌──────▼────────────┐
            │      │  en_preparation   │ ← Cuisine en cours
            │      └──────┬────────────┘
            │             │
            │      ┌──────▼──────────┐
            │      │  en_livraison   │ ← Équipe logistique
            │      └──────┬──────────┘
            │             │
            │      ┌──────▼──────┐
            │      │   livree    │ ← Client a reçu
            │      └──────┬──────┘
            │             │
            │      ┌──────┴──────────────────────┐
            │      │                              │
            │      ▼                              ▼
            │ ┌──────────┐          ┌──────────────────────────┐
            │ │ terminee │          │ en_attente_retour_materiel│
            │ └──────────┘          └─────────────┬────────────┘
            │  (pas de matériel)                  │
            │                              ┌──────▼──────┐
            │                              │  terminee   │
            │                              └─────────────┘
            │                               (matériel rendu)
            │
            │      ┌───────────┐
            └─────>│  annulee  │ ← Possible à tout moment
                   └───────────┘     (avant acceptation par le client,
                                      après par employé avec motif)
```

**Transitions autorisées :**

| Depuis | Vers | Qui | Conditions |
|---|---|---|---|
| `en_attente` | `acceptee` | Employé/Admin | Validation de la commande |
| `en_attente` | `annulee` | Client | Libre (avant acceptation) |
| `acceptee` | `en_preparation` | Employé/Admin | |
| `acceptee` | `annulee` | Employé/Admin | Motif + mode contact (GSM/MAIL) obligatoires |
| `en_preparation` | `en_livraison` | Employé/Admin | |
| `en_livraison` | `livree` | Employé/Admin | |
| `livree` | `terminee` | Employé/Admin | Si aucun matériel prêté |
| `livree` | `en_attente_retour_materiel` | Employé/Admin | Si matériel prêté au client |
| `en_attente_retour_materiel` | `terminee` | Employé/Admin | Matériel restitué |

**Actions automatiques sur changement de statut :**

| Statut atteint | Action |
|---|---|
| `en_attente` (création) | Email de confirmation au client (`MailerService`) |
| `en_attente_retour_materiel` | Email de notification au client (délai 10 jours ouvrés, frais 600€ si non restitué) |
| `terminee` | Email d'invitation à laisser un avis au client |

### 8.2 Calcul du prix d'une commande

```
Prix menu de base : prix_menu × (nombre_personnes / nombre_personne_min)

Réduction 10% : si nombre_personnes ≥ nombre_personne_min + 5
  → prix_menu = prix_menu × 0.90

Frais de livraison :
  Si ville_livraison == "Bordeaux" → 0€
  Sinon → 5€ + (distance_km × 0.59€)
    distance_km = GoogleMapsService.calculateDistance(adresse)
    Si API Google Maps indisponible → estimation par fallback

Prix total = prix_menu + prix_livraison
```

Les prix sont **snapshotés** dans la table COMMANDE au moment de la création. Toute modification ultérieure du prix du menu n'affecte pas les commandes existantes.

### 8.3 Gestion du matériel prêté

Certains menus nécessitent du matériel (vaisselle, équipement de cuisson…) défini via la table `MENU_MATERIEL`. Lors d'une commande :

1. L'employé enregistre le prêt de matériel (`POST /api/commandes/{id}/material`)
2. La commande passe `materiel_pret = true`
3. Après livraison, le statut passe en `en_attente_retour_materiel`
4. Un email est envoyé au client : **10 jours ouvrés** pour restituer (mentionné dans les CGV)
5. Un script planifié (`scripts/check_overdue_materials.php`) vérifie les retards
6. L'employé enregistre le retour (`POST /api/commandes/{id}/return-material`) → `terminee`

### 8.4 Flux d'inscription

1. Le visiteur remplit le formulaire (nom, prénom, GSM, email, adresse, mot de passe)
2. `UserValidator` valide les données (format, unicité email, force mot de passe)
3. `AuthService` hash le mot de passe avec Argon2ID
4. `UserRepository` insère l'utilisateur avec le rôle `UTILISATEUR`
5. `AuthService` génère un JWT, posé en cookie HttpOnly
6. `CsrfService` pose le cookie csrfToken
7. `MailerService` envoie l'email de bienvenue
8. Redirection vers la page d'accueil (connecté)

### 8.5 Flux de réinitialisation du mot de passe

1. L'utilisateur saisit son email sur la page « Mot de passe oublié »
2. `ResetTokenRepository` crée un token aléatoire avec date d'expiration
3. `MailerService` envoie un email avec un lien contenant le token
4. L'utilisateur clique le lien → page de reset avec le token en paramètre URL
5. Il saisit son nouveau mot de passe (mêmes critères de sécurité)
6. `AuthService` hash le nouveau mot de passe, `UserRepository` le met à jour
7. `ResetTokenRepository` marque le token comme utilisé

### 8.6 Flux des avis

1. La commande passe en statut `terminee` → email d'invitation au client
2. Le client crée un avis depuis son espace profil (note 1-5 + commentaire)
3. L'avis est stocké dans MongoDB (primaire) et dans `AVIS_FALLBACK` (MySQL)
4. Statut initial : `en_attente`
5. Un employé/admin valide ou refuse l'avis depuis le dashboard
6. Les avis validés apparaissent sur la page d'accueil (carrousel)
7. Si MongoDB est indisponible, les avis sont lus depuis `AVIS_FALLBACK`

---

## 9. Tests

### 9.1 Stratégie de test

Le projet adopte une approche de test multi-niveaux :

| Niveau | Outil | Couverture | Fichiers |
|---|---|---|---|
| **Tests unitaires backend** | PHPUnit | Controllers, Services, Validators, Middlewares, Core, Exceptions | 32 fichiers |
| **Tests unitaires frontend** | Vitest + jsdom | Services API, Widgets, DOM, Utilitaires | 20 fichiers + 4 helpers |
| **Tests d'intégration API** | Postman / Newman | Flux bout en bout (inscription, login, commande, menus…) | 10 collections |
| **CI/CD automatisé** | GitHub Actions | Exécution automatique à chaque push | 4 workflows |

### 9.2 Tests backend — PHPUnit (32 fichiers)

**Base de données de test isolée** : MySQL sur le port `:3307` (conteneur `vite-mysql-test`), MongoDB sur `:27018` (conteneur `vite-mongodb-test`). Configuration dans `.env.test`.

#### Tests des Controllers (10)

| Fichier | Teste |
|---|---|
| `AuthControllerTest` | Inscription, connexion, déconnexion, check session, reset password, mise à jour profil |
| `MenuControllerTest` | CRUD menus avec validation, filtres, permissions rôles |
| `PlatControllerTest` | CRUD plats, allergènes, permissions |
| `CommandeControllerTest` | Création commande, modification, changement statut, calcul prix |
| `AvisControllerTest` | Création avis, validation, refus, permissions |
| `ContactControllerTest` | Envoi formulaire contact, validation |
| `HoraireControllerTest` | Listing, modification, permissions |
| `MaterielControllerTest` | CRUD matériel, permissions |
| `StatsControllerTest` | Statistiques, filtres, permissions admin |
| `AccessControlTest` | Vérification des contrôles d'accès (rôles, auth) sur tous les endpoints |

#### Tests des Services (7)

| Fichier | Teste |
|---|---|
| `AuthServiceTest` | Hash Argon2ID, génération JWT, vérification credentials |
| `UserServiceTest` | CRUD users, désactivation, validation email unique |
| `UserServiceExceptionTest` | Cas d'erreur du UserService |
| `CommandeServiceTest` | Calcul prix, réductions, frais livraison, transitions statut |
| `ContactServiceTest` | Enregistrement contact, envoi email |
| `GoogleMapsServiceTest` | Calcul distance, fallback API |
| `MailerServiceTest` | Envoi emails, templates, gestion erreurs SMTP |

#### Tests des Validators (7)

| Fichier | Teste |
|---|---|
| `UserValidatorTest` | Validation inscription (email, mot de passe, GSM, adresse) |
| `LoginValidatorTest` | Validation connexion |
| `MenuValidatorTest` | Validation création/modification menu |
| `CommandeValidatorTest` | Validation commande (dates, personnes, stock) |
| `ContactValidatorTest` | Validation formulaire contact |
| `HoraireValidatorTest` | Validation horaires |
| `MaterielValidatorTest` | Validation matériel |

#### Tests des Middlewares (3)

| Fichier | Teste |
|---|---|
| `CorsMiddlewareTest` | Headers CORS, preflight OPTIONS, whitelist |
| `RateLimitMiddlewareTest` | Limitation par IP, reset après expiration |
| `SecurityHeadersMiddlewareTest` | Header CSP, directives par défaut, configuration custom |

#### Tests Core (3) et Exceptions (2)

- `RouterTest` : Résolution de routes, paramètres dynamiques, middlewares de route
- `RequestTest` : Parsing body JSON, attributs, paramètres
- `ResponseTest` : Sérialisation JSON, codes HTTP, headers
- `AuthExceptionTest` : Messages d'erreur, codes HTTP
- `InvalidCredentialsExceptionTest` : Message et code 401

### 9.3 Tests frontend — Vitest (20 fichiers + 4 helpers)

**Environnement** : Vitest avec jsdom (simulation DOM navigateur).

#### Tests des Services API (6)

Chaque service est testé avec des mocks de `fetch()` pour vérifier les appels API, les headers (CSRF, credentials), la gestion d'erreurs.

| Fichier | Service testé |
|---|---|
| `authService.test.js` | Login, register, logout, check, getCsrfToken, addCsrfHeader |
| `menuService.test.js` | getAll, getById, getThemes, getRegimes, create, update, delete |
| `commandeService.test.js` | create, getMyOrders, calculatePrice, modify, cancel |
| `avisService.test.js` | create, getPublic, getAll, validate, delete |
| `platService.test.js` | getAll, getByType, getAllergenes, CRUD |
| `adminService.test.js` | getEmployees, createEmployee, disableUser, getStats |

#### Tests DOM (4)

Tests de formulaires avec simulation d'interactions utilisateur :

| Fichier | Page testée |
|---|---|
| `inscription-form.test.js` | Formulaire inscription (validation, soumission, erreurs) |
| `connexion-form.test.js` | Formulaire connexion |
| `reset-form.test.js` | Formulaire reset mot de passe |
| `contact-form.test.js` | Formulaire contact |

#### Tests unitaires (7)

| Fichier | Composant testé |
|---|---|
| `helpers.test.js` | `escapeHtml()`, `formatPrice()`, `formatDate()` |
| `toast.test.js` | Notifications toast (affichage, auto-dismiss) |
| `logger.test.js` | Logging conditionnel (dev vs prod) |
| `password-toggle.test.js` | Toggle visibilité mot de passe |
| `components.test.js` | Chargement dynamique navbar/footer |
| `navbar.test.js` | Menu mobile (ouverture, fermeture, scroll) |
| `adminGuard.test.js` | Protection pages admin (rôle, redirection) |

#### Tests Widgets (3)

| Fichier | Widget testé |
|---|---|
| `avis-carousel.test.js` | Carrousel avis (navigation, affichage) |
| `menus-carousel.test.js` | Carrousel menus |
| `demo-cube.test.js` | Animation cube 3D |

#### Helpers de test (4)

| Fichier | Rôle |
|---|---|
| `setup-globals.js` | Setup global (window, document, fetch mock) |
| `dom-helpers.js` | Utilitaires création DOM pour tests |
| `mock-fetch.js` | Mock de fetch() configurable |
| `load-script.js` | Chargement de scripts JS dans jsdom |

### 9.4 Tests d'intégration API — Postman/Newman (10 collections)

Collections Postman exécutées automatiquement dans la CI via Newman :

| Collection | Flux testé |
|---|---|
| `inscription` | Inscription complète (validation, succès, doublons) |
| `login` | Connexion (succès, échec, rate limit) |
| `logout` | Déconnexion |
| `commande` | Flux complet de commande |
| `contact` | Envoi formulaire contact |
| `e2e_menus_plats` | CRUD menus et plats bout en bout |
| `e2e_password_reset` | Reset mot de passe bout en bout |

Environnements disponibles : `local`, `test`, `local_api`.

### 9.5 CI/CD — GitHub Actions (4 workflows)

| Workflow | Trigger | Actions |
|---|---|---|
| `test-backend.yml` | Push/PR sur `develop` et `main` | Setup PHP + MySQL + MongoDB → Composer install → PHPUnit → Newman/Postman |
| `frontend-tests.yml` | Push/PR sur `develop` et `main` | Setup Node.js → npm install → Vitest |
| `deploy-azure.yml` | Push sur `main` (après merge) | Build Docker → Push GHCR → Deploy Azure App Service |
| `email-integration.yml` | Dispatch manuel | Tests d'intégration email (SMTP) |

### 9.6 Lancer les tests

```bash
# Tests backend (PHPUnit) — depuis le conteneur PHP
docker exec vite-php-app ./vendor/bin/phpunit

# Tests frontend (Vitest) — depuis le host
cd frontend && npx vitest

# Tests Postman (Newman) — depuis le host
npx newman run backend/tests/postman/commande.postman_collection.json \
  -e backend/tests/postman/local.postman_environment.json
```

---

## 10. Performance et accessibilité

### 10.1 Optimisations SQL

| Optimisation | Implémentation |
|---|---|
| **Index** | Index sur les clés étrangères, `email` (UNIQUE), colonnes de recherche fréquentes |
| **Vues précalculées** | 3 vues SQL (`v_menus_actifs`, `v_commandes_en_cours`, `v_avis_valides`) pour les requêtes fréquentes |
| **Triggers** | Traçabilité automatique des statuts (pas de requête INSERT manuelle dans `COMMANDE_STATUT`) |
| **Prepared statements** | Toutes les requêtes utilisent des prepared statements PDO (sécurité + performance) |
| **Snapshots de prix** | Les prix sont copiés dans la commande — pas de jointure à chaque consultation |

### 10.2 Optimisations frontend

| Optimisation | Implémentation | Fichier(s) |
|---|---|---|
| **Skeleton screens** | Affichage de placeholders animés pendant le chargement des données API | `utils/skeleton.js` + `components/skeleton.css` |
| **Scroll-reveal** | Animations d'apparition au scroll (Intersection Observer) — lazy rendering | `utils/scroll-reveal.js` + `components/scroll-reveal.css` |
| **Parallax** | Effet parallax optimisé (requestAnimationFrame) | `widgets/scroll-parallax.js` |
| **Chargement dynamique** | Navbar et footer chargés une seule fois via `fetch()` et injectés dans toutes les pages | `core/components.js` |
| **CSS @layer** | Pas de `!important`, spécificité prévisible — CSS plus petit et maintenable | Architecture @layer |
| **Aucun framework JS** | Zéro bundle, zéro runtime — temps de chargement < 1s sur connexion moyenne | Vanilla JS |
| **Design tokens** | Toutes les valeurs centralisées — changement de thème en modifiant `_tokens.css` uniquement | `_tokens.css` |

### 10.3 Accessibilité (RGAA)

| Critère | Implémentation |
|---|---|
| **HTML sémantique** | Utilisation de `<header>`, `<nav>`, `<main>`, `<section>`, `<footer>`, `<article>` |
| **Attributs alt** | Toutes les images ont un `alt_text` (stocké en BDD dans `IMAGE_MENU`) |
| **Contraste** | Palette de couleurs définie dans les design tokens avec ratios de contraste WCAG AA |
| **Navigation clavier** | Formulaires accessibles via Tab, boutons focusables |
| **Labels de formulaires** | Chaque input a un `<label>` associé (attribut `for`) |
| **Messages d'erreur** | Affichés textuellement (pas uniquement par couleur) |
| **Responsive** | Design adaptatif desktop/tablette/mobile via media queries |
| **Police lisible** | Inter (sans-serif), taille de base 1rem, espacement contrôlé |

---

*Document généré le 18 février 2026 — Reflète l'état du code en production.*
