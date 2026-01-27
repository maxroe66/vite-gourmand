# Authentification (Inscription / Connexion / Déconnexion)

## Vue d'ensemble
Le backend utilise des JWT stockés dans un cookie `authToken` HttpOnly pour authentifier l'utilisateur. Trois points clés :
- **Inscription (register)** : crée l'utilisateur, force le rôle `UTILISATEUR`, et dépose le cookie.
- **Connexion (login)** : vérifie les identifiants, régénère le JWT, remplace le cookie.
- **Déconnexion (logout)** : supprime le cookie avec les mêmes options que celles utilisées pour l'émettre.

Les options du cookie (domaine, SameSite, secure, path) sont calculées une seule fois via un helper (`buildCookieOptions`) pour garantir la cohérence sur toutes les routes.

## Détails des flux

### Inscription
1. **Récupération du corps JSON** via `Request::createFromGlobals()` si aucun `Request` n'est fourni.
2. **Validation** avec `UserValidator` : prénom, nom, email, mot de passe (8+ chars, 1 maj, 1 min, 1 chiffre), téléphone, adresse, ville, code postal.
3. **Hash du mot de passe** (bcrypt via `password_hash`).
4. **Rôle forcé** à `UTILISATEUR` pour l'inscription publique.
5. **Création en base** via `UserService::createUser`. Collision email -> 409 + champ `errors['email']`.
6. **JWT** généré par `AuthService::generateToken(userId, role)`.
7. **Cookie `authToken`** écrit avec `buildCookieOptions(expire)` (HttpOnly, SameSite dépendant du HTTPS, domaine normalisé, path `/`).
8. **Email de bienvenue** via `MailerService`. Si échec d'email, on répond quand même 201 mais `emailSent=false`.

### Connexion
1. **Input JSON** récupéré comme pour l'inscription.
2. **Validation** avec `LoginValidator` (email, mot de passe requis, types string, format email).
3. **Recherche utilisateur** via `UserService::findByEmail`.
4. **Timing-attack mitigation** : si email inconnu, vérification sur un hash factice pour éviter de révéler l'absence de compte par timing.
5. **Compte actif** : rejet si `actif` est faux.
6. **Vérification du mot de passe** via `AuthService::verifyPassword` (lève `InvalidCredentialsException` si échec).
7. **JWT** généré avec l'ID et le rôle utilisateur.
8. **Cookie `authToken`** réécrit avec `buildCookieOptions(expire)`.
9. **Réponse 200** avec `userId`, `token` (copie du JWT) et message de succès.

### Déconnexion
1. **Expiration passée** (`time() - 3600`).
2. **Options du cookie** via `buildCookieOptions(expire)` pour supprimer la version avec domaine.
3. **Suppression host-only** : même options mais sans `domain` (`buildCookieOptions(expire, false)`) pour couvrir un éventuel cookie créé sans domaine explicite.
4. **Réponse 200** avec message de succès.

### Vérification (checkAuth)
- Appelée après passage dans le middleware d'auth (`AuthMiddleware`).
- Lit l'attribut `user` déjà injecté par le middleware (décodage JWT). Retourne 200 avec les infos utilisateur ou 401 si non présent ou utilisateur supprimé.

## Middleware Auth
- Cherche le token d'abord dans le cookie `authToken`, puis dans le header `Authorization: Bearer ...`.
- Vérifie la présence de `jwt.secret` en config, sinon lève `AuthException::configError`.
- Décode le JWT avec la clé HS256. Échec -> `AuthException::tokenInvalid` (401 attendu côté router).
- Attache le payload décodé dans `Request` sous l'attribut `user`.

## Calcul des options de cookie
- **HTTPS détecté** via `HTTPS=on`, `HTTP_X_FORWARDED_PROTO=https`, `HTTP_X_ARR_SSL`, ou `HTTP_X_ARR_PROTO=https` (proxys/CDN/Azure).
- **SameSite** : `None` si sécurisé, sinon `Lax`.
- **Domaine** :
  - `config['cookie_domain']` si fourni (normalisé avec un préfixe `.`).
  - Sinon `HTTP_HOST` sans port, sans `www.` (ex: `www.exemple.fr:8443` -> `.exemple.fr`).
- **Path** : `/`. **HttpOnly** : true. **Secure** : selon détection HTTPS.
- Helper unique `buildCookieOptions(expires, withDomain = true)` utilisé pour set et unset, garantissant la cohérence.

## Codes de statut et erreurs
- **201** Inscription OK (avec `emailSent=true|false`).
- **200** Connexion OK / Déconnexion OK / checkAuth OK.
- **400** Corps manquant ou invalide (register/login), token ou mot de passe manquants (reset).
- **401** Identifiants invalides, token manquant/expiré, compte désactivé, checkAuth sans user.
- **409** Email déjà utilisé (register).
- **422** Validation input échouée (register/login) ou mot de passe trop faible (reset).
- **500** Erreur serveur inattendue lors du login.

## Sécurité et bonnes pratiques
- JWT en cookie HttpOnly pour limiter l'accès JS.
- SameSite=None + Secure en HTTPS pour autoriser les requêtes cross-site avec credentials (SPA/front séparé).
- Domaine normalisé pour éviter la multiplication de cookies (apex/www/port).
- Protection timing sur login quand email inconnu.
- Rôle forcé à l'inscription publique.
- Logger les échecs de validation/connexion et les erreurs serveur pour audit.

## Points de configuration
- `config['jwt']['secret']` : clé HS256.
- `config['jwt']['expire']` : durée de vie du token (secondes).
- `config['cookie_domain']` (optionnel) : fixe explicitement le domaine du cookie (ex: `.vite-et-gourmand.me`).

## Fichiers concernés
- Contrôleur : `src/Controllers/Auth/AuthController.php`
- Middleware : `src/Middlewares/AuthMiddleware.php`
- Services : `src/Services/AuthService.php`, `src/Services/UserService.php`, `src/Services/MailerService.php`
- Validators : `src/Validators/UserValidator.php`, `src/Validators/LoginValidator.php`
- Exceptions : `src/Exceptions/AuthException.php`, `src/Exceptions/InvalidCredentialsException.php`, `src/Exceptions/UserServiceException.php`
