# CSRF - Implementation professionnelle (Double-Submit Cookie)

Date: 2026-02-11

## Objectif
Mettre en place une protection CSRF robuste et coherente pour une API stateless
(JWT en cookie httpOnly), sans casser l'architecture existante.

## Choix technique (et pourquoi)
**Double-Submit Cookie**:
- Adapté aux API stateless (pas de session serveur).
- Compatible avec JWT en cookie httpOnly.
- Simple a auditer et conforme aux bonnes pratiques OWASP.

Principe:
1) Le serveur depose un cookie `csrfToken` (non-httpOnly).
2) Le frontend lit ce cookie et envoie `X-CSRF-Token`.
3) Le backend verifie que `cookie == header`.

## Perimetre protege
Toutes les routes mutatrices (POST/PUT/PATCH/DELETE), y compris:
- Auth (register/login/logout/forgot/reset)
- Commandes, avis, admin, menus, plats, upload

## Etapes d'implementation

### 1) Backend (generation + verification)
- Ajout d'un service CSRF pour generer/poser le cookie.
- Ajout d'un middleware CSRF pour verifier le header.
- Rotation du token CSRF apres login/register.
- Suppression du token CSRF au logout.
- Endpoint d'initialisation: `GET /api/csrf`.
- CORS autorise `X-CSRF-Token`.

### 2) Frontend (injection automatique)
- Helper JS pour lire le cookie `csrfToken`.
- Injection du header `X-CSRF-Token` sur toutes les requetes mutatrices.
- Initialisation automatique du cookie CSRF au chargement des pages.

### 3) Tests (Postman / CI)
- Initialisation CSRF en debut de collection.
- Ajout automatique de `X-CSRF-Token` via script prerequest.

## Bonnes pratiques appliquees
- Token aleatoire cryptographiquement fort.
- Pas de secret en dur cote frontend.
- Pas de session serveur necessaire.
- Separation nette entre auth (JWT) et CSRF.

## Points d'attention
- Les collections Postman doivent demarrer par `GET /api/csrf`.
- Le cookie CSRF doit etre accessible au navigateur (non-httpOnly).
- Le header `X-CSRF-Token` doit etre autorise par CORS.

## Verification manuelle (checklist)
- [ ] `GET /api/csrf` pose un cookie `csrfToken`.
- [ ] Toute requete mutatrice sans header CSRF -> 403.
- [ ] Toute requete mutatrice avec header CSRF valide -> 200/201.
- [ ] Logout supprime le cookie CSRF.

## Fichiers cles
- Backend:
  - backend/src/Services/CsrfService.php
  - backend/src/Middlewares/CsrfMiddleware.php
  - backend/api/routes.*.php
  - backend/config/config.php
- Frontend:
  - frontend/js/services/authService.js
  - frontend/js/components.js
- Tests:
  - backend/tests/postman/*.postman_collection.json
