
# Documentation Technique – Fichier `config.php`

Ce document décrit le rôle et le fonctionnement du fichier `config.php`, responsable du chargement de l'environnement, de la configuration MySQL/MongoDB, du mail, du JWT, du CORS et de services tiers.

## 1. Rôle principal
`config.php` centralise toute la configuration du backend. Il :
- détecte l'environnement (dev, prod, test)
- charge le fichier `.env` approprié
- assemble une configuration normalisée pour MySQL, MongoDB, JWT, CORS, mails
- retourne un tableau associatif utilisé par le conteneur DI

## 2. Détection de l'environnement
L'environnement est déterminé via :
- `APP_ENV` ou `ENV`
- priorité aux variables système (CI / Docker / OS)

## 3. Chargement conditionnel de `.env`
- En test : `.env.test` si trouvé
- Sinon : `.env`
- `Dotenv::createImmutable()` garantit que les variables système ne sont jamais écrasées

## 4. Configuration MySQL
Paramètres chargés : host, port, base, user, password. 
Support SSL Azure inclus :
- `DB_SSL=1` active TLS
- certificat CA configurable

Options PDO configurées pour UTF8MB4 et exceptions.

## 5. Configuration MongoDB
Support :
- MongoDB standard
- Azure CosmosDB (détection automatique via port 10255)

Construction automatique de l’URI selon : user/pass, SSL, host, port.

## 6. CORS
Configuration retournée :
- `allowed_origins`
- `allowed_methods`
- `allowed_headers`
- `allow_credentials`

## 7. Mail
Support multi-provider :
- Dev : Mailtrap
- Prod : SendGrid

Choix automatique selon `APP_ENV`.

## 8. JWT
Vérification stricte : `JWT_SECRET` doit être défini en production.
Sinon une clé faible par défaut est fournie (mode dev/test seulement).

## 9. Retour final
Le fichier retourne un tableau complet contenant :
- db
- mongo
- cors
- jwt
- mail
- app_url
- env
- debug
- services tiers

Ce fichier constitue la base de toute la configuration du backend.
