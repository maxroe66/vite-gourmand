
[![Tests backend automatisés](https://github.com/maxroe66/vite-gourmand/actions/workflows/test-backend.yml/badge.svg?branch=develop)](https://github.com/maxroe66/vite-gourmand/actions/workflows/test-backend.yml?query=branch%3Adevelop)
[![Tests frontend automatisés](https://github.com/maxroe66/vite-gourmand/actions/workflows/frontend-tests.yml/badge.svg?branch=develop)](https://github.com/maxroe66/vite-gourmand/actions/workflows/frontend-tests.yml?query=branch%3Adevelop)
[![Déploiement Azure](https://github.com/maxroe66/vite-gourmand/actions/workflows/deploy-azure.yml/badge.svg?branch=develop)](https://github.com/maxroe66/vite-gourmand/actions/workflows/deploy-azure.yml?query=branch%3Adevelop)

# Vite & Gourmand
Application web de gestion de menus, commandes et avis.

- **Backend** : PHP 8+ (MySQL + MongoDB), architecture MVC, API REST JSON
- **Frontend** : HTML5 / CSS3 (architecture @layer) / JavaScript vanilla (ES6+)

---

## 🚀 Vue d’ensemble
Vite & Gourmand permet :
- aux visiteurs de consulter les menus et s’inscrire
- aux utilisateurs de commander et laisser un avis
- aux employés de gérer les menus, commandes et avis
- aux administrateurs de consulter des statistiques, gérer les menus/commandes/avis et la gestion des employés

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [Documentation technique](docs/documentation_technique/DOCUMENTATION_TECHNIQUE.md) | Architecture, modèle de données, API REST, sécurité, tests |
| [Documentation déploiement](docs/documentation_technique/DOCUMENTATION_DEPLOIEMENT.md) | Docker, Azure, CI/CD, SSL, troubleshooting |
| [Manuel d'utilisation](docs/documentation_technique/MANUEL_UTILISATION.md) | Parcours utilisateur, comptes de test, captures |
| [Gestion de projet](docs/documentation_technique/GESTION_PROJET.md) | Méthodologie Kanban, chronologie, Git flow, bilan |
| [Diagrammes](docs/diagrammes/) | MCD, MLD, UML (68 classes), cas d'utilisation (35 UC), séquences |

---

## ⚡ Démarrage rapide (DEV)

**Prérequis :** Docker + Docker Compose

### 1. Cloner le dépôt
```bash
git clone https://github.com/maxroe66/vite-gourmand.git
cd vite-gourmand
```

### 2. Configurer les variables d'environnement
```bash
cp .env.example .env
```
> Les valeurs par défaut fonctionnent telles quelles. Aucune modification n'est nécessaire pour un usage local.

### 3. Lancer les services
```bash
docker compose up -d
```

### 4. Initialiser le compte administrateur
```bash
docker exec vite-php-app php scripts/setup/setup-admin-password.php
```

### Accès locaux
| Service | URL |
|---|---|
| Application | http://localhost:8000 |
| phpMyAdmin | http://localhost:8081 |
| Mongo Express | http://localhost:8082 |

**Bases de données DEV :**
- MySQL : `vite_gourmand` (port 3306)
- MongoDB : `vite_gourmand` (port 27017)

---

## 🧪 Tests backend (DB de test + API)

**Configuration :**
```bash
cp .env.test.example .env.test
```
> Les valeurs par défaut correspondent aux containers Docker de test.

**Lancer les tests :**
```bash
./scripts/tests/test_backend.sh
```

**Bases de données TEST :**
- MySQL : `vite_gourmand_test` (port 3307)
- MongoDB : `vite_gourmand_test` (port 27018)

---

## 🔄 CI/CD (GitHub Actions)

### CI (tests)
- Workflow : `.github/workflows/test-backend.yml`
- Lance les tests backend (PHPUnit)
- Lance des tests Postman via Newman
- Démarre MySQL + MongoDB en services GitHub Actions (bases de test)

### CD (build & publication de l’image Docker)
- Workflow : `.github/workflows/publish-image.yml`
- Build l'image Docker via `docker/azure/Dockerfile.azure`
- Push l’image sur GitHub Container Registry (GHCR) :
  - `ghcr.io/maxroe66/vite-gourmand:develop`
  - `ghcr.io/maxroe66/vite-gourmand:<sha>`


### CD (déploiement Azure App Service)

- **Workflow** : `.github/workflows/deploy-azure.yml`
- Configure l’App Service pour utiliser l’image SHA immuable depuis GHCR
- Redémarre l’application
- **Post-checks** :
  - Health-check HTTP (`APP_BASE_URL`)
  - Test DB Azure : `SELECT NOW()` avec SSL (`--ssl-mode=REQUIRED`)

---

## 🚢 Déploiement (Azure App Service — Container)

- **Image Docker** :
  - `ghcr.io/maxroe66/vite-gourmand:<sha>` (image immuable)

- **Variables d’environnement Azure**
  À définir dans Azure → Web App → Variables d’environnement :
  ```env
  WEBSITES_PORT=8080
  LOG_FILE=/tmp/app.log
  DB_HOST=vite-gourmand-mysql-dev.mysql.database.azure.com
  DB_NAME=vite_et_gourmand
  DB_USER=vgadmin (sans suffixe @server)
  DB_PASSWORD=********
  DB_SSL=true

  # Stockage des images uploadées (optionnel mais recommandé)
  AZURE_STORAGE_CONNECTION_STRING=DefaultEndpointsProtocol=https;AccountName=...;AccountKey=...;EndpointSuffix=core.windows.net
  AZURE_STORAGE_CONTAINER=uploads
  ```

  > **Note :** Sans `AZURE_STORAGE_CONNECTION_STRING`, les images uploadées par les administrateurs sont stockées dans le filesystem du conteneur et **seront perdues à chaque redéploiement**. Avec cette variable configurée, les images sont persistées dans Azure Blob Storage.

- **Endpoints de vérification**
  ```http
  GET /health
  GET /api/auth/test
  ```

---

## 🖼️ Stockage des images

Les images des menus sont gérées via un `StorageService` à double stratégie :

| Environnement | Stratégie | Persistance |
|---|---|---|
| **Dev local** (Docker Compose) | Filesystem hôte via bind mount (`public/assets/uploads/`) | ✅ Persistent |
| **Production Azure** (avec Blob Storage) | Azure Blob Storage (conteneur `uploads`) | ✅ Persistent |
| **Production Azure** (sans Blob Storage) | Filesystem du conteneur | ❌ Perdu au redéploiement |

**Fonctionnement :**
- L'admin peut uploader une image (JPEG, PNG, WebP, GIF — max 5 Mo) ou coller une URL externe
- L'upload passe par `POST /api/upload` (protégé CSRF + auth + rôle employé/admin)
- Les URLs des images sont stockées en base de données (table `IMAGE_MENU`), pas les fichiers
- Les images statiques du site (hero, logos) sont versionnées dans Git (`public/assets/images/`) et embarquées dans l'image Docker

**Pour configurer Azure Blob Storage en production :**
1. Créer un Storage Account Azure
2. Créer un conteneur Blob nommé `uploads` (accès public Blob)
3. Définir `AZURE_STORAGE_CONNECTION_STRING` et `AZURE_STORAGE_CONTAINER` dans les variables d'environnement de l'App Service

---

## ⚙️ Configuration

Le projet utilise plusieurs fichiers d'environnement, un par contexte :

| Fichier | Rôle | Versionné |
|---|---|---|
| `.env.example` | Template pour le développement local + Docker | ✅ Oui |
| `.env.test.example` | Template pour les tests | ✅ Oui |
| `.env.azure.example` | Template pour le déploiement Azure | ✅ Oui |
| `.env` | Configuration DEV (secrets réels) | ❌ Ignoré |
| `.env.test` | Configuration tests | ❌ Ignoré |

> **Sécurité :** Les fichiers contenant des secrets réels (`.env`, `.env.test`, `.env.azure`) sont exclus du dépôt via `.gitignore`. Seuls les templates (`.env.example`, `.env.test.example`, `.env.azure.example`) sont versionnés.