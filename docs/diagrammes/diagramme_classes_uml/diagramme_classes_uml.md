# Diagramme de Classes UML — Vite & Gourmand

> **Version :** 2.0.0  
> **Mise à jour :** 18 février 2026  
> **Correspond au code réel** du projet (11 Controllers, 11 Services, 12 Repositories, 6 Middlewares, 10 Validators, 7 Models)

---

## Diagramme Mermaid

```mermaid
classDiagram
    direction TB

    %% ════════════════════════════════════════
    %% COUCHE CORE — Infrastructure
    %% ════════════════════════════════════════

    class Router {
        -routes: array
        -middlewares: array
        -container: Container
        +__construct(container)
        +addRoute(method, path, handler, middlewares) void
        +dispatch(request) Response
        -matchRoute(method, uri) array
        -resolveHandler(handler) callable
        -executeMiddlewares(middlewares, request) void
    }

    class Request {
        -method: string
        -uri: string
        -params: array
        -body: array
        -attributes: array
        +getMethod() string
        +getUri() string
        +getParam(key) mixed
        +getBody() array
        +getAttribute(key) mixed
        +setAttribute(key, value) void
        +getJsonBody() array
    }

    class Response {
        +json(data, statusCode) void
        +error(message, statusCode) void
        +success(data, message, statusCode) void
        +created(data, message) void
        +noContent() void
        +paginated(data, total, page, limit) void
    }

    class Database {
        -pdo: PDO
        +__construct(config)
        +getConnection() PDO
        +query(sql, params) PDOStatement
        +fetchOne(sql, params) array|false
        +fetchAll(sql, params) array
        +insert(sql, params) int
        +beginTransaction() void
        +commit() void
        +rollBack() void
    }

    class MongoDB {
        -client: MongoDB_Client
        -database: MongoDB_Database
        +__construct(config)
        +getDatabase() MongoDB_Database
        +getCollection(name) MongoDB_Collection
        +insertOne(collection, document) string
        +findOne(collection, filter) array|null
        +findAll(collection, filter, options) array
        +updateOne(collection, filter, update) int
        +deleteOne(collection, filter) int
        +aggregate(collection, pipeline) array
    }

    %% ════════════════════════════════════════
    %% COUCHE MIDDLEWARES (6)
    %% ════════════════════════════════════════

    class AuthMiddleware {
        -config: array
        -logger: LoggerInterface
        +__construct(config, logger)
        +handle(request, args) void
        -getTokenFromRequest() string|null
        -validateJwtFromCookie() object
    }

    class CsrfMiddleware {
        -csrfService: CsrfService
        +__construct(csrfService)
        +handle(request) void
        -validateDoubleSubmitCookie() bool
    }

    class CorsMiddleware {
        -config: array
        +__construct(config)
        +handle() void
        -getAllowedOrigins() array
    }

    class RateLimitMiddleware {
        -storagePath: string
        +__construct(config)
        +handle(maxRequests, windowSeconds) void
        -getClientIdentifier() string
        -isRateLimited(key, max, window) bool
    }

    class RoleMiddleware {
        +handle(request, allowedRoles) void
        -getUserRole(request) string
    }

    class SecurityHeadersMiddleware {
        -config: array
        +__construct(config)
        +handle() void
        -buildCspHeader() string
    }

    %% ════════════════════════════════════════
    %% COUCHE CONTROLLERS (11)
    %% ════════════════════════════════════════

    class AuthController {
        -authService: AuthService
        -csrfService: CsrfService
        -userValidator: UserValidator
        -loginValidator: LoginValidator
        -resetPasswordValidator: ResetPasswordValidator
        -config: array
        +register(request) Response
        +login(request) Response
        +logout(request) Response
        +check(request) Response
        +forgotPassword(request) Response
        +resetPassword(request) Response
        +updateProfile(request) Response
        +getCsrfToken() Response
        -buildCookieOptions(expire) array
    }

    class MenuController {
        -menuService: MenuService
        -menuValidator: MenuValidator
        +index(request) Response
        +show(request) Response
        +store(request) Response
        +update(request) Response
        +destroy(request) Response
        +getThemes() Response
        +getRegimes() Response
    }

    class PlatController {
        -platService: PlatService
        -platValidator: PlatValidator
        +index(request) Response
        +show(request) Response
        +store(request) Response
        +update(request) Response
        +destroy(request) Response
        +getAllergenes() Response
        +getByType(request) Response
    }

    class CommandeController {
        -commandeService: CommandeService
        -commandeValidator: CommandeValidator
        +store(request) Response
        +calculatePrice(request) Response
        +update(request) Response
        +updateStatus(request) Response
        +getUserOrders(request) Response
        +index(request) Response
        +show(request) Response
        +addMaterial(request) Response
        +returnMaterial(request) Response
        +getOverdueMaterials(request) Response
    }

    class AvisController {
        -avisService: AvisService
        +store(request) Response
        +index(request) Response
        +getPublic(request) Response
        +validate(request) Response
        +destroy(request) Response
    }

    class AdminController {
        -userService: UserService
        -employeeValidator: EmployeeValidator
        +createEmployee(request) Response
        +getEmployees(request) Response
        +disableUser(request) Response
    }

    class ContactController {
        -contactService: ContactService
        -contactValidator: ContactValidator
        +store(request) Response
    }

    class HoraireController {
        -horaireRepository: HoraireRepository
        -horaireValidator: HoraireValidator
        +index(request) Response
        +update(request) Response
    }

    class MaterielController {
        -materielRepository: MaterielRepository
        -materielValidator: MaterielValidator
        +index(request) Response
        +show(request) Response
        +store(request) Response
        +update(request) Response
        +destroy(request) Response
    }

    class StatsController {
        -commandeRepository: CommandeRepository
        -mongodb: MongoDB
        +getMenuCommandeStats(request) Response
    }

    class UploadController {
        -storageService: StorageService
        +upload(request) Response
    }

    %% ════════════════════════════════════════
    %% COUCHE SERVICES (11)
    %% ════════════════════════════════════════

    class AuthService {
        -userRepository: UserRepository
        -resetTokenRepository: ResetTokenRepository
        -mailerService: MailerService
        -config: array
        +register(data) array
        +login(email, password) array
        +generateToken(userId, role) string
        +verifyToken(token) object
        +forgotPassword(email) bool
        +resetPassword(token, password) bool
        +hashPassword(password) string
        +verifyPassword(password, hash) bool
    }

    class CommandeService {
        -commandeRepository: CommandeRepository
        -menuRepository: MenuRepository
        -materielRepository: MaterielRepository
        -googleMapsService: GoogleMapsService
        -mailerService: MailerService
        -mongodb: MongoDB
        -config: array
        +create(data, userId) array
        +calculatePrice(menuId, personnes, adresse) array
        +update(commandeId, data, userId) array
        +updateStatus(commandeId, status, userId, comment) array
        +cancel(commandeId, userId, motif, modeContact) array
        +addMaterial(commandeId, materielId, quantite) array
        +returnMaterial(commandeId, materielId) array
        +getOverdueMaterials() array
        -applyDiscount(personnes, minPersonnes, prixBase) float
        -calculateDeliveryFee(adresse) float
        -sendStatusEmail(commande, status) void
    }

    class AvisService {
        -avisRepository: AvisRepository
        -commandeRepository: CommandeRepository
        -mongodb: MongoDB
        -logger: LoggerInterface
        +create(data, userId) array
        +getAll(filters) array
        +getPublicAvis() array
        +validate(avisId, moderateurId) array
        +reject(avisId, moderateurId) array
        +delete(avisId) bool
        -syncToMongoDB(avis) void
    }

    class MenuService {
        -menuRepository: MenuRepository
        -platRepository: PlatRepository
        +getAll(filters) array
        +getById(id) array
        +create(data) array
        +update(id, data) array
        +delete(id) bool
        +getThemes() array
        +getRegimes() array
    }

    class PlatService {
        -platRepository: PlatRepository
        -allergeneRepository: AllergeneRepository
        +getAll() array
        +getById(id) array
        +create(data) array
        +update(id, data) array
        +delete(id) bool
        +getByType(type) array
        +getAllergenes() array
    }

    class UserService {
        -userRepository: UserRepository
        -authService: AuthService
        -mailerService: MailerService
        +createEmployee(data) array
        +getEmployees() array
        +disableUser(userId) bool
        +updateProfile(userId, data) array
        +getUserById(userId) array
    }

    class ContactService {
        -contactRepository: ContactRepository
        -mailerService: MailerService
        +create(data) array
    }

    class CsrfService {
        -config: array
        +generateToken() string
        +rotateToken() string
        +getTokenFromCookie() string|null
        +getTokenFromHeader() string|null
        +validateToken() bool
        -setCookie(token) void
        -deleteCookie() void
    }

    class GoogleMapsService {
        -apiKey: string
        -logger: LoggerInterface
        +calculateDistance(origin, destination) float
        +geocodeAddress(address) array
        -callApi(endpoint, params) array
        -fallbackDistance(address) float
    }

    class MailerService {
        -config: array
        -logger: LoggerInterface
        +sendWelcomeEmail(user) bool
        +sendOrderConfirmation(commande, user) bool
        +sendStatusChangeEmail(commande, user, status) bool
        +sendPasswordResetEmail(user, token) bool
        +sendMaterialOverdueNotice(commande, user) bool
        +sendContactNotification(contact) bool
        +sendEmployeeCreatedEmail(employee) bool
        -sendMail(to, subject, htmlBody) bool
        -loadTemplate(name, vars) string
    }

    class StorageService {
        -config: array
        -logger: LoggerInterface
        +upload(file) string
        +delete(path) bool
        -uploadLocal(file) string
        -uploadAzureBlob(file) string
        -getStorageDriver() string
    }

    %% ════════════════════════════════════════
    %% COUCHE REPOSITORIES (12)
    %% ════════════════════════════════════════

    class UserRepository {
        -db: Database
        +findById(id) array|null
        +findByEmail(email) array|null
        +create(data) int
        +update(id, data) bool
        +getAll(filters) array
        +disable(id) bool
        +getByRole(role) array
    }

    class MenuRepository {
        -db: Database
        +findById(id) array|null
        +getAll(filters) array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +getWithPlats(id) array
        +decrementStock(id, qty) bool
        +attachPlats(menuId, platIds) void
    }

    class PlatRepository {
        -db: Database
        +findById(id) array|null
        +getAll() array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +getByType(type) array
        +getByMenu(menuId) array
        +attachAllergenes(platId, allergeneIds) void
    }

    class CommandeRepository {
        -db: Database
        +findById(id) array|null
        +getAll(filters) array
        +getByUserId(userId) array
        +create(data) int
        +update(id, data) bool
        +updateStatus(id, status, userId, comment) bool
        +addAnnulation(data) int
        +addModification(data) int
        +getStatusHistory(commandeId) array
        +getMenuCommandeStats(filters) array
    }

    class AvisRepository {
        -db: Database
        -mongodb: MongoDB
        +findById(id) array|null
        +getAll(filters) array
        +getValidated() array
        +create(data) int
        +updateValidation(id, status, moderateurId) bool
        +delete(id) bool
        +getByCommande(commandeId) array|null
    }

    class AllergeneRepository {
        -db: Database
        +getAll() array
        +findById(id) array|null
        +getByPlat(platId) array
    }

    class ThemeRepository {
        -db: Database
        +getAll() array
        +findById(id) array|null
    }

    class RegimeRepository {
        -db: Database
        +getAll() array
        +findById(id) array|null
    }

    class HoraireRepository {
        -db: Database
        +getAll() array
        +findById(id) array|null
        +update(id, data) bool
    }

    class ContactRepository {
        -db: Database
        +create(data) int
        +getAll(filters) array
        +findById(id) array|null
    }

    class MaterielRepository {
        -db: Database
        +findById(id) array|null
        +getAll() array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +getByCommande(commandeId) array
        +addToCommande(commandeId, materielId, qty) int
        +markReturned(commandeId, materielId) bool
    }

    class ResetTokenRepository {
        -db: Database
        +create(userId, token, expiration) int
        +findByToken(token) array|null
        +markAsUsed(token) bool
        +deleteExpired() int
    }

    %% ════════════════════════════════════════
    %% COUCHE VALIDATORS (10)
    %% ════════════════════════════════════════

    class UserValidator {
        +validateRegistration(data) void
        +validateProfileUpdate(data) void
        -validateEmail(email) void
        -validatePassword(password) void
        -validatePhone(phone) void
    }

    class LoginValidator {
        +validateLogin(data) void
    }

    class ResetPasswordValidator {
        +validateForgotPassword(data) void
        +validateResetPassword(data) void
    }

    class MenuValidator {
        +validateCreate(data) void
        +validateUpdate(data) void
    }

    class PlatValidator {
        +validateCreate(data) void
        +validateUpdate(data) void
    }

    class CommandeValidator {
        +validateCreate(data) void
        +validateUpdate(data) void
        +validateStatusUpdate(data) void
        +validateCancellation(data) void
    }

    class ContactValidator {
        +validateCreate(data) void
    }

    class HoraireValidator {
        +validateUpdate(data) void
    }

    class MaterielValidator {
        +validateCreate(data) void
        +validateUpdate(data) void
    }

    class EmployeeValidator {
        +validateCreate(data) void
    }

    %% ════════════════════════════════════════
    %% COUCHE MODELS (7)
    %% ════════════════════════════════════════

    class User {
        <<Model>>
        +id: int
        +nom: string
        +prenom: string
        +email: string
        +gsm: string
        +adresse: string
        +ville: string
        +code_postal: string
        +mot_de_passe: string
        +role: ENUM~UTILISATEUR,EMPLOYE,ADMINISTRATEUR~
        +actif: bool
        +date_creation: DateTime
    }

    class Menu {
        <<Model>>
        +id: int
        +titre: string
        +description: string
        +nombre_personne_min: int
        +prix: decimal
        +stock_disponible: int
        +conditions: text
        +actif: bool
        +id_theme: int
        +id_regime: int
    }

    class Commande {
        <<Model>>
        +id: int
        +id_utilisateur: int
        +id_menu: int
        +date_prestation: date
        +heure_livraison: time
        +adresse_livraison: string
        +ville_livraison: string
        +nombre_personnes: int
        +prix_menu_snapshot: decimal
        +prix_livraison: decimal
        +prix_total: decimal
        +statut: ENUM~8 statuts~
        +has_avis: bool
        +materiel_pret: bool
        +date_creation: DateTime
    }

    class CommandeStatut {
        <<Model>>
        +id: int
        +id_commande: int
        +statut: string
        +date_changement: DateTime
        +modifie_par: int
        +commentaire: text
    }

    class Avis {
        <<Model>>
        +id: int
        +note: int
        +commentaire: text
        +statut_validation: ENUM~en_attente,valide,refuse~
        +id_utilisateur: int
        +id_commande: int
        +id_menu: int
        +modere_par: int
        +mongo_id: string
    }

    class Horaire {
        <<Model>>
        +id: int
        +jour: ENUM~7 jours~
        +heure_ouverture: time
        +heure_fermeture: time
        +ferme: bool
    }

    class Materiel {
        <<Model>>
        +id: int
        +libelle: string
        +description: text
        +valeur_unitaire: decimal
        +stock_disponible: int
    }

    %% ════════════════════════════════════════
    %% COUCHE EXCEPTIONS (6)
    %% ════════════════════════════════════════

    class AuthException {
        +tokenMissing()$ AuthException
        +tokenInvalid()$ AuthException
        +configError()$ AuthException
    }

    class ForbiddenException {
        +__construct(message)
    }

    class CommandeException {
        +notFound()$ CommandeException
        +invalidStatus()$ CommandeException
        +cannotModify()$ CommandeException
    }

    class InvalidCredentialsException {
        +__construct(message)
    }

    class TooManyRequestsException {
        +__construct(retryAfter)
    }

    class UserServiceException {
        +emailAlreadyExists()$ UserServiceException
        +userNotFound()$ UserServiceException
    }

    %% ════════════════════════════════════════
    %% RELATIONS — Core
    %% ════════════════════════════════════════

    Router --> Request : reçoit
    Router --> Response : produit
    Router --> AuthMiddleware : exécute
    Router --> CsrfMiddleware : exécute
    Router --> CorsMiddleware : exécute
    Router --> RateLimitMiddleware : exécute
    Router --> RoleMiddleware : exécute
    Router --> SecurityHeadersMiddleware : exécute

    %% ════════════════════════════════════════
    %% RELATIONS — Middlewares → Services
    %% ════════════════════════════════════════

    AuthMiddleware ..> AuthException : lève
    CsrfMiddleware --> CsrfService : utilise
    CsrfMiddleware ..> ForbiddenException : lève
    RateLimitMiddleware ..> TooManyRequestsException : lève
    RoleMiddleware ..> ForbiddenException : lève

    %% ════════════════════════════════════════
    %% RELATIONS — Controllers → Services/Validators
    %% ════════════════════════════════════════

    AuthController --> AuthService : utilise
    AuthController --> CsrfService : utilise
    AuthController --> UserValidator : utilise
    AuthController --> LoginValidator : utilise
    AuthController --> ResetPasswordValidator : utilise

    MenuController --> MenuService : utilise
    MenuController --> MenuValidator : utilise

    PlatController --> PlatService : utilise
    PlatController --> PlatValidator : utilise

    CommandeController --> CommandeService : utilise
    CommandeController --> CommandeValidator : utilise

    AvisController --> AvisService : utilise

    AdminController --> UserService : utilise
    AdminController --> EmployeeValidator : utilise

    ContactController --> ContactService : utilise
    ContactController --> ContactValidator : utilise

    HoraireController --> HoraireRepository : utilise
    HoraireController --> HoraireValidator : utilise

    MaterielController --> MaterielRepository : utilise
    MaterielController --> MaterielValidator : utilise

    StatsController --> CommandeRepository : utilise
    StatsController --> MongoDB : utilise

    UploadController --> StorageService : utilise

    %% ════════════════════════════════════════
    %% RELATIONS — Services → Repositories
    %% ════════════════════════════════════════

    AuthService --> UserRepository : utilise
    AuthService --> ResetTokenRepository : utilise
    AuthService --> MailerService : utilise

    CommandeService --> CommandeRepository : utilise
    CommandeService --> MenuRepository : utilise
    CommandeService --> MaterielRepository : utilise
    CommandeService --> GoogleMapsService : utilise
    CommandeService --> MailerService : utilise
    CommandeService --> MongoDB : synchronise stats

    AvisService --> AvisRepository : utilise
    AvisService --> CommandeRepository : vérifie commande
    AvisService --> MongoDB : synchronise avis

    MenuService --> MenuRepository : utilise
    MenuService --> PlatRepository : utilise

    PlatService --> PlatRepository : utilise
    PlatService --> AllergeneRepository : utilise

    UserService --> UserRepository : utilise
    UserService --> AuthService : utilise
    UserService --> MailerService : utilise

    ContactService --> ContactRepository : utilise
    ContactService --> MailerService : utilise

    %% ════════════════════════════════════════
    %% RELATIONS — Repositories → Core DB
    %% ════════════════════════════════════════

    UserRepository --> Database : utilise
    MenuRepository --> Database : utilise
    PlatRepository --> Database : utilise
    CommandeRepository --> Database : utilise
    AvisRepository --> Database : utilise
    AvisRepository --> MongoDB : utilise
    AllergeneRepository --> Database : utilise
    ThemeRepository --> Database : utilise
    RegimeRepository --> Database : utilise
    HoraireRepository --> Database : utilise
    ContactRepository --> Database : utilise
    MaterielRepository --> Database : utilise
    ResetTokenRepository --> Database : utilise

    %% ════════════════════════════════════════
    %% RELATIONS — Models (associations métier)
    %% ════════════════════════════════════════

    User "1" --> "0..*" Commande : passe
    Menu "1" --> "0..*" Commande : est commandé dans
    Commande "1" --> "0..*" CommandeStatut : possède historique
    Commande "1" --> "0..1" Avis : peut avoir
    User "1" --> "0..*" Avis : rédige
```

---

## Légende et explications

### Architecture en couches

Le diagramme reflète l'architecture **MVC/Service/Repository** du projet, organisée en 7 couches :

| Couche | Rôle | Composants |
|--------|------|-----------|
| **Core** | Infrastructure technique | `Router`, `Request`, `Response`, `Database`, `MongoDB` |
| **Middlewares** | Filtrage des requêtes | 6 middlewares (Auth, CSRF, CORS, RateLimit, Role, SecurityHeaders) |
| **Controllers** | Point d'entrée des requêtes API | 11 controllers (1 par domaine fonctionnel) |
| **Services** | Logique métier | 11 services (orchestration, règles de gestion) |
| **Repositories** | Accès aux données | 12 repositories (requêtes SQL/MongoDB) |
| **Validators** | Validation des entrées | 10 validators (données utilisateur) |
| **Models** | Entités de données | 7 models (représentation des tables) |
| **Exceptions** | Gestion d'erreurs | 6 exceptions métier typées |

### Flux d'une requête

```
Client HTTP
    │
    ▼
  Router ──▶ CorsMiddleware ──▶ SecurityHeadersMiddleware
                                        │
                                        ▼
                               CsrfMiddleware (POST/PUT/DELETE)
                                        │
                                        ▼
                               AuthMiddleware (routes protégées)
                                        │
                                        ▼
                               RoleMiddleware (routes admin/employé)
                                        │
                                        ▼
                               RateLimitMiddleware (routes sensibles)
                                        │
                                        ▼
                               Controller ──▶ Validator (validation)
                                        │
                                        ▼
                                    Service (logique métier)
                                        │
                                        ▼
                                   Repository (accès BDD)
                                        │
                                  ┌─────┴─────┐
                                  ▼           ▼
                               Database    MongoDB
                              (MySQL/PDO)  (NoSQL)
```

### Authentification — JWT en cookie HttpOnly

Le mécanisme d'authentification n'utilise **pas** de classe `JWTManager` séparée. Le JWT est géré par :

1. **`AuthService.generateToken()`** — Crée le JWT (HS256) via `firebase/php-jwt` avec payload `{iss, sub, role, iat, exp}`
2. **`AuthController.login()`** — Pose le token dans un cookie `authToken` (HttpOnly, Secure, SameSite)
3. **`AuthMiddleware.handle()`** — Lit le cookie, décode le JWT, enrichit `Request` avec les données utilisateur
4. **`CsrfService`** — Gère le cookie `csrfToken` (non-HttpOnly) pour le pattern Double Submit Cookie

Le frontend **ne lit/stocke jamais le JWT** : le navigateur transmet automatiquement le cookie avec `credentials: 'include'`.

### Injection de dépendances (PHP-DI)

Toutes les dépendances sont injectées via le conteneur **PHP-DI** configuré dans `backend/config/container.php`. Les classes ne créent pas leurs propres dépendances — elles les reçoivent en constructeur :

```
Container ──▶ PDO (MySQL)
           ──▶ MongoDB\Client
           ──▶ Monolog\Logger
           ──▶ Google Maps API Key
           ──▶ Config array
```

### Mappage Classes UML → Tables MySQL

| Classe UML (Model) | Table MySQL | Tables associées |
|---|---|---|
| `User` | `UTILISATEUR` | `RESET_TOKEN` |
| `Menu` | `MENU` | `IMAGE_MENU`, `MENU_MATERIEL`, `PROPOSE` (jonction plats) |
| `Commande` | `COMMANDE` | `COMMANDE_STATUT`, `COMMANDE_ANNULATION`, `COMMANDE_MODIFICATION`, `COMMANDE_MATERIEL` |
| `CommandeStatut` | `COMMANDE_STATUT` | — |
| `Avis` | `AVIS_FALLBACK` (MySQL) + collection `avis` (MongoDB) | — |
| `Horaire` | `HORAIRE` | — |
| `Materiel` | `MATERIEL` | `COMMANDE_MATERIEL`, `MENU_MATERIEL` |
| _(référence)_ | `PLAT` | `PLAT_ALLERGENE`, `PROPOSE` |
| _(référence)_ | `THEME` | — |
| _(référence)_ | `REGIME` | — |
| _(référence)_ | `ALLERGENE` | `PLAT_ALLERGENE` |
| _(référence)_ | `CONTACT` | — |

### Statistiques du diagramme

| Métrique | Quantité |
|----------|----------|
| **Controllers** | 11 |
| **Services** | 11 |
| **Repositories** | 12 |
| **Validators** | 10 |
| **Middlewares** | 6 |
| **Models** | 7 |
| **Exceptions** | 6 |
| **Core** | 5 |
| **Total classes** | **68** |
| **Relations** | **70+** |
| **Couverture MCD/MLD** | 100% (20 tables + 2 collections MongoDB) |

---

> Ce diagramme reflète le code réel du projet au 18 février 2026. Toutes les classes, méthodes et relations correspondent aux fichiers dans `backend/src/`.
