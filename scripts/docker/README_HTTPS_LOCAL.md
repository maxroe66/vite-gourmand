# HTTPS local avec Docker et mkcert — Vite & Gourmand

## 🚀 Objectif
Permettre à tous les développeurs d'utiliser HTTPS en local (https://localhost:8443) avec des certificats valides, sans casser la CI/CD ni les tests.

---

## 📦 Prérequis
- [mkcert](https://github.com/FiloSottile/mkcert) installé sur votre machine locale
  - Linux :
    ```bash
    sudo apt install libnss3-tools
    wget https://github.com/FiloSottile/mkcert/releases/latest/download/mkcert-v1.4.4-linux-amd64 -O mkcert
    chmod +x mkcert
    sudo mv mkcert /usr/local/bin/
    mkcert -install
    ```
  - Mac :
    ```bash
    brew install mkcert
    mkcert -install
    ```

---

## 🔒 Générer les certificats locaux

Dans le dossier racine du projet :


```bash
./scripts/docker/init-https-local.sh
```

Cela va générer les fichiers nécessaires dans `docker/certs/` :
- `localhost+2.pem` (certificat)
- `localhost+2-key.pem` (clé privée, chmod 644 pour compatibilité Docker)

**Sécurité locale** :
- Le volume Docker est monté en lecture seule (`:ro`), donc la clé ne peut pas être modifiée dans le conteneur.
- La clé privée n'est jamais versionnée (voir .gitignore).
- Le chmod 644 est nécessaire uniquement pour le dev local Docker, car Apache tourne sous un autre utilisateur que celui qui génère la clé.

---

## 🐳 Lancer Docker en HTTPS local

```bash
ENABLE_HTTPS=true docker-compose up --build
```

- Accès HTTP : http://localhost:8000
- Accès HTTPS : https://localhost:8443

---


## 🧪 CI/CD & Tests
- Par défaut, la CI/CD et les tests utilisent HTTP (pas de certificat requis).
- Le switch HTTP/HTTPS se fait via la variable d'environnement `ENABLE_HTTPS`.
- Aucun impact sur les tests Postman, unitaires ou frontend.
## 🚨 Passage en production

- **Ne jamais utiliser les certificats/dev en production !**
- En production, la clé privée doit :
  - Être générée et stockée par l'administrateur système ou via un gestionnaire de secrets (Vault, AWS Secrets Manager, etc.).
  - Avoir des permissions strictes (généralement `chmod 600` et propriétaire `www-data`).
  - Ne jamais être lisible par d'autres utilisateurs ou process.
- Le volume Docker doit être monté sans `:ro` si un chown est nécessaire, ou la clé doit être générée directement dans le conteneur avec le bon propriétaire.
- Adapter la configuration Apache pour pointer vers la vraie clé/certificat de production.

Pour les environnements cloud (ex. Azure) et la CI/CD :

- Préférez stocker les certificats/clefs privées dans un gestionnaire centralisé (ex. **Azure Key Vault**, **AWS Secrets Manager**, **HashiCorp Vault**) et lier le service d'hébergement à ce coffre via Managed Identity ou via un déploiement automatisé.
- Ne stockez pas les clés privées dans `GitHub Secrets` en clair. Conservez seulement les identifiants nécessaires (par ex. `AZURE_CREDENTIALS`) pour que le pipeline puisse appeler Azure et importer/binder le certificat.
- Si vous utilisez **Azure App Service**, vous pouvez uploader un PFX via la CLI dans votre workflow GitHub Actions et binder le certificat au custom hostname (extrait ci‑dessous).

Exemple minimal (GitHub Actions + Azure CLI) :
```yaml
- uses: azure/login@v1
  with:
    creds: ${{ secrets.AZURE_CREDENTIALS }}

- name: Upload certificate
  run: |
    az webapp config ssl upload \
      --resource-group RG_NAME \
      --name APP_NAME \
      --certificate-file certs/site.pfx \
      --certificate-password "${{ secrets.PFX_PASSWORD }}"

- name: Bind certificate
  run: |
    THUMB=$(az webapp config ssl list -g RG_NAME -n APP_NAME --query "[0].thumbprint" -o tsv)
    az webapp config ssl bind -g RG_NAME -n APP_NAME --certificate-thumbprint $THUMB --ssl-type SNI
```

— ou mieux — importez le certificat dans **Key Vault** et donnez l'accès en lecture au service (Managed Identity), évitant le stockage direct dans le pipeline.

Exemple CLI pour Key Vault :
```bash
az keyvault create -g RG_NAME -n KV_NAME
az keyvault certificate import --vault-name KV_NAME -n mycert --file site.pfx
# Puis configurez l'App Service / proxy pour utiliser le certificat depuis Key Vault
```

**Résumé :**
- En dev local : clé générée par mkcert, chmod 644, volume `:ro`, jamais versionnée.
- En prod : clé générée par l'admin, chmod 600, propriétaire www-data, jamais exposée, jamais versionnée.

**Remarques pour WSL / Windows**
- Si vous développez dans WSL mais naviguez depuis Windows, il faut importer la CA `rootCA.pem` dans le magasin Windows (CurrentUser → Trusted Root). Cela peut être fait depuis WSL en convertissant le chemin via `wslpath -w` puis en appelant `powershell.exe -Command "Import-Certificate ..."`.
- Si Firefox est utilisé sur Windows, importez la CA directement dans Firefox (Paramètres → Vie privée & sécurité → Certificats → Autorités) car Firefox peut utiliser son propre magasin.

Si vous voulez, je peux ajouter un workflow GitHub Actions prêt à l'emploi pour uploader/binder un PFX vers App Service (staging d'abord), ou un script CLI pour provisionner Key Vault et importer le certificat. Indiquez `RG_NAME` et `APP_NAME` si vous voulez que je le génère automatiquement.

---

## 🛠️ Dépannage
- Si le navigateur affiche un avertissement, relance `mkcert -install` puis le script.
- Pour ajouter d'autres domaines (vite.local, etc.) :
  ```bash
  cd docker/certs
  mkcert vite.local
  ```

---

## 📚 Références
- https://github.com/FiloSottile/mkcert
- https://httpd.apache.org/docs/2.4/ssl/ssl_howto.html

---

*Pour toute question, voir l'équipe technique ou le README principal du projet.*
