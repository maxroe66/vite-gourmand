# Configuration MongoDB Azure (Cosmos DB)

## 📋 Étapes de configuration

### 1. Instance Cosmos DB créée
```bash
Nom: vite-gourmand-mongodb
Type: MongoDB API 4.0
Région: France Central
Resource Group: rg-vite-gourmand
```

### 2. Récupérer la chaîne de connexion

Une fois la création terminée (5-10 minutes), exécutez :

```bash
az cosmosdb keys list \
  --name vite-gourmand-mongodb \
  --resource-group rg-vite-gourmand \
  --type connection-strings \
  --query "connectionStrings[0].connectionString" -o tsv
```

### 3. Créer la base de données MongoDB

```bash
# Se connecter à Cosmos DB via MongoDB shell
mongo "mongodb://vite-gourmand-mongodb.mongo.cosmos.azure.com:10255/?ssl=true" \
  --username vite-gourmand-mongodb \
  --password <PRIMARY_PASSWORD>

# Puis dans le shell MongoDB :
use vite_gourmand_prod
```

### 4. Initialiser les collections

Exécuter le script `database_mongodb_setup_cosmosdb.js` (Script optimisé pour Cosmos DB avec index corrects) :

```bash
mongosh "mongodb://vite-gourmand-mongodb.mongo.cosmos.azure.com:10255/vite_gourmand_prod?ssl=true" \
  --username vite-gourmand-mongodb \
  --password <PRIMARY_PASSWORD> \
  --file backend/database/mongoDB/database_mongodb_setup_cosmosdb.js
```

### 5. Configuration dans Azure App Service

Ajouter les variables d'environnement :

```bash
az webapp config appsettings set \
  --name vite-gourmand-app \
  --resource-group rg-vite-gourmand \
  --settings \
    MONGO_HOST="vite-gourmand-mongodb.mongo.cosmos.azure.com" \
    MONGO_PORT="10255" \
    MONGO_DB="vite_gourmand_prod" \
    MONGO_USERNAME="vite-gourmand-mongodb" \
    MONGO_PASSWORD="<PRIMARY_PASSWORD>" \
    MONGO_URI="mongodb://vite-gourmand-mongodb:<PRIMARY_PASSWORD>@vite-gourmand-mongodb.mongo.cosmos.azure.com:10255/vite_gourmand_prod?ssl=true&retrywrites=false"
```

### 6. Vérification

```bash
# Vérifier que Cosmos DB est créé
az cosmosdb show \
  --name vite-gourmand-mongodb \
  --resource-group rg-vite-gourmand \
  --query "{Name:name, Status:provisioningState, Endpoint:documentEndpoint}"
```

## 🔐 Sécurité

- ✅ SSL/TLS activé par défaut
- ✅ Authentification obligatoire
- ⚠️ Ajouter votre IP aux règles de pare-feu si nécessaire

## 💰 Coûts

- Azure Cosmos DB (MongoDB API) : ~5-10€/mois avec Azure for Students
- Niveau gratuit : 400 RU/s et 5 GB de stockage

## 📊 Pour l'ECF

Votre application démontre l'utilisation de :
- ✅ **Base relationnelle** : MySQL Flexible Server
- ✅ **Base non-relationnelle** : Azure Cosmos DB (MongoDB API)
- ✅ **Architecture hybride** : Fallback MySQL pour les avis
- ✅ **Production ready** : Deux bases de données Azure

## Remarque TLS & domaine

Si votre application utilise un domaine personnalisé pour l'App Service, n'oubliez pas :

- Ajouter le `hostname` dans App Service (après propagation DNS) et créer un certificat managé via `az webapp config ssl create`.
- Activer `httpsOnly` sur l'App Service et s'assurer que `APP_ENV=production` dans les Application settings pour que la redirection HTTP→HTTPS et le HSTS côté application soient effectifs.

Exemples de commandes et vérifications sont disponibles dans `Docs/README.azure.md`.
