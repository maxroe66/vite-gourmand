# Documentation de déploiement — Vite & Gourmand

> **Version :** 2.0  
> **Date :** 18 février 2026  
> **Auteur :** Maxime Roé  
> **Statut :** Validé — Production Azure active

---

## Table des matières

1. [Prérequis et environnement](#1-prérequis-et-environnement)
2. [Architecture Docker — Développement local](#2-architecture-docker--développement-local)
3. [Configuration des variables d'environnement](#3-configuration-des-variables-denvironnement)
4. [Installation et lancement local](#4-installation-et-lancement-local)
5. [Base de données — Initialisation](#5-base-de-données--initialisation)
6. [HTTPS local (optionnel)](#6-https-local-optionnel)
7. [Architecture de production — Azure](#7-architecture-de-production--azure)
8. [CI/CD — GitHub Actions](#8-cicd--github-actions)
9. [Monitoring et maintenance](#9-monitoring-et-maintenance)
10. [Dépannage](#10-dépannage)

---

## 1. Prérequis et environnement

### 1.1 Outils requis

| Outil | Version minimale | Usage |
|---|---|---|
| **Docker** | 24.x | Conteneurisation de tous les services |
| **Docker Compose** | 2.20+ | Orchestration multi-conteneurs |
| **Git** | 2.30+ | Gestion de version |
| **Node.js** | 18.x | Tests frontend (Vitest) |
| **Composer** | 2.x | Dépendances PHP (installé dans le conteneur) |
| **mkcert** (optionnel) | 1.4+ | Certificats SSL auto-signés pour HTTPS local |

### 1.2 Ports utilisés

| Port | Service | Accès |
|---|---|---|
| `8000` | Apache HTTP | `http://localhost:8000` |
| `8443` | Apache HTTPS | `https://localhost:8443` (si HTTPS activé) |
| `9000` | PHP-FPM | Interne uniquement (proxy Apache) |
| `3306` | MySQL | Connexion BDD principale |
| `3307` | MySQL Test | BDD de tests PHPUnit/Newman |
| `27017` | MongoDB | Base NoSQL principale |
| `27018` | MongoDB Test | BDD de tests MongoDB |
| `8081` | phpMyAdmin | `http://localhost:8081` |
| `8082` | Mongo Express | `http://localhost:8082` |

---

## 2. Architecture Docker — Développement local

### 2.1 Vue d'ensemble des services

```
┌─────────────────────────────────────────────────────────────────┐
│                        docker-compose.yml                       │
│                         réseau: vite-network                    │
│                                                                 │
│  ┌──────────────┐    proxy:fcgi     ┌──────────────────┐        │
│  │   Apache      │ ───────────────► │    PHP-FPM        │        │
│  │  vite-apache  │  :9000           │   vite-php-app    │        │
│  │  :8000/:8443  │                  │   :9000           │        │
│  └──────────────┘                   └───────┬──────────┘        │
│                                         PDO │  MongoDB\Client   │
│                                             │                    │
│              ┌──────────────────────────────┼──────────┐        │
│              │                              │          │        │
│  ┌───────────▼──┐  ┌───────────────┐  ┌────▼───────┐  │        │
│  │    MySQL      │  │  MySQL Test   │  │  MongoDB   │  │        │
│  │  vite-mysql   │  │ vite-mysql-   │  │ vite-      │  │        │
│  │  :3306        │  │  test :3307   │  │ mongodb    │  │        │
│  └──────────────┘  └───────────────┘  │ :27017     │  │        │
│                                       └────────────┘  │        │
│                                                        │        │
│  ┌───────────────┐  ┌───────────────┐  ┌────────────┐ │        │
│  │  phpMyAdmin   │  │ Mongo Express │  │ MongoDB    │ │        │
│  │  :8081        │  │  :8082        │  │ Test       │ │        │
│  └───────────────┘  └───────────────┘  │ :27018     │ │        │
│                                        └────────────┘ │        │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Détail des conteneurs

#### PHP-FPM (`vite-php-app`)

| Paramètre | Valeur |
|---|---|
| Image de base | `php:8.1-fpm` |
| Extensions PHP | `pdo`, `pdo_mysql`, `zip`, `mbstring`, `mongodb` (PECL) |
| Utilisateur | `vite_user:vite_group` (UID/GID 1000) — non-root |
| Composer | Copié depuis l'image officielle `composer:latest` |
| Volumes | Tout le projet monté dans `/var/www/vite_gourmand` |
| Config PHP | `docker/php/php.ini` : `memory_limit=256M`, `upload_max_filesize=50M`, `display_errors=On` |

#### Apache (`vite-apache`)

| Paramètre | Valeur |
|---|---|
| Image de base | `httpd:2.4` |
| Modules activés | `proxy`, `proxy_fcgi`, `rewrite`, `ssl`, `headers` |
| DocumentRoot | `/var/www/vite_gourmand/public` |
| VirtualHost HTTP | `vite.conf` — port 80 |
| VirtualHost HTTPS | `vite-ssl.conf` — port 443 (activation conditionnelle) |
| Entrypoint | `entrypoint.sh` — active HTTPS si `ENABLE_HTTPS=true` |

**Alias Apache configurés :**

```apache
Alias /frontend /var/www/vite_gourmand/frontend
Alias /assets   /var/www/vite_gourmand/public/assets
```

**Headers de sécurité (systématiques) :**
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- HSTS activé en HTTPS

**Front controller (RewriteRule) :**

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php [QSA,L]
```

Toute requête PHP est envoyée à PHP-FPM via `proxy:fcgi://php-app:9000`.

#### MySQL (`vite-mysql`)

| Paramètre | Valeur |
|---|---|
| Image | `mysql:8.0` |
| Charset | `utf8mb4` forcé (serveur + client via `my.cnf`) |
| SQL Mode | `STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION,...` |
| Volume | `mysql_data` (persistant) |
| Variables d'env | `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD` |

#### MySQL Test (`vite-mysql-test`)

Configuration identique à MySQL principal, sur le port **3307**, avec les variables `MYSQL_TEST_*`. Base isolée pour PHPUnit et Newman.

#### MongoDB (`vite-mongodb`)

| Paramètre | Valeur |
|---|---|
| Image | `mongo:4.4` |
| Config | `docker/mongodb/mongod.conf` |
| Bind | `0.0.0.0:27017` |
| Auth | Activée (`authorization: enabled`) |
| Storage | WiredTiger |
| Volume | `mongodb_data` (persistant) |

#### MongoDB Test (`vite-mongodb-test`)

Identique à MongoDB principal, sur le port **27018**. Base isolée pour les tests.

#### phpMyAdmin et Mongo Express

Interfaces web pour administrer les bases de données en développement :
- **phpMyAdmin** : `http://localhost:8081` — se connecte automatiquement à `vite-mysql`
- **Mongo Express** : `http://localhost:8082` — se connecte automatiquement à `vite-mongodb`

---

## 3. Configuration des variables d'environnement

### 3.1 Fichiers `.env`

Le projet utilise 3 fichiers de configuration :

| Fichier | Environnement | Priorité de chargement |
|---|---|---|
| `.env` | Développement local | 3 (défaut) |
| `.env.test` | Tests (PHPUnit, Newman) | 1 (prioritaire en mode test) |
| `.env.azure` | Production Azure | 2 (prioritaire si présent) |

**Ordre de chargement dans `index.php` :** `.env.test` → `.env.azure` → `.env`

### 3.2 Variables de développement local (`.env`)

```bash
# ─── Application ───
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# ─── MySQL ───
DB_HOST=mysql                    # Nom du service Docker
DB_PORT=3306
DB_NAME=vite_gourmand
DB_USER=vite_user
DB_PASSWORD=<mot_de_passe>

# ─── MongoDB ───
MONGO_HOST=mongodb               # Nom du service Docker
MONGO_PORT=27017
MONGO_DB=vite_gourmand
MONGO_USERNAME=vite_user
MONGO_PASSWORD=<mot_de_passe>

# ─── JWT ───
JWT_SECRET=<clé_secrète_HS256_minimum_32_caractères>

# ─── CORS ───
CORS_ALLOWED_ORIGINS=http://localhost:8000,https://localhost:8443
FRONTEND_ORIGIN=http://localhost:8000

# ─── Email (Mailtrap en dev) ───
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_USERNAME=<mailtrap_user>
MAIL_PASSWORD=<mailtrap_password>
MAIL_FROM_ADDRESS=noreply@vite-gourmand.fr
CONTACT_EMAIL=contact@vite-gourmand.fr

# ─── Google Maps API ───
GOOGLE_MAPS_API_KEY=<clé_api>

# ─── Docker Compose (interpolation) ───
MYSQL_ROOT_PASSWORD=<root_password>
MYSQL_DATABASE=vite_gourmand
MYSQL_USER=vite_user
MYSQL_PASSWORD=<mot_de_passe>
MYSQL_TEST_ROOT_PASSWORD=<test_root_password>
MYSQL_TEST_DATABASE=vite_gourmand_test
MYSQL_TEST_USER=test_user
MYSQL_TEST_PASSWORD=<test_password>
MONGO_INITDB_ROOT_USERNAME=vite_user
MONGO_INITDB_ROOT_PASSWORD=<mot_de_passe>
MONGO_INITDB_DATABASE=vite_gourmand
MONGO_TEST_INITDB_ROOT_USERNAME=test_user
MONGO_TEST_INITDB_ROOT_PASSWORD=<test_password>
MONGO_TEST_INITDB_DATABASE=vite_gourmand_test

# ─── Outils admin ───
PMA_HOST=mysql
PMA_USER=root
PMA_PASSWORD=<root_password>
ME_MONGO_HOST=mongodb
ME_MONGO_PORT=27017
ME_MONGO_ADMIN_USERNAME=vite_user
ME_MONGO_ADMIN_PASSWORD=<mot_de_passe>

# ─── HTTPS local (optionnel) ───
ENABLE_HTTPS=false
```

### 3.3 Variables de production Azure (`.env.azure`)

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<app-name>.azurewebsites.net

# ─── MySQL Azure Database ───
DB_HOST=<serveur>.mysql.database.azure.com
DB_PORT=3306
DB_NAME=vite_gourmand
DB_USER=<admin_user>
DB_PASSWORD=<mot_de_passe_fort>
DB_SSL=true                      # Active le TLS MySQL
DB_SSL_CA=/etc/ssl/azure/DigiCertGlobalRootCA.crt.pem

# ─── MongoDB Cosmos DB ───
MONGO_URI=mongodb://<account>:<key>@<account>.mongo.cosmos.azure.com:10255/?ssl=true&...

# ─── Azure Blob Storage (images) ───
AZURE_STORAGE_CONNECTION_STRING=DefaultEndpointsProtocol=https;AccountName=...
AZURE_STORAGE_CONTAINER=images

# ─── Email (SendGrid en prod) ───
SENDGRID_API_KEY=<sendgrid_api_key>

# ─── Port Azure ───
WEBSITES_PORT=8080
```

### 3.4 Variables de test (`.env.test`)

```bash
APP_ENV=test
DB_HOST=mysql-test              # Service Docker mysql-test
DB_PORT=3307
DB_NAME=vite_gourmand_test
DB_USER=test_user
DB_PASSWORD=<test_password>
MONGO_HOST=mongodb-test
MONGO_PORT=27018
MONGO_DB=vite_gourmand_test
```

---

## 4. Installation et lancement local

### 4.1 Clonage et configuration initiale

```bash
# 1. Cloner le dépôt
git clone https://github.com/maxroe66/vite-gourmand.git
cd vite-gourmand

# 2. Créer les fichiers d'environnement
cp .env.example .env
cp .env.test.example .env.test

# 3. Éditer .env avec vos valeurs (mots de passe, clés API)
nano .env
```

### 4.2 Lancement de l'infrastructure Docker

```bash
# Démarrer tous les services
docker compose up -d

# Vérifier que tous les conteneurs tournent
docker compose ps

# Résultat attendu :
# vite-apache       running   0.0.0.0:8000->80/tcp, 0.0.0.0:8443->443/tcp
# vite-php-app      running   9000/tcp
# vite-mysql        running   0.0.0.0:3306->3306/tcp
# vite-mysql-test   running   0.0.0.0:3307->3306/tcp
# vite-mongodb      running   0.0.0.0:27017->27017/tcp
# vite-mongodb-test running   0.0.0.0:27018->27017/tcp
# vite-phpmyadmin   running   0.0.0.0:8081->80/tcp
# vite-mongo-express running  0.0.0.0:8082->8081/tcp
```

### 4.3 Installation des dépendances PHP

```bash
# Installer les dépendances Composer dans le conteneur PHP
docker exec -it vite-php-app composer install

# Vérifier l'installation
docker exec -it vite-php-app php -m | grep -E "pdo_mysql|mongodb"
```

### 4.4 Installation des dépendances frontend (tests)

```bash
# Depuis la racine du projet (sur le host)
cd frontend
npm install
cd ..
```

### 4.5 Vérification

| URL | Service attendu |
|---|---|
| `http://localhost:8000` | Page d'accueil Vite & Gourmand |
| `http://localhost:8000/api/menus` | API JSON — liste des menus |
| `http://localhost:8081` | phpMyAdmin |
| `http://localhost:8082` | Mongo Express |

---

## 5. Base de données — Initialisation

### 5.1 Fichiers SQL disponibles

| Fichier | Rôle |
|---|---|
| `backend/database/sql/database_creation.sql` | Schéma complet (20 tables, 3 vues, 2 triggers, contraintes) |
| `backend/database/sql/database_seed.sql` | Données de démonstration (fixtures) |
| `backend/database/sql/database_complete.sql` | Schéma + seed combinés |
| `backend/database/sql/database_creation_test.sql` | Schéma pour la BDD de test |

### 5.2 Initialisation MySQL

```bash
# Méthode 1 : Schéma + fixtures séparément
docker exec -i vite-mysql mysql -u root -p<ROOT_PASSWORD> vite_gourmand \
  < backend/database/sql/database_creation.sql
docker exec -i vite-mysql mysql -u root -p<ROOT_PASSWORD> vite_gourmand \
  < backend/database/sql/database_seed.sql

# Méthode 2 : Tout en une commande
docker exec -i vite-mysql mysql -u root -p<ROOT_PASSWORD> vite_gourmand \
  < backend/database/sql/database_complete.sql

# Initialiser la base de test
docker exec -i vite-mysql-test mysql -u root -p<TEST_ROOT_PASSWORD> vite_gourmand_test \
  < backend/database/sql/database_creation_test.sql
```

### 5.3 Initialisation MongoDB

```bash
# Fichiers de setup MongoDB
docker exec -i vite-mongodb mongosh \
  --username <MONGO_USER> --password <MONGO_PASS> --authenticationDatabase admin \
  < backend/database/mongoDB/database_mongodb_setup.js
```

### 5.4 Comptes de test (fixtures)

Après l'initialisation des fixtures, les comptes de démonstration sont disponibles.
Consultez le **Manuel d'utilisation** (`MANUEL_UTILISATION.md`, section 16) pour les identifiants.

---

## 6. HTTPS local (optionnel)

### 6.1 Installation de mkcert

```bash
# macOS
brew install mkcert && mkcert -install

# Linux (Ubuntu/Debian)
sudo apt install libnss3-tools
curl -JLO "https://dl.filippo.io/mkcert/latest?for=linux/amd64"
chmod +x mkcert-v*-linux-amd64 && sudo mv mkcert-v*-linux-amd64 /usr/local/bin/mkcert
mkcert -install

# WSL (Windows)
# Installer mkcert dans WSL ET dans Windows (PowerShell : choco install mkcert)
```

### 6.2 Génération des certificats

```bash
# Script automatisé
./scripts/docker/init-https-local.sh

# OU manuellement
mkdir -p docker/certs
mkcert -cert-file docker/certs/vite.local.pem \
       -key-file docker/certs/vite.local-key.pem \
       vite.local localhost 127.0.0.1 ::1
```

### 6.3 Activation

```bash
# Dans .env, activer HTTPS
ENABLE_HTTPS=true

# Redémarrer Apache
docker compose restart apache

# Accéder en HTTPS
# https://localhost:8443
```

L'entrypoint Apache (`entrypoint.sh`) détecte `ENABLE_HTTPS=true` et :
1. Active le module SSL (`a]loadModule ssl_module`)
2. Ajoute `Listen 443` 
3. Inclut la configuration `vite-ssl.conf`
4. Lance Apache avec SSL + TLS 1.2+

---

## 7. Architecture de production — Azure

### 7.1 Services Azure utilisés

| Service Azure | Usage | Équivalent Docker local |
|---|---|---|
| **Azure App Service** (Linux) | Hébergement de l'application (PHP + Apache) | `vite-php-app` + `vite-apache` |
| **Azure Database for MySQL** | Base de données relationnelle | `vite-mysql` |
| **Azure Cosmos DB** (API MongoDB) | Base de données NoSQL | `vite-mongodb` |
| **Azure Blob Storage** | Stockage des images uploadées | Système de fichiers local |
| **GHCR** (GitHub Container Registry) | Registre d'images Docker | — |

### 7.2 Image Docker de production (`Dockerfile.azure`)

Contrairement au développement local (PHP-FPM + Apache séparés), la production utilise une **image unique** :

```
┌─────────────────────────────────────────┐
│     Dockerfile.azure                    │
│     Base : php:8.1-apache               │
│                                         │
│  ┌─────────────────────────────────┐    │
│  │ PHP 8.1 + Apache (mod_php)     │    │
│  │ Extensions : pdo, pdo_mysql,   │    │
│  │              mysqli, mongodb    │    │
│  │ Modules : rewrite, headers,    │    │
│  │           ssl                   │    │
│  └─────────────────────────────────┘    │
│                                         │
│  DocumentRoot : /var/www/html/public    │
│  AllowOverride : All                    │
│  DirectoryIndex : index.php             │
│                                         │
│  Certificat TLS : DigiCertGlobalRoot    │
│  (pour MySQL Azure en SSL)              │
│                                         │
│  Headers sécurité :                     │
│  - HSTS (31536000s, includeSubDomains)  │
│  - X-Content-Type-Options: nosniff      │
│  - X-Frame-Options: SAMEORIGIN          │
│  - Referrer-Policy: strict-origin       │
│                                         │
│  Dépendances : Composer 2 (--no-dev)    │
│  Port exposé : 80                       │
└─────────────────────────────────────────┘
```

Différences avec le setup local :

| Aspect | Développement local | Production Azure |
|---|---|---|
| Architecture | PHP-FPM + Apache (2 conteneurs) | PHP + Apache combinés (1 conteneur) |
| Communication PHP | Proxy FastCGI (port 9000) | mod_php (intégré) |
| Debugging | `display_errors=On`, `APP_DEBUG=true` | `display_errors=Off`, `APP_DEBUG=false` |
| Dépendances | Composer avec dev | Composer `--no-dev` |
| Images | Système de fichiers local | Azure Blob Storage |
| MySQL TLS | Non (réseau Docker interne) | Oui (certificat DigiCert) |
| MongoDB | Standalone local | Azure Cosmos DB (API MongoDB) |

### 7.3 Configuration Cosmos DB

Le fichier `config.php` détecte automatiquement Cosmos DB via le port (`10255`) ou le domaine (`cosmos`, `mongocluster`) et adapte les options de connexion MongoDB :

```php
// Détection automatique Cosmos DB
if ($port == 10255 || str_contains($host, 'cosmos') || str_contains($host, 'mongocluster')) {
    $options['ssl'] = true;
    $options['retryWrites'] = false;      // Cosmos DB ne supporte pas retryWrites
    $options['maxIdleTimeMS'] = 120000;
}
```

### 7.4 Redirection HTTPS en production

`public/index.php` force HTTPS en production via détection des headers de proxy Azure :

```php
if ($isProduction) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || isset($_SERVER['HTTP_X_ARR_SSL']);  // Header spécifique Azure

    if (!$isHttps) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit;
    }

    // HSTS
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
```

---

## 8. CI/CD — GitHub Actions

### 8.1 Vue d'ensemble des pipelines

```
┌──────────────────────────────────────────────────────────────────┐
│                     GitHub Actions Workflows                      │
│                                                                    │
│  ┌─────────────────────┐  ┌────────────────────┐  ┌──────────┐  │
│  │ test-backend.yml    │  │ frontend-tests.yml │  │ email-   │  │
│  │ push/PR → main,     │  │ push/PR modifiant  │  │ integr.  │  │
│  │ develop, feat/*     │  │ frontend/**        │  │ PR/cron/ │  │
│  │                     │  │                    │  │ manual   │  │
│  │ MySQL + MongoDB     │  │ Node 18            │  │          │  │
│  │ PHP 8.1 + Newman    │  │ npm ci             │  │ PHPUnit  │  │
│  │ → PHPUnit           │  │ → Vitest           │  │ + Newman │  │
│  │ → Newman            │  │                    │  │          │  │
│  └─────────────────────┘  └────────────────────┘  └──────────┘  │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │ deploy-azure.yml                                         │    │
│  │ Trigger : push sur develop                               │    │
│  │                                                           │    │
│  │ ┌──────────┐   ┌──────────┐   ┌─────────────────────┐   │    │
│  │ │  Build    │──►│  Deploy  │──►│  Post-deploy check  │   │    │
│  │ │  + Push   │   │  Azure   │   │  Health + Migrations│   │    │
│  │ │  GHCR     │   │  Webapp  │   │  + Admin + MongoDB  │   │    │
│  │ └──────────┘   └──────────┘   └─────────────────────┘   │    │
│  └──────────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────────┘
```

### 8.2 Pipeline de tests backend (`test-backend.yml`)

**Déclencheur :** Push ou PR sur `main`, `master`, `develop`, `feat/*`

**Services GitHub Actions :**
- MySQL 8.0 (port 3306, base `vite_gourmand_test`)
- MongoDB 4.4 (port 27017)

**Étapes :**

```
1. Checkout du code
2. Setup PHP 8.1 (shivammathur/setup-php)
   Extensions : pdo_mysql, mbstring, mongodb
3. Install Newman (npm install -g newman)
4. Setup BDD test :
   - MySQL : database_creation.sql + database_seed.sql
   - MongoDB : database_mongodb_setup.js
5. Composer install
6. Lancement serveur PHP intégré (php -S localhost:8000)
7. PHPUnit (32 tests)
8. Newman :
   - Collection inscription
   - Collection login
   - Collection logout
   - Collection password reset
   - Test JWT e2e
```

### 8.3 Pipeline de tests frontend (`frontend-tests.yml`)

**Déclencheur :** Push ou PR modifiant `frontend/**`

**Étapes :**

```
1. Checkout du code
2. Setup Node.js 18
3. npm ci (dans frontend/)
4. npm run test:ci (Vitest)
```

### 8.4 Pipeline de déploiement Azure (`deploy-azure.yml`)

**Déclencheur :** Push sur `develop`

**Job 1 — Build & Push :**

```
1. Checkout du code
2. Copie frontend/ dans public/frontend (pour servir via Apache)
3. Composer install --no-dev
4. Docker build (Dockerfile.azure)
5. Docker push vers GHCR :
   - ghcr.io/maxroe66/vite-gourmand:develop
   - ghcr.io/maxroe66/vite-gourmand:<sha>
```

**Job 2 — Deploy :**

```
1. Azure Login (AZURE_CREDENTIALS, service principal)
2. az webapp config container set :
   - Image GHCR
   - Variables d'env (DB, MongoDB, JWT, Mail, Storage)
3. az webapp restart
```

**Job 3 — Post-deploy check :**

```
1. Health check HTTP (20 retries, 10s d'intervalle)
2. Migrations MySQL via SSL :
   - database_creation.sql
   - database_seed.sql
3. Setup admin password (Argon2ID hash) 
4. Install mongosh
5. Init MongoDB Cosmos DB (database_mongodb_setup_cosmosdb.js)
```

**Secrets GitHub utilisés :**

| Secret | Usage |
|---|---|
| `AZURE_CREDENTIALS` | Service principal (JSON) pour `az login` |
| `AZURE_WEBAPP_NAME` | Nom de l'App Service |
| `AZURE_RESOURCE_GROUP` | Resource group Azure |
| `APP_BASE_URL` | URL publique de l'application |
| `AZURE_MYSQL_HOST` | Serveur MySQL Azure |
| `AZURE_MYSQL_DB` | Nom de la base |
| `AZURE_MYSQL_USER` | Utilisateur MySQL |
| `AZURE_MYSQL_PASS` | Mot de passe MySQL |
| `ADMIN_INITIAL_PASSWORD` | **Requis.** Mot de passe admin initial (≥ 12 chars, 1 maj., 1 min., 1 chiffre, 1 spécial). Le déploiement échouera si absent. |
| `AZURE_MONGO_URI` | URI Cosmos DB complète |

### 8.5 Pipeline d'intégration email (`email-integration.yml`)

**Déclencheur :** PR vers `develop` + cron quotidien + dispatch manuel

Teste l'envoi d'emails avec **graceful degradation** (fonctionne même sans secrets SMTP configurés). Vérifie que les appels d'envoi sont tracés dans les logs.

---

## 9. Monitoring et maintenance

### 9.1 Logs

| Composant | Emplacement | Format |
|---|---|---|
| **PHP applicatif** (Monolog) | `backend/logs/app.log` (dev) / `stderr` (prod) | JSON structuré |
| **Apache access** | `docker compose logs apache` | Combined Log Format |
| **Apache error** | `docker compose logs apache` | Standard error log |
| **MySQL** | `docker compose logs mysql` | MySQL error log |
| **Azure** | Portail Azure → App Service → Log stream | Stdout + Stderr |

### 9.2 Commandes de maintenance

```bash
# ─── Gestion Docker ───
docker compose up -d                     # Démarrer
docker compose down                      # Arrêter
docker compose restart apache            # Redémarrer Apache seul
docker compose logs -f php-app           # Suivre les logs PHP en temps réel
docker compose logs -f --tail=100 apache # 100 dernières lignes Apache

# ─── Shell dans les conteneurs ───
docker exec -it vite-php-app bash        # Shell PHP
docker exec -it vite-mysql mysql -u root -p  # Client MySQL
docker exec -it vite-mongodb mongosh     # Client MongoDB

# ─── Tests ───
docker exec vite-php-app ./vendor/bin/phpunit     # Tests backend
cd frontend && npx vitest                          # Tests frontend

# ─── Composer ───
docker exec -it vite-php-app composer install      # Installer les dépendances
docker exec -it vite-php-app composer update       # Mettre à jour

# ─── Base de données ───
docker exec -i vite-mysql mysqldump -u root -p vite_gourmand > backup.sql  # Backup
docker exec -i vite-mysql mysql -u root -p vite_gourmand < backup.sql      # Restore

# ─── Azure (si CLI installé) ───
az webapp log tail --name <app> --resource-group <rg>     # Logs en temps réel
az webapp restart --name <app> --resource-group <rg>      # Redémarrer
```

### 9.3 Script de vérification matériel

```bash
# Vérifier les retours de matériel en retard
docker exec vite-php-app php scripts/check_overdue_materials.php
```

Ce script identifie les commandes en `en_attente_retour_materiel` dont le délai de 10 jours ouvrés est dépassé.

---

## 10. Dépannage

### 10.1 Problèmes fréquents

| Symptôme | Cause probable | Solution |
|---|---|---|
| `localhost:8000` ne répond pas | Apache non démarré | `docker compose up -d apache` |
| Erreur 502 Bad Gateway | PHP-FPM non démarré | `docker compose restart php-app` |
| Erreur 500 sur `/api/*` | Erreur PHP (config, BDD) | `docker compose logs php-app` |
| `SQLSTATE[HY000] Connection refused` | MySQL non prêt | Attendre ~30s après `docker compose up`, vérifier `docker compose ps` |
| `MongoDB connection failed` | MongoDB non démarré ou auth incorrecte | Vérifier `MONGO_USERNAME`/`MONGO_PASSWORD` dans `.env` |
| CORS error dans le navigateur | `CORS_ALLOWED_ORIGINS` incorrect | Vérifier que l'URL dans `.env` correspond exactement (port inclus) |
| `403 Forbidden` sur requête POST | Token CSRF manquant ou invalide | Vérifier que `AuthService.addCsrfHeader()` est appelé |
| `401 Unauthorized` inattendu | Cookie `authToken` expiré (1h) | Se reconnecter |
| Page blanche (HTML) | Fichier HTML manquant | Vérifier que `frontend/pages/` contient le fichier demandé |
| phpMyAdmin inaccessible | Conteneur phpmyadmin arrêté | `docker compose up -d phpmyadmin` |

### 10.2 Reset complet de l'environnement

```bash
# Arrêter tout et supprimer les volumes (ATTENTION : perte de données)
docker compose down -v

# Supprimer les images du projet
docker images | grep vite | awk '{print $3}' | xargs docker rmi -f

# Reconstruire de zéro
docker compose build --no-cache
docker compose up -d

# Réinitialiser les bases de données
docker exec -i vite-mysql mysql -u root -p<PASSWORD> vite_gourmand \
  < backend/database/sql/database_complete.sql
```

### 10.3 Vérification de la connectivité

```bash
# Tester MySQL depuis le conteneur PHP
docker exec vite-php-app php -r "
  \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=vite_gourmand', 'vite_user', '<password>');
  echo 'MySQL OK';
"

# Tester MongoDB depuis le conteneur PHP  
docker exec vite-php-app php -r "
  \$client = new MongoDB\Client('mongodb://vite_user:<password>@mongodb:27017/vite_gourmand?authSource=admin');
  \$db = \$client->selectDatabase('vite_gourmand');
  echo 'MongoDB OK';
"

# Tester l'API
curl -s http://localhost:8000/api/menus | head -c 200
```

---

*Document généré le 18 février 2026 — Reflète l'infrastructure en production.*
