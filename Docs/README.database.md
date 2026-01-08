# 🗄️ Guide des Bases de Données - Vite & Gourmand

**📖 Point d'entrée principal** : Ce document fournit une **vue d'ensemble complète** de toutes les bases de données (MySQL + MongoDB) sur tous les environnements (dev/test/prod).

## 📚 Documentation complémentaire

Pour des guides détaillés et spécifiques, consultez :

| Document | Rôle | Quand l'utiliser ? |
|----------|------|-------------------|
| **README.database.md** (ce fichier) | Vue d'ensemble globale | Comprendre l'architecture, trouver les commandes de base |
| [README.azure.md](README.azure.md) | Tutorial MySQL Azure | Se connecter à MySQL prod, commandes SQL avancées, dépannage |
| [MONGODB_AZURE_SETUP.md](MONGODB_AZURE_SETUP.md) | Procédure MongoDB Azure | Setup initial Cosmos DB, initialisation collections |
| [AZURE_CONFIG_CHECKLIST.md](AZURE_CONFIG_CHECKLIST.md) | Checklist déploiement | Vérifier variables d'environnement avant mise en prod |

---

Ce document explique comment sont gérées, configurées et utilisées les différentes bases de données du projet.

---

## 📊 Vue d'ensemble

Le projet utilise **3 environnements** de bases de données distincts :

| Environnement | MySQL | MongoDB | Localisation | Fichier config |
|---------------|-------|---------|--------------|----------------|
| **Développement** | `vite_gourmand` (port 3306) | `vite_gourmand` (port 27017) | Docker local | `.env` |
| **Test** | `vite_gourmand_test` (port 3307) | `vite_gourmand_test` (port 27018) | Docker local | `.env.test` |
| **Production (Azure)** | `vite_et_gourmand` | `vite_gourmand_prod` (Azure Cosmos DB) | Azure Cloud | Variables Azure |

---

## 🔧 1. Base de données de DÉVELOPPEMENT

### Configuration

**Fichier** : `.env` (racine du projet)

```ini
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=vite_gourmand
DB_USER=root
DB_PASS=root_password_dev

MONGO_HOST=127.0.0.1
MONGO_PORT=27017
MONGO_DB=vite_gourmand
MONGO_USERNAME=root
MONGO_PASSWORD=mongo_root_password_dev
```

### Démarrage avec Docker

```bash
# Démarrer tous les conteneurs (MySQL, MongoDB, phpMyAdmin, etc.)
./docker-up.sh

# Ou manuellement
docker-compose --env-file .env.compose up -d
```

### Accès MySQL Dev

**Via terminal** :
```bash
mysql -h 127.0.0.1 -P 3306 -u root -proot_password_dev vite_gourmand
```

**Via phpMyAdmin** :
- URL : http://localhost:8081
- Serveur : `mysql`
- User : `root`
- Password : `root_password_dev`

**Via VS Code (extension MySQL)** :
- Host : `127.0.0.1`
- Port : `3306`
- User : `root`
- Password : `root_password_dev`
- Database : `vite_gourmand`

### Accès MongoDB Dev

**Via terminal** :
```bash
mongosh mongodb://root:mongo_root_password_dev@127.0.0.1:27017/vite_gourmand?authSource=admin
```

**Via Mongo Express** :
- URL : http://localhost:8082
- User : `root`
- Password : `mongo_root_password_dev`

### Fichiers SQL

**Schéma** : [backend/database/sql/database_creation.sql](backend/database/sql/database_creation.sql)
- Création de toutes les tables
- Définition des contraintes et index
- Vues SQL

**Données de test** : [backend/database/sql/database_fixtures.sql](backend/database/sql/database_fixtures.sql)
- 7 utilisateurs (admin, employé, 5 clients)
- 6 menus complets avec plats
- 7 commandes avec différents statuts
- 3 avis clients
- Matériel et horaires

### Réinitialisation

```bash
# Supprimer et recréer la base dev
docker exec vite-mysql mysql -uroot -proot_password_dev -e "DROP DATABASE IF EXISTS vite_gourmand; CREATE DATABASE vite_gourmand;"

# Réappliquer le schéma
docker exec -i vite-mysql mysql -uroot -proot_password_dev vite_gourmand < backend/database/sql/database_creation.sql

# Réinsérer les données
docker exec -i vite-mysql mysql -uroot -proot_password_dev vite_gourmand < backend/database/sql/database_fixtures.sql
```

---

## 🧪 2. Base de données de TEST

### Configuration

**Fichier** : `.env.test` (racine du projet)

```ini
DB_HOST=127.0.0.1
DB_PORT=3307
DB_NAME=vite_gourmand_test
DB_USER=root
DB_PASS=root_password_test

MONGO_HOST=127.0.0.1
MONGO_PORT=27018
MONGO_DB=vite_gourmand_test
MONGO_USERNAME=root
MONGO_PASSWORD=mongo_root_password_test
```

### Utilisation

Les tests PHPUnit utilisent **automatiquement** cette base :

```bash
cd backend
php vendor/bin/phpunit
```

Le fichier [backend/phpunit.xml](backend/phpunit.xml) force `APP_ENV=test`, ce qui charge `.env.test`.

### Accès MySQL Test

```bash
mysql -h 127.0.0.1 -P 3307 -u root -proot_password_test vite_gourmand_test
```

### Réinitialisation base de test

```bash
# Script automatique pour les tests
./scripts/tests/reset_test_db.sh

# Ou manuellement
docker exec vite-mysql-test mysql -uroot -proot_password_test -e "DROP DATABASE IF EXISTS vite_gourmand_test; CREATE DATABASE vite_gourmand_test;"
docker exec -i vite-mysql-test mysql -uroot -proot_password_test vite_gourmand_test < backend/database/sql/database_creation.sql
```

### Isolation

⚠️ **Important** : La base de test est **complètement séparée** de la base dev :
- Port différent (3307 vs 3306)
- Conteneur Docker distinct (`vite-mysql-test`)
- Volume Docker séparé (`mysql_test_data`)

Vous pouvez **détruire et recréer** la base de test sans impact sur le développement.

---

## ☁️ 3. Base de données AZURE (Production)

### Configuration

**Variables d'environnement Azure** (App Service → Configuration → Application settings)

```ini
DB_HOST=vite-gourmand-mysql-dev.mysql.database.azure.com
DB_PORT=3306
DB_NAME=vite_et_gourmand
DB_USER=vgadmin
DB_PASS=Cordelia1
DB_SSL=1
JWT_SECRET=<votre-secret-64-caracteres>
```

**Fichier de référence** : `.env.azure` (non utilisé par l'application, juste pour documentation)

### Accès depuis votre machine

```bash
# Depuis WSL/Linux
mysql -h vite-gourmand-mysql-dev.mysql.database.azure.com \
      -u vgadmin -p \
      --ssl-mode=REQUIRED \
      vite_et_gourmand

# Le mot de passe sera demandé
```

⚠️ **Attention** : Votre IP doit être autorisée dans le pare-feu Azure MySQL !

**Vérifier/Ajouter votre IP** :
1. Azure Portal → MySQL Flexible Server → Networking
2. Firewall rules → Add current client IP address
3. Save

### Migrations automatiques

Le workflow [.github/workflows/deploy-azure.yml](../.github/workflows/deploy-azure.yml) exécute automatiquement :

```yaml
- name: Run MySQL migrations on Azure (schema)
  run: |
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --ssl-mode=REQUIRED \
      "$DB_NAME" < backend/database/sql/database_creation.sql

- name: Seed minimal (admin / données de référence)
  run: |
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --ssl-mode=REQUIRED \
      "$DB_NAME" < backend/database/sql/database_fixtures.sql
```

Ces scripts sont **idempotents** (grâce à `INSERT IGNORE`, `CREATE TABLE IF NOT EXISTS`) et peuvent être exécutés plusieurs fois.

### Gestion manuelle MySQL

**Via Azure CLI** :
```bash
# Se connecter
az mysql flexible-server connect \
  --name vite-gourmand-mysql-dev \
  --admin-user vgadmin \
  --interactive

# Voir les logs
az mysql flexible-server server-logs list \
  --resource-group <votre-rg> \
  --name vite-gourmand-mysql-dev
```

**Via Azure Data Studio** ou **MySQL Workbench** :
- Host : `vite-gourmand-mysql-dev.mysql.database.azure.com`
- Port : `3306`
- User : `vgadmin`
- SSL : Required

### MongoDB Azure (Cosmos DB)

**Configuration** :
- Nom : `vite-gourmand-mongodb`
- Type : Cosmos DB avec API MongoDB 4.0
- Endpoint : `vite-gourmand-mongodb.mongo.cosmos.azure.com:10255`
- Base de données : `vite_gourmand_prod`

**Accès depuis votre machine** :
```bash
# Avec mongo shell (dans un conteneur Docker)
docker run --rm -it mongo:4.4 \
  mongo "mongodb://vite-gourmand-mongodb:<PRIMARY_PASSWORD>@vite-gourmand-mongodb.mongo.cosmos.azure.com:10255/?ssl=true&retrywrites=false"

# Remplacer <PRIMARY_PASSWORD> par la clé récupérée avec :
# az cosmosdb keys list --name vite-gourmand-mongodb --resource-group rg-vite-gourmand --type keys --query "primaryMasterKey" -o tsv
```

**Collections créées** :
- `avis` : Avis clients avec validation JSON Schema (vide initialement)
- `statistiques_commandes` : Statistiques par menu et période (vide initialement)
- `avis_page_accueil` : Vue agrégée des 10 derniers avis validés
- `statistiques_menus_globales` : Vue agrégée des stats globales

**Important** : Les collections sont **vides** en production (pas de données de test). Les données sont créées uniquement par les vrais utilisateurs.

**Initialisation** : Voir [MONGODB_AZURE_SETUP.md](MONGODB_AZURE_SETUP.md) pour les détails complets.

### Sauvegardes

Azure MySQL Flexible Server effectue des **sauvegardes automatiques** :
- Rétention : 7 jours par défaut (configurable jusqu'à 35 jours)
- Restauration point-in-time disponible

Pour restaurer :
```bash
az mysql flexible-server restore \
  --resource-group <rg> \
  --name <nouveau-serveur> \
  --source-server vite-gourmand-mysql-dev \
  --restore-time "2026-01-04T14:00:00Z"
```

---

## 📁 Structure des fichiers de base de données

```
backend/database/
├── sql/
│   ├── database_creation.sql         # Schéma complet (tables, vues, index)
│   └── database_fixtures.sql         # Données de test (utilisateurs, menus, commandes)
└── mongoDB/
    ├── database_mongodb_setup.js       # Config MongoDB DEV/TEST (avec données)
    └── database_mongodb_setup_azure.js # Config MongoDB AZURE (structure uniquement)
```

---

## 🔐 Sécurité et bonnes pratiques

### Mots de passe

| Environnement | Niveau de sécurité | Où ? |
|---------------|-------------------|------|
| **Dev local** | Faible (OK pour dev) | `.env` (gitignored) |
| **Test local** | Faible (OK pour test) | `.env.test` (commité car non sensible) |
| **Azure prod** | **Fort requis** | Variables Azure (chiffrées) |

### Règles

✅ **À FAIRE** :
- Utiliser des mots de passe forts en production
- Ne jamais commiter `.env` dans Git
- Activer SSL/TLS sur Azure (`DB_SSL=1`)
- Limiter les accès IP (pare-feu Azure)
- Rotations régulières des mots de passe Azure

❌ **À ÉVITER** :
- Réutiliser les mots de passe entre environnements
- Utiliser des mots de passe en dur dans le code
- Désactiver SSL en production
- Ouvrir le pare-feu Azure à `0.0.0.0/0`

---

## 🚀 Scripts utiles

### Docker

```bash
# Démarrer
./docker-up.sh

# Arrêter
./docker-down.sh

# Recréer complètement (efface les données !)
docker-compose --env-file .env.compose down -v
./docker-up.sh
```

### Tests

```bash
# Lancer tous les tests
cd backend && php vendor/bin/phpunit

# Tester uniquement JWT
php vendor/bin/phpunit --filter AuthServiceTest

# Avec couverture
php vendor/bin/phpunit --coverage-html coverage/
```

### Migrations manuelles

```bash
# Dev local
mysql -h 127.0.0.1 -P 3306 -u root -proot_password_dev vite_gourmand < backend/database/sql/database_creation.sql

# Test local
mysql -h 127.0.0.1 -P 3307 -u root -proot_password_test vite_gourmand_test < backend/database/sql/database_creation.sql

# Azure (avec votre IP autorisée)
mysql -h vite-gourmand-mysql-dev.mysql.database.azure.com \
      -u vgadmin -p \
      --ssl-mode=REQUIRED \
      vite_et_gourmand < backend/database/sql/database_creation.sql
```

---

## 🐛 Dépannage

### Problème : "Access denied" sur MySQL local

```bash
# Vérifier que Docker tourne
docker ps | grep mysql

# Vérifier les logs
docker logs vite-mysql

# Recréer le conteneur
docker-compose --env-file .env.compose down -v
./docker-up.sh
```

### Problème : "Can't connect" sur Azure

```bash
# Vérifier votre IP publique
curl ifconfig.me

# Ajouter votre IP dans Azure Portal :
# MySQL Flexible Server → Networking → Firewall rules → Add current client IP
```

### Problème : Erreur 1062 (Duplicate entry)

Les fichiers SQL utilisent `INSERT IGNORE` pour être idempotents. Si vous avez cette erreur :
1. Vérifiez que vous utilisez bien la dernière version du fichier
2. Supprimez et recréez la base si nécessaire

### Problème : Tests échouent avec erreur de connexion

```bash
# Vérifier que le conteneur test tourne
docker ps | grep mysql-test

# Vérifier le fichier .env.test
cat .env.test | grep DB_

# Vérifier que phpunit.xml a les bonnes variables
cat backend/phpunit.xml | grep JWT_SECRET
```

---

## 📚 Fichiers techniques

### Configuration projet
- [docker-compose.yml](../docker-compose.yml) : Configuration des conteneurs Docker (MySQL, MongoDB, phpMyAdmin, Mongo Express)
- [backend/config/config.php](../backend/config/config.php) : Chargement dynamique des variables d'environnement

### Scripts base de données
- [backend/database/sql/database_creation.sql](../backend/database/sql/database_creation.sql) : Schéma MySQL complet
- [backend/database/sql/database_fixtures.sql](../backend/database/sql/database_fixtures.sql) : Données de test MySQL
- [backend/database/mongoDB/database_mongodb_setup.js](../backend/database/mongoDB/database_mongodb_setup.js) : Config MongoDB dev/test
- [backend/database/mongoDB/database_mongodb_setup_azure.js](../backend/database/mongoDB/database_mongodb_setup_azure.js) : Config MongoDB prod Azure

---

## ✅ Checklist de vérification

Avant de déployer en production, vérifiez :

- [ ] `.env` configuré pour dev local (gitignored)
- [ ] `.env.test` à jour avec les bons mots de passe test
- [ ] Variables Azure MySQL configurées (DB_*, JWT_SECRET, etc.)
- [ ] Variables Azure MongoDB configurées (MONGO_*, voir [AZURE_CONFIG_CHECKLIST.md](AZURE_CONFIG_CHECKLIST.md))
- [ ] Collections MongoDB Azure initialisées (structure uniquement)
- [ ] Pare-feu Azure autorise votre IP (si connexion manuelle)
- [ ] Pare-feu Azure autorise "Azure services" (pour App Service)
- [ ] SSL activé sur MySQL Azure (`DB_SSL=1`)
- [ ] JWT_SECRET unique et fort (64+ caractères hex)
- [ ] Tests passent localement (`php vendor/bin/phpunit`)
- [ ] Migrations testées sur Azure
- [ ] Sauvegardes Azure activées et testées

---

**Dernière mise à jour** : 4 janvier 2026
