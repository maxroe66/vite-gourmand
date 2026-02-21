
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
| [Manuel d'utilisation](docs/documentation_technique/MANUEL_UTILISATION.pdf) | Parcours utilisateur, comptes de test, captures |
| [Gestion de projet](docs/documentation_technique/GESTION_PROJET.md) | Méthodologie Kanban, chronologie, Git flow, bilan |
| [Diagrammes](docs/diagrammes/) | MCD, MLD, UML (68 classes), cas d'utilisation (35 UC), séquences |

---

## ⚡ Démarrage rapide (DEV)

> **Le projet s'exécute entièrement dans Docker.** Aucun PHP, Composer, MySQL ou MongoDB n'est à installer sur votre machine. Seuls **Git** et **Docker Desktop** sont nécessaires.

---

### Étape 0 — Installer les outils requis

Si vous partez d'un poste avec uniquement VS Code installé, suivez ces étapes **dans l'ordre**.

#### 0.1 Installer Git

Git est nécessaire pour cloner le dépôt.

<details>
<summary><strong>Windows</strong></summary>

1. Télécharger l'installeur : https://git-scm.com/download/win
2. Lancer l'installeur et **garder toutes les options par défaut** (cocher "Git from the command line and also from 3rd-party software")
3. **Redémarrer VS Code** après l'installation
4. Ouvrir un terminal dans VS Code (`Ctrl + ù` ou menu **Terminal → Nouveau terminal**) et vérifier :
   ```bash
   git --version
   ```
</details>

<details>
<summary><strong>macOS</strong></summary>

Git est souvent pré-installé sur macOS. Vérifier dans le terminal VS Code (`Cmd + ù`) :
```bash
git --version
```
Si la commande n'est pas reconnue, une popup Apple proposera automatiquement d'installer les **Xcode Command Line Tools** — cliquer sur **Installer** et patienter. Sinon, lancer manuellement :
```bash
xcode-select --install
```
</details>

<details>
<summary><strong>Linux (Ubuntu / Debian)</strong></summary>

```bash
sudo apt update && sudo apt install -y git
git --version
```
</details>

#### 0.2 Installer Docker Desktop

Docker permet d'exécuter l'application dans des conteneurs isolés (PHP, MySQL, MongoDB, Apache…). **Docker Compose est inclus dans Docker Desktop.**

<details>
<summary><strong>Windows</strong></summary>

1. **Activer WSL2** (obligatoire pour Docker sur Windows) :
   - Ouvrir **PowerShell en administrateur** et exécuter :
     ```powershell
     wsl --install
     ```
   - **Redémarrer l'ordinateur** quand demandé
2. Télécharger Docker Desktop : https://www.docker.com/products/docker-desktop/
3. Installer et **laisser les options par défaut** (s'assurer que "Use WSL 2 based engine" est coché)
4. **Lancer Docker Desktop** (icône dans la barre des tâches — attendre que le statut passe au vert "Engine running")
5. **Redémarrer VS Code**, puis vérifier dans le terminal :
   ```bash
   docker --version
   docker compose version
   ```

> **Important :** Docker Desktop doit être **lancé et en cours d'exécution** (icône verte dans la barre des tâches) avant d'utiliser les commandes Docker.
</details>

<details>
<summary><strong>macOS</strong></summary>

1. Télécharger Docker Desktop : https://www.docker.com/products/docker-desktop/
2. Ouvrir le `.dmg`, glisser Docker dans Applications
3. Lancer Docker Desktop et attendre que le statut passe au vert
4. Vérifier dans le terminal :
   ```bash
   docker --version
   docker compose version
   ```
</details>

<details>
<summary><strong>Linux (Ubuntu / Debian)</strong></summary>

```bash
# Installer Docker Engine + Compose plugin
sudo apt update
sudo apt install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Autoriser votre utilisateur à utiliser Docker sans sudo
sudo usermod -aG docker $USER
newgrp docker

# Vérifier
docker --version
docker compose version
```
</details>

#### 0.3 (Optionnel) Extensions VS Code recommandées

Ces extensions ne sont **pas nécessaires** pour faire fonctionner le projet, mais améliorent le confort :

| Extension | ID VS Code | Utilité |
|-----------|-----------|---------|
| Docker | `ms-azuretools.vscode-docker` | Interface visuelle pour gérer les conteneurs, voir les logs |
| PHP Intelephense | `bmewburn.vscode-intelephense-client` | Autocomplétion et navigation dans le code PHP |
| MySQL Client | `cweijan.vscode-mysql-client2` | Consulter la base de données depuis VS Code |
| REST Client | `humao.rest-client` | Tester les endpoints API directement |

Pour les installer, exécuter dans le terminal VS Code :
```bash
code --install-extension ms-azuretools.vscode-docker
code --install-extension bmewburn.vscode-intelephense-client
code --install-extension cweijan.vscode-mysql-client2
code --install-extension humao.rest-client
```

#### Vérification des prérequis

Avant de continuer, **vérifier que les deux commandes suivantes fonctionnent** dans le terminal VS Code :

```bash
git --version        # Doit afficher : git version 2.x.x
docker --version     # Doit afficher : Docker version 2x.x.x
docker compose version  # Doit afficher : Docker Compose version v2.x.x
```

> Si `docker` n'est pas reconnu : s'assurer que Docker Desktop est **lancé** (Windows/macOS) ou que le service tourne (`sudo systemctl start docker` sur Linux).

---

### Étape 1 — Cloner le dépôt

```bash
git clone https://github.com/maxroe66/vite-gourmand.git
cd vite-gourmand
```

Puis ouvrir le dossier dans VS Code : **Fichier → Ouvrir le dossier** → sélectionner `vite-gourmand`.

### Étape 2 — Configurer les variables d'environnement

```bash
cp .env.example .env
```
> **Windows (si `cp` n'est pas reconnu) :** `copy .env.example .env`

> Les valeurs par défaut fonctionnent telles quelles. Aucune modification n'est nécessaire pour un usage local.

### Étape 3 — Lancer l'application

```bash
docker compose up -d
```

**C'est tout.** Tout est automatisé :

| Étape | Automatisation |
|-------|---------------|
| Installation des dépendances PHP (Composer) | ✅ Automatique au premier démarrage |
| Création de la base de données MySQL | ✅ Automatique (schéma + données de test) |
| Configuration de MongoDB | ✅ Automatique |
| Démarrage d'Apache | ✅ Attend automatiquement que PHP soit prêt |

> **⏳ Premier lancement :** l'installation des dépendances Composer prend environ 30-60 secondes.
> Pour suivre l'avancement en temps réel :
> ```bash
> docker compose logs -f php-app
> ```
> L'application est prête quand vous voyez : `✅ Dépendances Composer installées avec succès.`

### Étape 4 — Accéder à l'application

Ouvrir dans votre navigateur : **http://localhost:8000**

### Vérifier que tout fonctionne

```bash
# Vérifier l'état des conteneurs (tous doivent être "Up" ou "healthy")
docker compose ps

# Tester l'API (doit renvoyer : {"message":"API Auth OK"})
curl http://localhost:8000/api/auth/test
```

> **Windows (si `curl` n'est pas reconnu) :** ouvrir directement http://localhost:8000/api/auth/test dans le navigateur.

### (Optionnel) Personnaliser le mot de passe administrateur
```bash
docker exec vite-php-app php scripts/setup/setup-admin-password.php
```
> Les comptes de test sont déjà fonctionnels grâce aux fixtures SQL — cette étape n'est nécessaire que pour définir un mot de passe personnalisé.

### Accès locaux
| Service | URL |
|---|---|
| Application | http://localhost:8000 |
| phpMyAdmin | http://localhost:8081 |
| Mongo Express | http://localhost:8082 |

### Comptes de test

Les identifiants de démonstration sont fournis dans le **Manuel d'utilisation** (`docs/documentation_technique/MANUEL_UTILISATION.md`, section 16).

### Fonctionnalités disponibles

| Fonctionnalité | Disponible | Détail |
|---|---|---|
| Navigation, menus, plats | ✅ | — |
| Authentification / JWT | ✅ | Secret dev auto-généré |
| Commande complète | ✅ | Sélection plats, calcul prix, validation |
| Calcul réel distance livraison | ✅ | Clé Google Maps restreinte incluse dans `.env.example` |
| Envoi d'emails | ✅ | Emails capturés dans Mailtrap sandbox (voir ci-dessous) |
| Espace admin / employé | ✅ | Voir Manuel d'utilisation pour les identifiants |
| Upload images menus | ✅ | Stockage local (filesystem) |
| Avis clients | ✅ | Création, modération, carousel |

### 📬 Consulter les emails envoyés

L'application utilise **Mailtrap** (sandbox email) : les emails sont capturés et consultables en ligne, mais **ne sont jamais délivrés à de vrais destinataires**.

Pour voir les emails envoyés (inscription, confirmation de commande, reset mot de passe, contact…) :
1. Se connecter sur **https://mailtrap.io/signin** avec les identifiants fournis dans le Manuel d'utilisation
2. Aller dans **Email Testing → Inboxes → "My Sandbox"**
3. Tous les emails envoyés par l'application apparaissent ici

> **Note :** Les identifiants Mailtrap sont déjà renseignés dans `.env.example`. Aucune configuration supplémentaire n'est nécessaire.

### 🔒 Note de sécurité

Les credentials externes fournis dans `.env.example` sont des **clés restreintes/sandbox** dédiées à la démonstration locale :
- **Mailtrap** : inbox sandbox — aucun vrai email n'est délivré
- **Google Maps** : clé restreinte à `localhost` uniquement, limitée à l'API Distance Matrix
- **JWT** : secret dev auto-généré, jamais utilisé en production
- **Mots de passe BDD** : valeurs Docker locales, isolées dans des containers

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

## 🧪 Tests frontend

```bash
cd frontend && npm install && npx vitest --run
```
> Les tests frontend utilisent [Vitest](https://vitest.dev/) et couvrent la validation des formulaires, les interactions DOM et les services API.

---

## 🔄 CI/CD (GitHub Actions)

### CI (tests)
- Workflow : `.github/workflows/test-backend.yml`
- Lance les tests backend (PHPUnit)
- Lance des tests Postman via Newman
- Démarre MySQL + MongoDB en services GitHub Actions (bases de test)

### CD (build, publication & déploiement Azure)
- **Workflow** : `.github/workflows/deploy-azure.yml`
- Build l'image Docker via `docker/azure/Dockerfile.azure`
- Push l'image sur GitHub Container Registry (GHCR) :
  - `ghcr.io/maxroe66/vite-gourmand:develop`
  - `ghcr.io/maxroe66/vite-gourmand:<sha>`
- Configure l'Azure App Service pour utiliser l'image SHA immuable depuis GHCR
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

- **Endpoint de vérification**
  ```http
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

---

## ❓ Troubleshooting

<details>
<summary><strong>L'application ne répond pas sur http://localhost:8000</strong></summary>

1. Vérifier que tous les conteneurs tournent :
   ```bash
   docker compose ps
   ```
2. Si `vite-php-app` affiche `(health: starting)`, les dépendances Composer sont en cours d'installation. Patienter 30-60 secondes :
   ```bash
   docker compose logs -f php-app
   ```
3. Si `vite-apache` affiche `Exit` ou redémarre en boucle, c'est qu'il attend le healthcheck de PHP. Attendre que `vite-php-app` passe à `(healthy)`.
4. Si le problème persiste, rebuild complet :
   ```bash
   docker compose down -v
   docker compose up -d --build
   ```
</details>

<details>
<summary><strong>Erreur "port already in use" au lancement</strong></summary>

Un autre service utilise déjà le port 8000, 3306 ou 27017. Identifier le processus :
```bash
# Linux / macOS
sudo lsof -i :8000
# Windows (PowerShell)
netstat -ano | findstr :8000
```
Arrêter le processus concerné, ou modifier les ports dans `docker-compose.yml`.
</details>

<details>
<summary><strong>Erreur "permission denied" sur Docker (Linux)</strong></summary>

Ajouter votre utilisateur au groupe Docker :
```bash
sudo usermod -aG docker $USER
# Puis se reconnecter (ou redémarrer)
```
</details>

<details>
<summary><strong>La BDD semble vide ou les fixtures ne se chargent pas</strong></summary>

Les scripts SQL ne s'exécutent qu'au **premier** démarrage de MySQL (quand le volume est vierge). Pour réinitialiser :
```bash
docker compose down -v   # Supprime les volumes (données BDD)
docker compose up -d     # Recrée tout depuis zéro
```
</details>

<details>
<summary><strong>Composer install échoue dans le conteneur</strong></summary>

Si l'installation automatique échoue (problème réseau, etc.), vous pouvez la relancer manuellement :
```bash
docker exec vite-php-app bash -c "cd backend && composer install"
```
Puis vérifier le healthcheck :
```bash
docker compose ps   # vite-php-app doit être "healthy"
```
</details>

<details>
<summary><strong>"docker" n'est pas reconnu comme commande (Windows)</strong></summary>

1. S'assurer que **Docker Desktop est lancé** (icône baleine dans la barre des tâches, statut vert)
2. Si Docker Desktop vient d'être installé, **redémarrer VS Code** (le PATH est mis à jour au redémarrage)
3. Vérifier que Docker est dans le PATH : ouvrir un **nouveau** terminal dans VS Code (`Ctrl + ù`)
4. Si le problème persiste, redémarrer l'ordinateur
</details>

<details>
<summary><strong>"wsl --install" demande un redémarrage (Windows)</strong></summary>

C'est normal. WSL2 (Windows Subsystem for Linux) est un prérequis de Docker Desktop sur Windows. Après `wsl --install` :
1. Redémarrer l'ordinateur
2. Au redémarrage, une fenêtre Ubuntu peut s'ouvrir pour créer un compte — la fermer
3. Installer Docker Desktop
4. Relancer VS Code
</details>

<details>
<summary><strong>"cp" n'est pas reconnu (Windows CMD)</strong></summary>

Le terminal par défaut de VS Code sur Windows peut être CMD au lieu de PowerShell/Git Bash. Solutions :
```powershell
# PowerShell / CMD : utiliser copy au lieu de cp
copy .env.example .env
```
Ou changer le terminal par défaut dans VS Code : `Ctrl + Shift + P` → "Terminal: Select Default Profile" → choisir **Git Bash** ou **PowerShell**.
</details>

<details>
<summary><strong>Réinitialiser complètement le projet</strong></summary>

Pour repartir de zéro (supprime toutes les données, tous les conteneurs, tous les volumes) :
```bash
docker compose down -v --rmi local
docker compose up -d
```
</details>