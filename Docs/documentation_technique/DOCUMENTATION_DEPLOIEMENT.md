# 🚀 Documentation Déploiement - Vite & Gourmand

**Date :** 11 décembre 2025  
**Version :** 1.0.0  
**Auteur :** FastDev Team  
**Statut :** Production-Ready Template

---

## 📋 Table des Matières

1. [Architecture Déploiement](#architecture-déploiement)
2. [Installation Locale](#installation-locale)
3. [Déploiement Docker](#déploiement-docker)
4. [Configuration Production](#configuration-production)
5. [Migrations Base de Données](#migrations-base-de-données)
6. [Variables d'Environnement](#variables-denvironnement)
7. [Monitoring & Logs](#monitoring--logs)
8. [Troubleshooting](#troubleshooting)

---

## 🏗️ Architecture Déploiement

### Environnements

```
Development (LOCAL)
├─ PHP CLI 8.0+
├─ MySQL 8.0 (local)
├─ MongoDB (optionnel)
└─ Navigateur local

Staging (TEST)
├─ Docker Compose
├─ Services: PHP-Apache, MySQL, MongoDB
├─ Volume persistant pour DB
└─ HTTPS (Let's Encrypt)

Production (LIVE)
├─ Cloud (AWS, Azure, Digital Ocean, OVH)
├─ Kubernetes (optionnel scalabilité)
├─ MySQL 8.0 managed
├─ MongoDB managed
├─ CDN (images, assets)
├─ Load balancer
└─ HTTPS (Let's Encrypt auto-renew)
```

### Stack Conteneurisation

```yaml
Services Docker:
  ├─ PHP-FPM 8.0 (FastCGI)
  ├─ Apache 2.4 (Web server)
  ├─ MySQL 8.0 (Database)
  └─ MongoDB 4.4 (Analytics)

Volumes Persistants:
  ├─ /var/lib/mysql (DB data)
  ├─ /var/lib/mongodb (NoSQL data)
  └─ /var/www/vite_gourmand (Code)

Networks:
  └─ internal (services communiquent)
```

---

## 💻 Installation Locale

### Prérequis

```bash
# Linux/Mac
- PHP 8.0+
- MySQL 8.0+
- MongoDB 4.4+ (optionnel)
- Composer
- Git
- Apache/Nginx (optionnel, PHP built-in suffit)

# Windows
- Même + WSL2 recommandé
- Ou XAMPP/Laragon (intègre PHP, MySQL)
```

### Étape 1 : Cloner Dépôt

```bash
# Clone
git clone https://github.com/votre-org/vite-et-gourmand.git
cd vite-et-gourmand

# Checkout develop branch (dev, pas main)
git checkout develop
```

### Étape 2 : Installer Dépendances

```bash
# Installer composer (si pas installé)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Installer dépendances PHP
composer install --dev

# Vérifier installation
php -v  # ≥ 8.0
composer --version
```

### Étape 3 : Copier .env

```bash
# Template
cp .env.example .env

# Éditer variables locales
nano .env

# Contenu minimal pour LOCAL:
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_HOST=localhost
DB_PORT=3306
DB_NAME=vite_gourmand_dev
DB_USER=root
DB_PASSWORD=root

MONGO_HOST=localhost
MONGO_PORT=27017
MONGO_DB=vite_gourmand_dev

JWT_SECRET=dev-secret-key-change-in-production

GOOGLE_MAPS_API_KEY=xxxxx  (optionnel pour dev)
```

### Étape 4 : Créer Base de Données

```bash
# MySQL
# Option A : Via command line
mysql -u root -proot < sql/database_creation.sql
mysql -u root -proot vite_gourmand_dev < sql/database_fixtures.sql

# Option B : Via GUI (MySQL Workbench)
# - File → Open SQL Script → database_creation.sql → Execute
# - File → Open SQL Script → database_fixtures.sql → Execute
```

### Étape 5 : Démarrer Serveur Local

```bash
# Option A : PHP Built-in (simple)
php -S localhost:8000

# Option B : Apache local
sudo systemctl start apache2
# Configurer vhost /etc/apache2/sites-available/vite.conf
# DocumentRoot /chemin/vite-et-gourmand/public
# a2ensite vite.conf
# sudo systemctl restart apache2

# Accéder
open http://localhost:8000
```

### Étape 6 : Vérifier Installation

```bash
# Vérifier PHP
php -r "echo 'PHP ' . PHP_VERSION . ' OK';"

# Vérifier MySQL
mysql -u root -proot -e "SELECT 1"

# Vérifier extensions PHP
php -m | grep -E "pdo|pdo_mysql|curl|json"

# Tests quick
curl http://localhost:8000/api/health
# Should return 200 OK
```

---

## 🐳 Déploiement Docker

### Files Docker

#### `docker-compose.yml`

```yaml
version: '3.9'

services:
  # PHP-FPM Service
  php-app:
    build:
      context: .
      dockerfile: Dockerfile.php
    container_name: vite-php-app
    working_dir: /var/www/vite_gourmand
    volumes:
      - .:/var/www/vite_gourmand
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    environment:
      - APP_ENV=development
      - APP_DEBUG=true
      - DB_HOST=mysql
      - MONGO_HOST=mongodb
    depends_on:
      - mysql
      - mongodb
    networks:
      - vite-network
    restart: unless-stopped

  # Apache Web Server
  apache:
    build:
      context: .
      dockerfile: Dockerfile.apache
    container_name: vite-apache
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/vite_gourmand
      - ./docker/apache/vite.conf:/etc/apache2/sites-available/vite.conf
    depends_on:
      - php-app
    networks:
      - vite-network
    restart: unless-stopped

  # MySQL Database
  mysql:
    image: mysql:8.0
    container_name: vite-mysql
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: vite_gourmand
      MYSQL_USER: vite_user
      MYSQL_PASSWORD: vite_pass
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./sql/database_creation.sql:/docker-entrypoint-initdb.d/01-schema.sql
      - ./sql/database_fixtures.sql:/docker-entrypoint-initdb.d/02-fixtures.sql
    networks:
      - vite-network
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10

  # MongoDB Database
  mongodb:
    image: mongo:4.4
    container_name: vite-mongodb
    environment:
      MONGO_INITDB_DATABASE: vite_gourmand
    ports:
      - "27017:27017"
    volumes:
      - mongodb_data:/data/db
      - ./mongoDB/database_mongodb_setup.js:/docker-entrypoint-initdb.d/setup.js
    networks:
      - vite-network
    restart: unless-stopped
    healthcheck:
      test: echo 'db.runCommand("ping").ok' | mongosh localhost:27017/test --quiet
      interval: 10s
      timeout: 5s
      retries: 5

  # phpMyAdmin (optionnel, dev only)
  phpmyadmin:
    image: phpmyadmin:latest
    container_name: vite-phpmyadmin
    environment:
      PMA_HOST: mysql
      PMA_USER: root
      PMA_PASSWORD: root
    ports:
      - "8081:80"
    depends_on:
      - mysql
    networks:
      - vite-network
    restart: unless-stopped

  # Mongo Express (optionnel, dev only)
  mongo-express:
    image: mongo-express:latest
    container_name: vite-mongo-express
    environment:
      ME_CONFIG_MONGODB_ADMINUSERNAME: root
      ME_CONFIG_MONGODB_ADMINPASSWORD: root
      ME_CONFIG_MONGODB_URL: mongodb://mongodb:27017
    ports:
      - "8082:8081"
    depends_on:
      - mongodb
    networks:
      - vite-network
    restart: unless-stopped

volumes:
  mysql_data:
    driver: local
  mongodb_data:
    driver: local

networks:
  vite-network:
    driver: bridge
```

#### `Dockerfile.php`

```dockerfile
FROM php:8.0-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    wget \
    git \
    unzip \
    libpq-dev \
    libmcrypt-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    curl \
    json

# Install MongoDB driver
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/vite_gourmand

# Copy app files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create logs directory
RUN mkdir -p logs && chmod -R 755 logs

# Expose port (FPM listens on 9000)
EXPOSE 9000

CMD ["php-fpm"]
```

#### `Dockerfile.apache`

```dockerfile
FROM apache:2.4

# Enable required modules
RUN a2enmod rewrite \
    && a2enmod proxy \
    && a2enmod proxy_fcgi

# Install PHP CLI (pour scripts)
RUN apt-get update && apt-get install -y php-cli && rm -rf /var/lib/apt/lists/*

# Copy Apache config
COPY docker/apache/vite.conf /etc/apache2/sites-available/vite.conf

# Enable site
RUN a2ensite vite.conf && a2dissite 000-default.conf

# Copy app files
COPY . /var/www/vite_gourmand

# Set permissions
RUN chown -R www-data:www-data /var/www/vite_gourmand

EXPOSE 80
```

#### `docker/apache/vite.conf`

```apache
<VirtualHost *:80>
    ServerName vite.local
    ServerAlias localhost
    DocumentRoot /var/www/vite_gourmand/public

    <Directory /var/www/vite_gourmand/public>
        AllowOverride All
        Require all granted
        
        # URL rewriting
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
    </Directory>

    # PHP-FPM proxy
    <FilesMatch \.php$>
        SetHandler "proxy:fcgi://php-app:9000"
    </FilesMatch>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/vite-error.log
    CustomLog ${APACHE_LOG_DIR}/vite-access.log combined
</VirtualHost>
```

#### `docker/php/php.ini`

```ini
[PHP]
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M

[mail]
; SMTP config for local dev (optionnel)
```

### Lancer Docker

```bash
# Build images
docker-compose build

# Démarrer services
docker-compose up -d

# Vérifier
docker-compose ps
# Tous les containers doivent être "healthy"

# Vérifier logs
docker-compose logs -f

# Accéder
open http://localhost:8000
open http://localhost:8081  # phpMyAdmin
open http://localhost:8082  # Mongo Express

# Arrêter
docker-compose down

# Arrêter + supprimer volumes (reset complètement)
docker-compose down -v
```

---

## ⚙️ Configuration Production

### Variables d'Environnement Production

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vitegourmand.fr
FORCE_HTTPS=true

# Database Production (managed service)
DB_HOST=mysql.prod.rds.amazonaws.com  # AWS RDS ou similar
DB_PORT=3306
DB_NAME=vite_gourmand_prod
DB_USER=vite_prod_user
DB_PASSWORD=<very_strong_password>

# MongoDB Production
MONGO_HOST=mongodb.prod.aws.com
MONGO_PORT=27017
MONGO_DB=vite_gourmand_prod
MONGO_USER=vite_mongo
MONGO_PASSWORD=<very_strong_password>

# API Géolocalisation
GOOGLE_MAPS_API_KEY=<your_production_key>
GEOLOCATION_API_TIMEOUT=10000

# Email
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com  # ou service professionnel
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=noreply@vitegourmand.fr
MAIL_PASSWORD=<app_password>
MAIL_FROM_ADDRESS=noreply@vitegourmand.fr
MAIL_FROM_NAME="Vite & Gourmand"

# JWT Security (générer nouveau token)
JWT_SECRET=<generate_new_long_random_string>
JWT_EXPIRATION=86400

# Cache
CACHE_DRIVER=redis  # optionnel mais recommandé
REDIS_HOST=redis.prod.aws.com
REDIS_PASSWORD=<password>
REDIS_PORT=6379

# Session
SESSION_DRIVER=cookie  # ou redis
SESSION_LIFETIME=120

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error  # Plus restrictif en prod

# Security
TRUSTED_PROXIES=*  # Pour load balancer
TRUSTED_HOSTS=vitegourmand.fr,www.vitegourmand.fr
```

### Configuration Serveur Web (Nginx)

```nginx
# /etc/nginx/sites-available/vitegourmand

upstream php_backend {
    server php-app:9000;
}

server {
    listen 80;
    server_name vitegourmand.fr www.vitegourmand.fr;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name vitegourmand.fr www.vitegourmand.fr;
    
    # SSL Certificates (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/vitegourmand.fr/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/vitegourmand.fr/privkey.pem;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    # Root & index
    root /var/www/vite_gourmand/public;
    index index.php;
    
    # Logs
    access_log /var/log/nginx/vite-access.log;
    error_log /var/log/nginx/vite-error.log;
    
    # Location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP
    location ~ \.php$ {
        fastcgi_pass php_backend;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Static assets (cache 1 year)
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
    }
    
    # Deny access to files
    location ~ /\. {
        deny all;
    }
    location ~ /^(\.|composer) {
        deny all;
    }
}
```

### SSL Let's Encrypt Auto-Renewal

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx -y

# Get certificate
sudo certbot certonly --nginx -d vitegourmand.fr -d www.vitegourmand.fr

# Auto-renewal (runs twice daily)
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

# Test renewal
sudo certbot renew --dry-run
```

---

## 🗄️ Migrations Base de Données

### Versioning SQL

```
sql/
├─ database_creation.sql      (v1.0 - schema initial)
├─ migrations/
│  ├─ 001_create_tables.sql
│  ├─ 002_add_indexes.sql
│  ├─ 003_add_avis_fallback.sql
│  └─ 004_add_triggers.sql
```

### Process Migration

```bash
# Development
1. Créer fichier migration: sql/migrations/005_new_feature.sql
2. Tester localement: mysql vite_gourmand < sql/migrations/005_new_feature.sql
3. Commit + push

# Staging
1. Backup DB: mysqldump vite_gourmand > backup_prod_$(date +%s).sql
2. Exécuter: mysql vite_gourmand < sql/migrations/005_new_feature.sql
3. Test new feature

# Production
1. Backup DB: mysqldump vite_gourmand_prod > backup_prod_$(date +%s).sql
2. Scheduled downtime (maintenance window)
3. Exécuter migration
4. Vérifier
5. Rollback plan (restore from backup)
```

### Schema Update (Production Safe)

```sql
-- SAFE : Ajouter colonne (vs supprimer)
ALTER TABLE commandes ADD COLUMN new_field VARCHAR(100);

-- DANGEROUS : Supprimer (backup d'abord!)
ALTER TABLE commandes DROP COLUMN old_field;

-- SAFE : Ajouter index (vs supprimer)
ALTER TABLE commandes ADD INDEX idx_new (new_field);

-- Process migration d'ajustement de schéma
-- 1. Ajouter colonne vide
-- 2. Remplir données existantes
-- 3. Contrainte NOT NULL (après vérif)
```

---

## 🔐 Variables d'Environnement

### Template `.env.example`

```env
# ==================================================
# APPLICATION
# ==================================================
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
FORCE_HTTPS=false

# ==================================================
# DATABASE (MySQL)
# ==================================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=vite_gourmand
DB_USER=root
DB_PASSWORD=

# ==================================================
# DATABASE (MongoDB)
# ==================================================
MONGO_HOST=127.0.0.1
MONGO_PORT=27017
MONGO_DB=vite_gourmand
MONGO_USERNAME=
MONGO_PASSWORD=

# ==================================================
# API - GEOLOCATION
# ==================================================
GOOGLE_MAPS_API_KEY=
GEOLOCATION_API_TIMEOUT=5000

# ==================================================
# EMAIL
# ==================================================
MAIL_DRIVER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@vitegourmand.fr
MAIL_FROM_NAME="Vite & Gourmand"

# ==================================================
# JWT AUTHENTICATION
# ==================================================
JWT_SECRET=your-secret-key-change-in-production
JWT_EXPIRATION=86400

# ==================================================
# CACHE
# ==================================================
CACHE_DRIVER=array
CACHE_TTL=3600

# ==================================================
# SESSION
# ==================================================
SESSION_DRIVER=cookie
SESSION_LIFETIME=120

# ==================================================
# LOGGING
# ==================================================
LOG_CHANNEL=single
LOG_LEVEL=debug

# ==================================================
# SECURITY
# ==================================================
TRUSTED_PROXIES=
TRUSTED_HOSTS=localhost
```

### Managing Secrets

```bash
# NEVER commit .env file!
echo ".env" >> .gitignore
git rm --cached .env

# Use .env.example for team
cp .env.example .env  # Each dev generates own

# Production: Pass via Docker/Kubernetes secrets
# Option 1: Docker secrets
docker secret create jwt_secret <(echo "long-secret-here")

# Option 2: Kubernetes secrets
kubectl create secret generic vite-secrets \
  --from-literal=JWT_SECRET=xxx \
  --from-literal=DB_PASSWORD=xxx

# Option 3: Cloud provider (AWS Secrets Manager, Azure KeyVault)
```

---

## 📊 Monitoring & Logs

### Log Files

```
logs/
├─ app.log              (application logs)
├─ error.log            (PHP errors)
├─ slow-queries.log     (MySQL queries > 2s)
└─ access.log           (HTTP requests)
```

### Monitoring Stack

```yaml
# ELK Stack (Elasticsearch, Logstash, Kibana)
- Filebeat → read logs
- Logstash → parse & enrich
- Elasticsearch → store & index
- Kibana → visualize & analyze
```

### Healthcheck Endpoint

```php
// GET /api/health
class HealthController {
    public function check() {
        $health = [
            'status' => 'ok',
            'timestamp' => now(),
            'mysql' => $this->checkMySQL(),
            'mongodb' => $this->checkMongoDB(),
            'api_geoloc' => $this->checkApiGeolocation(),
        ];
        return response()->json($health);
    }
}

// Monitoring
curl https://vitegourmand.fr/api/health
# Expected: {"status":"ok", "mysql":true, "mongodb":true, "api_geoloc":true}
```

---

## 🔧 Troubleshooting

### Problèmes Courants

| Problème | Cause | Solution |
|----------|-------|----------|
| `Connection refused 3306` | MySQL not running | `docker-compose restart mysql` |
| `SQLSTATE[HY000]` | Bad credentials | Vérifier DB_USER, DB_PASSWORD en .env |
| `JWT token expired` | Token vieux | User doit se reconnecter |
| `Google Maps API rate limit` | Trop appels | Augmenter quota, implémenter cache |
| `MongoDB connection timeout` | MongoDB down | Fallback AVIS_FALLBACK activé? |
| `PHP Out of memory` | Script heavy | Augmenter memory_limit en php.ini |
| `CORS error` | Frontend ≠ Backend domain | Ajouter CORS headers |

### Debug Commands

```bash
# Docker
docker-compose ps
docker-compose logs -f php-app
docker-compose logs -f mysql

# MySQL
docker-compose exec mysql mysql -u root -proot -e "SHOW DATABASES;"
docker-compose exec mysql mysql -u root -proot vite_gourmand -e "SHOW TABLES;"

# MongoDB
docker-compose exec mongodb mongosh
> show databases
> use vite_gourmand
> db.statistiques_commandes.find().limit(1)

# PHP
docker-compose exec php-app php -v
docker-compose exec php-app composer show

# Network
docker-compose exec php-app ping mysql
docker-compose exec php-app curl -I http://apache:80
```

### Performance Tuning

```bash
# MySQL slow query log
# /etc/mysql/my.cnf
[mysqld]
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# View slow queries
docker-compose exec mysql tail -f /var/log/mysql/slow.log
```

---

## ✅ Checklist Déploiement

**Avant Production :**

- [ ] `.env.example` commité, `.env` ignoré
- [ ] JWT_SECRET changé (nouveau token fort)
- [ ] GOOGLE_MAPS_API_KEY valide
- [ ] Email config fonctionnel
- [ ] HTTPS activé + SSL certificate
- [ ] Database backups automatisés
- [ ] Logs centralisés (ELK ou similaire)
- [ ] Monitoring alerts configurés
- [ ] Firewall rules restrictives
- [ ] Admin user créé (pas default)
- [ ] Rate limiting API activé
- [ ] Cache Redis configuré
- [ ] CDN pour assets (optionnel)

---

## 📞 Support

**Documentation Complète :** `README.md`  
**Architecture Technique :** `DOCUMENTATION_TECHNIQUE.md`  
**Questions Docker ?** https://docs.docker.com/  
**Questions MySQL ?** https://dev.mysql.com/doc/  

---

**Status :** ✅ Production-Ready  
**Last Updated :** 11 décembre 2025

