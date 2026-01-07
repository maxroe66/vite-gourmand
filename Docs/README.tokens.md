# Documentation du Système d'Authentification par Token JWT

Ce document explique le fonctionnement du système d'authentification basé sur les JSON Web Tokens (JWT) mis en place dans l'application. L'objectif est d'assurer une communication sécurisée entre le frontend (JavaScript vanilla) et le backend (PHP), en utilisant des cookies `HttpOnly` pour stocker les tokens.

## Principes de base

-   **Stateless (sans état)** : Le serveur ne stocke pas les informations de session. Chaque requête du client doit contenir les informations nécessaires à son authentification.
-   **JWT** : Un token compact et auto-contenu qui contient des informations (le *payload*) sur l'utilisateur (comme son ID et son rôle). Il est signé numériquement par le serveur pour garantir son intégrité.
-   **Cookie `HttpOnly`** : Le JWT est stocké côté client dans un cookie qui n'est pas accessible par JavaScript. Cela protège le token contre les attaques de type XSS (Cross-Site Scripting). Le navigateur se charge d'envoyer automatiquement le cookie à chaque requête vers le serveur.

## Flux d'Authentification (Inscription / Connexion)

1.  **Initiation (Frontend)** : L'utilisateur soumet un formulaire d'inscription ou de connexion. Le JavaScript (`frontend/js/services/authService.js`) envoie une requête `fetch` en `POST` vers une route du backend (ex: `/api/auth/register`).

2.  **Traitement (Backend)** :
    -   La requête arrive sur `public/index.php`, qui la transmet au routeur (`backend/api/routes.auth.php`).
    -   La closure de la route correspondante est exécutée. Elle instancie les services nécessaires (`UserService`, `AuthService`) et le contrôleur `AuthController`, en leur injectant la configuration de l'application.
    -   Le `AuthController` valide les données, crée l'utilisateur en base de données et, si tout est correct, demande à `AuthService` de générer un token.

3.  **Génération du Token (`AuthService`)** :
    -   La méthode `generateToken` dans `AuthService` crée un *payload* contenant :
        -   `iss` (Issuer) : Qui a émis le token (ex: 'vite-gourmand').
        -   `sub` (Subject) : L'identifiant de l'utilisateur.
        -   `role` : Le rôle de l'utilisateur.
        -   `iat` (Issued At) : La date de création du token.
        -   `exp` (Expiration Time) : La date d'expiration du token.
    -   Ce payload est ensuite encodé et signé avec une clé secrète (définie dans `config.php`) en utilisant l'algorithme `HS256`. Le résultat est une chaîne de caractères : le JWT.

4.  **Envoi du Cookie (`AuthController`)** :
    -   Le `AuthController` reçoit le JWT et utilise la fonction `setcookie()` de PHP pour l'envoyer au navigateur.
    -   Des options de sécurité cruciales sont définies :
        -   `httponly: true` : Le cookie est inaccessible en JavaScript.
        -   `secure: true` (en production) : Le cookie n'est envoyé que sur une connexion HTTPS.
        -   `samesite: 'Lax'` : Offre une protection contre les attaques CSRF.
        -   Le `domain` est omis pour laisser le navigateur utiliser le domaine par défaut.

5.  **Stockage (Frontend)** : Le navigateur reçoit l'en-tête `Set-Cookie` et stocke le cookie `authToken`. Il le joindra automatiquement à toutes les futures requêtes vers le même domaine.

## Flux d'une Requête Authentifiée

1.  **Initiation (Frontend)** : Le frontend a besoin d'accéder à une ressource protégée ou de vérifier si l'utilisateur est toujours connecté. Il envoie une requête `fetch` vers une route protégée, par exemple `GET /api/auth/check`.

2.  **Vérification par le Middleware (Backend)** :
    -   Dans `routes.auth.php`, la route `/api/auth/check` est configurée pour d'abord appeler la méthode `AuthMiddleware::check($config)`.
    -   Le middleware `check` :
        -   Extrait le token du cookie `$_COOKIE['authToken']`.
        -   Utilise la configuration (`$config`) pour récupérer la clé secrète.
        -   Tente de décoder et de vérifier le token avec `JWT::decode()`.
        -   Si le token est invalide ou expiré, le script s'arrête avec une erreur `401 Unauthorized`.
        -   Si le token est valide, le payload décodé est stocké dans une propriété statique de la classe `AuthMiddleware` pour être accessible plus tard.

3.  **Exécution du Contrôleur (Backend)** :
    -   Le middleware ayant validé la requête, l'exécution continue.
    -   La méthode `checkAuth` du `AuthController` est appelée.
    -   **Elle ne re-valide pas le token.** Elle fait confiance au travail du middleware.
    -   Elle récupère les informations de l'utilisateur en appelant `AuthMiddleware::getDecodedToken()`.
    -   Elle retourne une réponse JSON confirmant que l'utilisateur est authentifié, avec son ID et son rôle.

## Flux de Déconnexion

1.  **Initiation (Frontend)** : L'utilisateur clique sur "Déconnexion". Une requête `POST` est envoyée à `/api/auth/logout`.

2.  **Invalidation du Cookie (Backend)** :
    -   La méthode `logout` du `AuthController` est appelée.
    -   Elle utilise `setcookie()` pour renvoyer un cookie `authToken` avec une date d'expiration dans le passé.
    -   Le navigateur reçoit cette instruction et supprime immédiatement le cookie. La session côté client est terminée.
