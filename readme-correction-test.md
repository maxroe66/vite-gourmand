# 🛠️ Avancement — ce que j'ai corrigé

Voici un résumé clair de ce que j'ai identifié et corrigé, ainsi que les bonnes pratiques que j'applique désormais.

## 1) Les tests n’utilisaient pas la base de test
Problèmes identifiés :
- `phpunit.xml` ne forçait pas `APP_ENV=test`.
- Le backend lisait un `.env` de dev ou des variables incohérentes (`DB_PASS` vs `DB_PASSWORD`).
- Les scripts SQL utilisaient `USE ...` / `CREATE DATABASE ...`, et écrasaient la base visée.

Ce que j'ai fait :
- `backend/config/config.php` : charge `.env.test` si `APP_ENV=test`, privilégie `getenv()`, et construit le DSN avec port.
- `backend/phpunit.xml` : ajout de `APP_ENV=test` pour forcer le mode test.
- `scripts/test_backend.sh` : export des variables de test (`DB_PORT=3307`, `DB_NAME=vite_gourmand_test`, ...), démarrage du serveur de test.
- Postman/Newman : URLs paramétrées via `{{base_url}}{{api_path}}` (plus de ports en dur dans les requêtes).

---

## 2) Reset MongoDB qui plantait (doublons)
Problème :
- `setup.js` utilisait un fallback (`vite_et_gourmand`) parce que `process.env.MONGO_INITDB_DATABASE` était `undefined` lors d'un `docker exec`.

Ce que j'ai fait :
- `setup.js` : ajout du support pour injecter `DB_NAME` via `--eval`.
- `reset_test_db.sh` : j'exécute maintenant le script avec `--eval "var DB_NAME='vite_gourmand_test';"`.

---

## 3) 500 sur `/api/auth/register`
Causes successives :
- Absence du driver `pdo_mysql` (erreur « could not find driver »).
- La base de test n'avait pas les tables (le SQL avait ciblé la mauvaise base à cause de `USE`).
- Postman envoyait des données non conformes (ex : `firstName="Test1234"`).

Ce que j'ai fait :
- Installation de `php8.1-mysql` pour obtenir `pdo_mysql`.
- Suppression des `USE ...` dans les scripts SQL de test.
- Postman : ajout du header `Content-Type: application/json`, et validation stricte des champs (prénom/nom sans chiffres). Le test « email déjà utilisé » réutilise désormais exactement `{{uniqueEmail}}`.
- `User.php` : adaptation pour accepter `password` et générer le hash si `passwordHash` est absent.

---

## Organisation pour éviter les erreurs à l'avenir
Principe : la base utilisée dépend uniquement de `APP_ENV` et des variables DB (`DB_NAME`, `DB_PORT`, ...). J'ai séparé clairement les configs dev/test.

### A) Fichiers de configuration recommandés (à la racine)
- `.env` → dev (base normale)
- `.env.test` → tests (base de test)

Exemples :
```ini
# .env (DEV)
APP_ENV=development
APP_DEBUG=true
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=vite_gourmand
DB_USER=root
DB_PASSWORD=<PLACEHOLDER>
MONGO_HOST=127.0.0.1
MONGO_PORT=27017
MONGO_DB=vite_gourmand

# .env.test (TEST)
APP_ENV=test
APP_DEBUG=false
DB_HOST=127.0.0.1
DB_PORT=3307
DB_NAME=vite_gourmand_test
DB_USER=root
DB_PASSWORD=root
MONGO_HOST=127.0.0.1
MONGO_PORT=27018
MONGO_DB=vite_gourmand_test
```

### B) Règle d'or
- En dev : lancer l'app sans `APP_ENV=test` → lecture de `.env` → base normale.
- En test : lancer `./scripts/test_backend.sh` qui exporte `APP_ENV=test` → lecture de `.env.test` → base de test.

### C) Scripts SQL : ne jamais inclure `USE <db>`
- `database_creation.sql` et `database_fixtures.sql` ne doivent pas contenir `USE` ni `CREATE DATABASE`.
- La base ciblée est choisie par la commande shell : `mysql <db> < script.sql`.

### D) Ports (convention)
- Dev API : http://localhost:8000 (docker/apache)
- Tests API : http://localhost:8001 (php -S)
- Dev MySQL : 3306
- Test MySQL : 3307
- Dev Mongo : 27017
- Test Mongo : 27018

---

## Checklist rapide avant de coder
- `echo $APP_ENV` → doit être vide ou `development` (pas `test`)
- `.env` contient `DB_NAME=vite_gourmand`
- `.env.test` contient `DB_NAME=vite_gourmand_test`
- Je lance les tests via `./scripts/test_backend.sh` (ne pas lancer les tests « à la main » sans exporter l'env)

Si tu veux, je peux :
- committer ce fichier pour toi,
- ajouter une note dans le `README.md` principal,
- ou exécuter les tests de bout en bout maintenant.

