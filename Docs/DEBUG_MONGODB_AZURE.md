# 🔍 Guide de diagnostic MongoDB sur Azure

## Problème identifié

Les commandes sont créées dans MySQL mais ne sont pas synchronisées dans MongoDB sur Azure, alors que cela fonctionne en local.

## Causes possibles

### 1. **Erreur de connexion silencieuse**
MongoDB échoue à se connecter mais l'erreur est capturée par `try/catch` et seulement loguée dans `error_log`, donc invisible dans le navigateur.

### 2. **Configuration incorrecte pour Cosmos DB**
Azure Cosmos DB (API MongoDB) nécessite des paramètres spéciaux :
- `ssl=true` obligatoire
- `retrywrites=false` obligatoire pour Cosmos DB
- Port `10255` au lieu de `27017`

### 3. **Variables d'environnement manquantes**
Les variables `MONGO_*` ne sont peut-être pas correctement définies dans Azure App Service.

## Solutions mises en place

### ✅ 1. Logging détaillé ajouté

**Fichiers modifiés :**
- [backend/src/Services/CommandeService.php](backend/src/Services/CommandeService.php) - Méthode `syncOrderToStatistics()` avec logs détaillés
- [backend/config/container.php](backend/config/container.php) - Test de connexion au démarrage

**Types d'erreurs loguées :**
- `AuthenticationException` → Problème de credentials
- `ConnectionTimeoutException` → Problème de réseau/firewall
- `ConnectionException` → Problème d'URI ou de configuration

### ✅ 2. Route de diagnostic créée

**URL de test :** `https://votre-site.azurewebsites.net/api/diagnostic/mongodb`

Cette route affiche :
- Variables d'environnement configurées
- URI MongoDB (masquée pour sécurité)
- Résultats des tests de connexion
- Nombre de documents dans la collection
- Logs MongoDB récents

## Procédure de diagnostic

### Étape 1 : Vérifier les variables d'environnement dans Azure

```bash
# Connexion à Azure
az login

# Lister les variables d'environnement
az webapp config appsettings list \
  --name vite-gourmand-app \
  --resource-group rg-vite-gourmand \
  --output table
```

**Variables requises :**
```bash
MONGO_URI=mongodb://vite-gourmand-mongodb:VOTRE_PASSWORD@vite-gourmand-mongodb.mongo.cosmos.azure.com:10255/vite_gourmand_prod?ssl=true&retrywrites=false
```

OU séparément :
```bash
MONGO_HOST=vite-gourmand-mongodb.mongo.cosmos.azure.com
MONGO_PORT=10255
MONGO_DB=vite_gourmand_prod
MONGO_USERNAME=vite-gourmand-mongodb
MONGO_PASSWORD=VOTRE_PASSWORD
```

### Étape 2 : Accéder à la route de diagnostic

1. Ouvrir dans le navigateur :
   ```
   https://vite-gourmand-app.azurewebsites.net/api/diagnostic/mongodb
   ```

2. Vérifier le résultat :
   - ✅ `client_created: true` → Client MongoDB créé
   - ✅ `list_databases: "SUCCESS"` → Connexion établie
   - ✅ `count_documents: X` → Collection accessible
   - ❌ Sinon, voir le message d'erreur détaillé

### Étape 3 : Consulter les logs Azure

```bash
# Logs en temps réel
az webapp log tail \
  --name vite-gourmand-app \
  --resource-group rg-vite-gourmand

# Ou depuis Azure Portal
# App Service → Monitoring → Log stream
```

**Filtrer les logs MongoDB :**
Rechercher les lignes contenant :
- `[MongoDB Init]`
- `[MongoDB Sync #XXX]`

### Étape 4 : Tester la création d'une commande

1. Créer une commande sur le site Azure
2. Consulter immédiatement les logs (Étape 3)
3. Rechercher les messages de la forme :
   ```
   [MongoDB Sync #123] Début de la synchronisation
   [MongoDB Sync #123] SUCCÈS - Matched: 0, Modified: 0, Upserted: OUI
   ```

## Messages d'erreur courants

### Erreur : "ERREUR AUTHENTIFICATION MongoDB"
**Cause :** Mauvais username/password  
**Solution :**
```bash
# Récupérer le bon password
az cosmosdb keys list \
  --name vite-gourmand-mongodb \
  --resource-group rg-vite-gourmand \
  --type keys \
  --query primaryMasterKey -o tsv

# Mettre à jour dans Azure
az webapp config appsettings set \
  --name vite-gourmand-app \
  --resource-group rg-vite-gourmand \
  --settings MONGO_PASSWORD="NOUVEAU_PASSWORD"
```

### Erreur : "ERREUR TIMEOUT MongoDB"
**Cause :** Problème réseau ou firewall  
**Solution :**
1. Vérifier que Cosmos DB autorise les connexions depuis Azure App Service
2. Dans Azure Portal → Cosmos DB → Firewall → Autoriser "Allow access from Azure services"

### Erreur : "mongoDBClient est NULL"
**Cause :** Le client n'a pas pu être instancié  
**Solution :**
1. Vérifier que `MONGO_URI` est défini
2. Vérifier le format de l'URI (voir ci-dessus)

### Erreur : "Commande non trouvée dans MySQL"
**Cause :** La commande a été supprimée avant la sync  
**Solution :** Normal si suppression, sinon vérifier l'ID de la commande

## Vérification manuelle de MongoDB

### Via MongoDB Shell (Mongo Compass)

```bash
# Connexion
mongo "mongodb://vite-gourmand-mongodb.mongo.cosmos.azure.com:10255/?ssl=true&retrywrites=false" \
  --username vite-gourmand-mongodb \
  --password VOTRE_PASSWORD

# Lister les bases
show dbs

# Sélectionner la base
use vite_gourmand_prod

# Compter les documents
db.statistiques_commandes.countDocuments()

# Afficher les commandes
db.statistiques_commandes.find().pretty()

# Vérifier la dernière commande
db.statistiques_commandes.find().sort({updatedAt: -1}).limit(1).pretty()
```

## Configuration correcte pour Cosmos DB

### ⚠️ IMPORTANT : Paramètres spécifiques Cosmos DB

**URI complète (recommandée) :**
```
mongodb://USERNAME:PASSWORD@HOST:10255/DATABASE?ssl=true&retrywrites=false&retryReads=false
```

**Paramètres obligatoires :**
- `ssl=true` → TLS obligatoire pour Cosmos DB
- `retrywrites=false` → Cosmos DB ne supporte pas les retries automatiques
- `retryReads=false` → (Optionnel) Désactive les retries en lecture

### Dans backend/config/config.php

Vérifier que l'URI est construite correctement :

```php
if ($mongoUser && $mongoPass) {
    $mongoUri = "mongodb://{$mongoUser}:{$mongoPass}@{$mongoHost}:{$mongoPort}/{$mongoDb}?ssl=true&retrywrites=false";
}
```

## Checklist de vérification

- [ ] Variables d'environnement définies dans Azure (`MONGO_URI` ou `MONGO_HOST`, etc.)
- [ ] URI contient `ssl=true&retrywrites=false`
- [ ] Port est `10255` (Cosmos DB) et non `27017` (MongoDB standard)
- [ ] Firewall Cosmos DB autorise Azure App Service
- [ ] Collection `statistiques_commandes` existe dans la base
- [ ] Route de diagnostic accessible et retourne des infos
- [ ] Logs montrent `[MongoDB Init] Connexion réussie !`
- [ ] Après création de commande : `[MongoDB Sync #X] SUCCÈS`

## Test de connexion rapide (CLI)

```bash
# Depuis votre machine locale
curl https://vite-gourmand-app.azurewebsites.net/api/diagnostic/mongodb | jq

# Chercher dans la réponse :
# - "client_created": true
# - "list_databases": "SUCCESS"
# - "count_documents": nombre > 0
```

## Prochaines étapes

1. **Déployer les modifications** (logs ajoutés)
2. **Consulter la route de diagnostic**
3. **Vérifier les logs Azure en temps réel**
4. **Créer une commande de test**
5. **Confirmer la sync dans les logs**
6. **Vérifier dans MongoDB que la commande apparaît**

---

**Note :** Une fois le problème résolu, vous pouvez désactiver les logs verbeux en les commentant ou en les mettant sous condition `APP_DEBUG`.
