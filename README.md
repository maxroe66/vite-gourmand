
[![Tests backend automatisés](https://github.com/maxroe66/vite-gourmand/actions/workflows/test-backend.yml/badge.svg?branch=develop)](https://github.com/maxroe66/vite-gourmand/actions/workflows/test-backend.yml?query=branch%3Adevelop)
[![Déploiement Azure](https://github.com/maxroe66/vite-gourmand/actions/workflows/deploy-azure.yml/badge.svg?branch=develop)](https://github.com/maxroe66/vite-gourmand/actions/workflows/deploy-azure.yml?query=branch%3Adevelop)

# Vite & Gourmand
Application web de gestion de menus, commandes et avis.

- **Backend** : PHP (MySQL + MongoDB)
- **Frontend** : (à compléter)

---

## 🚀 Vue d’ensemble
Vite & Gourmand permet :
- aux visiteurs de consulter les menus et s’inscrire
- aux utilisateurs de commander et laisser un avis
- aux employés de gérer les menus, commandes et avis
- aux administrateurs de consulter des statistiques, gérer les menus/commandes/avis et la gestion des employés

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
docker exec vite-php-app php scripts/setup-admin-password.php
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
- Build l’image Docker via `Dockerfile.azure`
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
  ```

- **Endpoints de vérification**
  ```http
  GET /health
  GET /api/auth/test
  ```

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