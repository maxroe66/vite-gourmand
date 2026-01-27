# Backend — Architecture et fonctionnement (dossier `src`)

Ce document présente le fonctionnement complet du backend (dossier `src`) pour un jury technique : architecture, cycle de requête, routage, sécurité, persistance et points d’amélioration identifiés.

---

## 🎯 Vue d’ensemble

- Pattern global : Controller → Service → Repository, avec objets Request/Response, middlewares, exceptions et modèles.
- DI via conteneur PSR‑11 (config/container.php) pour instancier contrôleurs, services, dépôts et middlewares.
- Base de code sans framework, routeur maison, réponses HTTP standardisées, validations dédiées.

---

## 🧱 Architecture par couches

- **Core** : router, request/response, accès DB.
  - Router (groupes, middlewares, handlers) : [backend/src/Core/Router.php](backend/src/Core/Router.php)
  - Request (JSON, query params, attributs middleware) : [backend/src/Core/Request.php](backend/src/Core/Request.php)
  - Response (statuts, headers, JSON) : [backend/src/Core/Response.php](backend/src/Core/Response.php)
  - Database (PDO MySQL) : [backend/src/Core/Database.php](backend/src/Core/Database.php)
  - MongoDB client helper : [backend/src/Core/MongoDB.php](backend/src/Core/MongoDB.php)
- **Controllers** : points d’entrée HTTP, orchestrent validation → service → réponse. Exemple : [backend/src/Controllers/CommandeController.php](backend/src/Controllers/CommandeController.php)
- **Services** : règles métier (prix, stock, timeline, mails, géoloc). Exemple : [backend/src/Services/CommandeService.php](backend/src/Services/CommandeService.php)
- **Repositories** : accès données SQL/Mongo. Exemple : [backend/src/Repositories/CommandeRepository.php](backend/src/Repositories/CommandeRepository.php)
- **Models** : entités métiers (Menu, Commande, User, etc.).
- **Middlewares** : Auth JWT, rôles, CORS. Exemples : [backend/src/Middlewares/AuthMiddleware.php](backend/src/Middlewares/AuthMiddleware.php), [backend/src/Middlewares/RoleMiddleware.php](backend/src/Middlewares/RoleMiddleware.php), [backend/src/Middlewares/CorsMiddleware.php](backend/src/Middlewares/CorsMiddleware.php)
- **Validators** : validation d’inputs (ex. CommandeValidator).
- **Exceptions** : erreurs métiers typées (AuthException, CommandeException, ForbiddenException, etc.).

---

## 🔁 Cycle d’une requête

1. Routing (front controller public/index.php) : le routeur associe méthode + chemin à un handler, exécute les middlewares attachés.
2. Middlewares : Auth JWT enrichit Request avec l’utilisateur, Role vérifie les rôles, CORS gère les headers et OPTIONS.
3. Controller : valide (Validator dédié), appelle le Service, construit un Response objet.
4. Service : applique les règles métier, appelle les Repositories (SQL/Mongo), déclenche emails ou calculs.
5. Repository : exécute les requêtes SQL (PDO) ou interactions Mongo.
6. Response : objet Response standardisé (statut, headers, JSON) renvoyé, puis envoyé en sortie.

---

## 🌐 Routage principal (API)

- Auth : login/register/logout/reset/check. Fichier : [backend/api/routes.auth.php](backend/api/routes.auth.php)
- Menus / Plats : lecture publique + CRUD protégé (EMPLOYE/ADMIN). Fichier : [backend/api/routes.menus.php](backend/api/routes.menus.php)
- Commandes : création, calcul prix, update, statut, matériel, timeline. Fichier : [backend/api/routes.commandes.php](backend/api/routes.commandes.php)
- Avis : création, liste, validation, suppression, endpoint public. Fichier : [backend/api/routes.avis.php](backend/api/routes.avis.php)
- Matériel : inventaire protégé (EMPLOYE/ADMIN). Fichier : [backend/api/routes.materiel.php](backend/api/routes.materiel.php)
- Upload : upload image (TODO sécurisation). Fichier : [backend/api/routes.php](backend/api/routes.php)

---

## 🔐 Sécurité et middlewares

- AuthMiddleware : vérifie JWT (cookie authToken ou header Bearer), attache l’utilisateur dans Request.
- RoleMiddleware : contrôle des rôles requis ; certains contrôleurs refont un check interne pour défense en profondeur.
- CORS : headers dynamiques selon configuration, gère OPTIONS, refuse les origines non autorisées.
- Exceptions capturées par Router : 401 (AuthException), 403 (ForbiddenException), sinon 500 générique.

---

## 💾 Persistance et métier (exemple Commande)

- Calcul de prix : distance via GoogleMapsService, réduction conditionnelle, frais de livraison, snapshot des montants dans la commande.
- Création : validation, prix, insertion COMMANDE + historique statut initial, décrément stock menu, prêt matériel auto selon menu, sync Mongo (best effort).
- Timeline : historique statuts + acteur (jointure UTILISATEUR) via CommandeRepository.
- Matériel : prêt et retour ajustent COMMANDE_MATERIEL et stocks MATERIEL.

---

## 🧪 Tests

- Suite PHPUnit présente dans backend/tests (controllers, services, validators, core).
- Les objets Response/Request facilitent les assertions (contenu, headers, codes HTTP).

---

## ⚠️ Points d’attention / backlog technique

1) ✅ **CORRIGÉ** - Route upload sécurisée : `/api/upload` maintenant protégée par AuthMiddleware + RoleMiddleware (EMPLOYE/ADMIN). Source : [backend/api/routes.php](backend/api/routes.php)
2) ✅ **CORRIGÉ** - Rôles vérifiés sur `return-material` : AuthMiddleware pour l'authentification + RoleMiddleware pour les rôles (EMPLOYE/ADMINISTRATEUR). Source : [backend/api/routes.commandes.php](backend/api/routes.commandes.php)
3) ✅ **CORRIGÉ** - Commandes : toutes les routes utilisent maintenant le chaînage `->middleware()` pour une architecture cohérente et maintenable. Les rôles sont correctement appliqués (EMPLOYE/ADMIN pour material, stats Admin uniquement). Source : [backend/api/routes.commandes.php](backend/api/routes.commandes.php)
4) ✅ **CORRIGÉ** - Avis listés : `/api/avis` sert désormais le public (avis validés uniquement) sans token, mais exige un admin authentifié pour les statuts de modération. Auth est appliquée de façon optionnelle et les champs sensibles sont masqués pour les clients publics. Source : [backend/api/routes.avis.php](backend/api/routes.avis.php), [backend/src/Controllers/AvisController.php](backend/src/Controllers/AvisController.php)
5) ✅ **CORRIGÉ** - Matériels : l’inventaire reste protégé (Auth + Role EMPLOYE/ADMIN) car réservé aux équipes internes ; aucun accès public requis. Le frontend l’utilise côté back-office via cookies de session. Source : [backend/api/routes.materiel.php](backend/api/routes.materiel.php)
6) ✅ **CORRIGÉ** - Méthode HTTP non supportée : le Router renvoie désormais 405 avec l’en-tête Allow quand le chemin existe sur d’autres méthodes, sinon 404 si aucune route ne correspond. Source : [backend/src/Core/Router.php](backend/src/Core/Router.php)
7) ✅ **CORRIGÉ** - JSON invalide distingué du body vide : Request détecte maintenant les erreurs de parsing (code/message), les expose via `hasJsonError()`/`getJsonError()` et considère un body vide comme sans erreur. Source : [backend/src/Core/Request.php](backend/src/Core/Request.php)
8) ✅ **CORRIGÉ** - Rôles déplacés en middleware : les routes sensibles (statut commande, matériel) passent désormais par RoleMiddleware (EMPLOYE/ADMIN) avant le contrôleur, évitant l’exécution inutile. Sources : [backend/api/routes.commandes.php](backend/api/routes.commandes.php)

---

## ✅ Conclusion

Le dossier `src` forme un mini‑framework maison : front controller, routeur avec middlewares, DI, services métiers, repositories, validations et exceptions typées. L’architecture est claire et modulaire ; en traitant le backlog ci‑dessus (sécurisation d’upload, harmonisation des middlewares, gestion stricte des rôles et des erreurs JSON), le backend sera aligné sur les meilleures pratiques de production.
