# ✅ Checklist de configuration Azure App Service

## Variables d'environnement à configurer dans Azure Portal

**App Service → Configuration → Application settings**

### 🔐 Base de données MySQL
```
DB_HOST=vite-gourmand-mysql-dev.mysql.database.azure.com
DB_PORT=3306
DB_NAME=vite_et_gourmand
DB_USER=vgadmin
DB_PASS=Cordelia1
DB_SSL=1
DB_SSL_CA=/etc/ssl/azure/DigiCertGlobalRootCA.crt.pem
```

### 🔑 JWT Secret
```
JWT_SECRET=<générer une nouvelle clé aléatoire avec: openssl rand -hex 32>
```

### 📧 Email (optionnel pour le moment)
```
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=<votre-email>
MAIL_PASSWORD=<votre-mot-de-passe-application>
MAIL_FROM_ADDRESS=noreply@vite-gourmand.com
```

### 🗄️ MongoDB (optionnel)
```
MONGO_URI=<votre-connection-string-mongodb-atlas-ou-azure>
MONGO_DB=vite_gourmand
```

### 🐛 Debug
```
APP_ENV=production
APP_DEBUG=false
```

---

## 🔍 Vérifications importantes

1. **Pare-feu MySQL** : Vérifier que l'IP d'Azure App Service est autorisée
   - Ou activer "Allow Azure services" dans le pare-feu MySQL

2. **SSL/TLS** : Le certificat DigiCert doit être présent dans le conteneur
   - Vérifié dans Dockerfile.azure ✅

3. **Ports** : App Service expose automatiquement le port 80 (Apache)

4. **Logs** : En cas d'erreur 503, vérifier les logs :
   ```bash
   az webapp log tail --name vite-gourmand-app-dev --resource-group <votre-rg>
   ```

---

## 🚀 Commandes utiles

### Redémarrer l'application
```bash
az webapp restart --name vite-gourmand-app-dev --resource-group <votre-rg>
```

### Voir les logs en temps réel
```bash
az webapp log tail --name vite-gourmand-app-dev --resource-group <votre-rg>
```

### Tester la connexion à la base de données
```bash
az mysql flexible-server connect \
  --name vite-gourmand-mysql-dev \
  --admin-user vgadmin \
  --interactive
```
