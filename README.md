[![Tests backend automatisés](https://github.com/maxroe66/vite-gourmand/actions/workflows/test-backend.yml/badge.svg?branch=develop)](https://github.com/maxroe66/vite-gourmand/actions/workflows/test-backend.yml?query=branch%3Adevelop)

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

**Prérequis** : Docker + Docker Compose

```bash
# Lancer tous les services (backend, BDD, outils)
docker compose up -d
```

- Application : http://localhost:8000
- phpMyAdmin : http://localhost:8081
- Mongo Express : http://localhost:8082

**Bases utilisées en DEV :**
- MySQL : `vite_gourmand` (port 3306)
- MongoDB : `vite_gourmand` (port 27017)

---

## 🧪 Tests backend (DB de test + API)

Lance tout (reset DB test + PHPUnit + Newman) :

```bash
./scripts/test_backend.sh
```

**Bases utilisées en TEST :**
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

---

## ☁️ Déploiement (Azure App Service — Container)

### Image Docker
- `ghcr.io/maxroe66/vite-gourmand:develop`

### Variables d’environnement Azure
À définir dans Azure → Web App → Variables d’environnement :

- `WEBSITES_PORT=8080`
- `LOG_FILE=/tmp/app.log`

### Endpoints de vérification
- `GET /health`
- `GET /api/auth/test`

---

## ⚙️ Configuration

- `.env.example` : template (à copier vers `.env`)
- `.env` : configuration DEV (base réelle)
- `.env.test` : configuration TEST (base test)

> ⚠️ Ne versionnez pas les fichiers `.env` contenant des secrets.