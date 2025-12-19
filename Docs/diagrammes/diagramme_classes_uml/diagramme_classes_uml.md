# Diagramme de Classes UML - Vite & Gourmand

```mermaid
classDiagram
    %% Classes abstraites et interfaces
    class Database {
        <<abstract>>
        -connection: PDO
        -host: string
        -dbname: string
        -user: string
        -password: string
        +connect()*
        +disconnect()*
        +executeQuery(sql, params)* PDOStatement
        +fetchOne(sql, params)* array
        +fetchAll(sql, params)* array
        +insert(table, data)* int
        +update(table, data, where)* int
        +delete(table, where)* int
    }

    class MongoDBConnection {
        <<abstract>>
        -connection: MongoDB\Client
        -database: MongoDB\Database
        -uri: string
        -dbname: string
        +connect()*
        +disconnect()*
        +getCollection(name)* MongoDB\Collection
        +insertOne(collection, document)* string
        +updateOne(collection, filter, document)* int
        +deleteOne(collection, filter)* int
        +findAll(collection, filter)* array
        +findOne(collection, filter)* array
    }

    %% Classes d'accès aux données (MySQL)
    class MySQLDatabase {
        -connection: PDO
        +__construct(host, dbname, user, password)
        +connect() void
        +disconnect() void
        +executeQuery(sql, params) PDOStatement
        +fetchOne(sql, params) array
        +fetchAll(sql, params) array
        +insert(table, data) int
        +update(table, data, where) int
        +delete(table, where) int
    }

    %% Classes d'accès aux données (MongoDB)
    class MongoDBClient {
        -client: MongoDB\Client
        -database: MongoDB\Database
        +__construct(uri, dbname)
        +connect() void
        +disconnect() void
        +getCollection(name) MongoDB\Collection
        +insertOne(collection, document) string
        +insertMany(collection, documents) array
        +updateOne(collection, filter, document) int
        +updateMany(collection, filter, document) int
        +deleteOne(collection, filter) int
        +findAll(collection, filter) array
        +findOne(collection, filter) array
        +countDocuments(collection, filter) int
    }

    %% Classe d'authentification
    class Auth {
        -db: MySQLDatabase
        -secretKey: string
        -tokenExpiry: int
        +__construct(db, secretKey)
        +register(email, password, firstName, lastName, phone, address) bool
        +login(email, password) object
        +generateToken(userId, role) string
        +verifyToken(token) object
        +refreshToken(token) string
        +logout(userId) bool
        +resetPasswordRequest(email) bool
        +resetPassword(token, newPassword) bool
        +hashPassword(password) string
        +verifyPassword(password, hash) bool
    }

    %% Classes métier - Entités
    class User {
        -id: int
        -email: string
        -firstName: string
        -lastName: string
        -phone: string
        -address: string
        -postalCode: string
        -role: string
        -passwordHash: string
        -isActive: bool
        -createdAt: DateTime
        -updatedAt: DateTime
        +__construct(db)
        +findById(id) User
        +findByEmail(email) User
        +getAll(filters) array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +changePassword(id, oldPassword, newPassword) bool
        +updateProfile(id, data) bool
        +getRole() string
    }

    class Menu {
        -id: int
        -title: string
        -description: string
        -theme: string
        -regime: string
        -minPersonnes: int
        -prixUnitaire: float
        -conditions: string
        -stock: int
        -images: array
        -createdAt: DateTime
        -updatedAt: DateTime
        -dishes: array
        -allergens: array
        +__construct(db)
        +findById(id) Menu
        +getAll(filters) array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +addDish(dishId) bool
        +removeDish(dishId) bool
        +getAllergenes() array
        +getAvailableDishes() array
        +decrementStock(quantity) bool
        +calculatePrice(personnes) float
        +isAvailable() bool
    }

    class Plat {
        -id: int
        -name: string
        -description: string
        -type: string
        -createdAt: DateTime
        -updatedAt: DateTime
        +__construct(db)
        +findById(id) Plat
        +getAll() array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +getAllergens() array
        +getMenusContaining() array
        +associateAllergen(allergenId) bool
        +removeAllergen(allergenId) bool
    }

    class Allergene {
        -id: int
        -label: string
        -description: string
        -createdAt: DateTime
        -updatedAt: DateTime
        +__construct(db)
        +findById(id) Allergene
        +getAll() array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +getPlatsByAllergen() array
    }

    class Theme {
        -id: int
        -label: string
        -description: string
        -createdAt: DateTime
        -updatedAt: DateTime
        +__construct(db)
        +findById(id) Theme
        +getAll() array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +getMenusByTheme() array
    }

    class Regime {
        -id: int
        -label: string
        -description: string
        -createdAt: DateTime
        -updatedAt: DateTime
        +__construct(db)
        +findById(id) Regime
        +getAll() array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +getMenusByRegime() array
    }

    class Commande {
        -id: int
        -userId: int
        -menuId: int
        -clientName: string
        -clientEmail: string
        -clientPhone: string
        -deliveryAddress: string
        -deliveryDate: DateTime
        -deliveryTime: string
        -personnes: int
        -status: string
        -totalPrice: float
        -prixMenuSnapshot: float
        -minPersonnesSnapshot: int
        -hasLoanedMaterial: bool
        -createdAt: DateTime
        -updatedAt: DateTime
        +__construct(db, mongodb)
        +findById(id) Commande
        +getByUserId(userId) array
        +getAll(filters) array
        +create(data) int
        +updateStatus(id, newStatus, notes) bool
        +calculateDeliveryPrice(address) float
        +applyDiscount(personnes) float
        +getTotalPrice() float
        +loanMaterial(materialIds) bool
        +returnMaterial() bool
        +canBeModified() bool
        +canBeCancelled() bool
        +cancel(reason, contactMode) bool
    }

    class Avis {
        -id: int
        -commandeId: int
        -userId: int
        -rating: int
        -comment: string
        -isValidated: bool
        -validatedBy: int
        -rejectionReason: string
        -createdAt: DateTime
        -validatedAt: DateTime
        +__construct(db, mongodb)
        +findById(id) Avis
        +getValidated() array
        +getByUserId(userId) array
        +getByCommande(commandeId) Avis
        +create(data) int
        +validate(avisId, validatedBy) bool
        +reject(avisId, reason) bool
        +update(id, data) bool
        +delete(id) bool
        +getAverageRating() float
        +getValidatedCount() int
        +syncToMongoDB(avisId) bool
        +archiveOldAvis() int
    }

    class Materiel {
        -id: int
        -name: string
        -description: string
        -quantity: int
        -replacementFee: float
        -condition: string
        -createdAt: DateTime
        -updatedAt: DateTime
        +__construct(db)
        +findById(id) Materiel
        +getAll() array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +isAvailable() bool
        +getAvailableQuantity() int
    }

    class ImageMenu {
        -id: int
        -menuId: int
        -url: string
        -altText: string
        -position: int
        -createdAt: DateTime
        -updatedAt: DateTime
        +__construct(db)
        +findById(id) ImageMenu
        +getByMenu(menuId) array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +reorder(menuId, newPositions) bool
    }

    class Horaire {
        -id: int
        -day: string
        -openingTime: string
        -closingTime: string
        -isClosed: bool
        -createdAt: DateTime
        -updatedAt: DateTime
        +__construct(db)
        +findByDay(day) Horaire
        +getAll() array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +isOpenToday() bool
        +getOpeningHours(day) array
    }

    class Contact {
        -id: int
        -title: string
        -description: string
        -email: string
        -isProcessed: bool
        -processedAt: DateTime
        -processedBy: int
        -createdAt: DateTime
        +__construct(db)
        +findById(id) Contact
        +getAll(filters) array
        +create(data) int
        +update(id, data) bool
        +markAsProcessed(id, processedBy) bool
        +delete(id) bool
        +getPending() array
    }

    class ResetToken {
        -id: int
        -userId: int
        -token: string
        -expiration: DateTime
        -isUsed: bool
        -usedAt: DateTime
        -createdAt: DateTime
        +__construct(db)
        +findByToken(token) ResetToken
        +create(userId) string
        +verify(token) bool
        +markAsUsed(token) bool
        +delete(id) bool
        +isExpired() bool
        +deleteExpiredTokens() int
    }

    class AvisFallback {
        -id: int
        -mongoId: string
        -userId: int
        -commandeId: int
        -menuId: int
        -rating: int
        -comment: string
        -validationStatus: string
        -moderatedBy: int
        -createdAt: DateTime
        -validatedAt: DateTime
        +__construct(db)
        +findById(id) AvisFallback
        +findByMongoId(mongoId) AvisFallback
        +getAll() array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +syncFromMongoDB(mongoAvis) bool
        +restoreToMongoDB(id) bool
    }

    class CommandeAnnulation {
        -id: int
        -commandeId: int
        -cancelledBy: int
        -contactMode: string
        -reason: string
        -cancelledAt: DateTime
        +__construct(db)
        +findById(id) CommandeAnnulation
        +getByCommande(commandeId) CommandeAnnulation
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
    }

    class CommandeModification {
        -id: int
        -commandeId: int
        -modifiedBy: int
        -fieldsModified: string
        -previousValues: object
        -newValues: object
        -modifiedAt: DateTime
        +__construct(db)
        +findById(id) CommandeModification
        +getByCommande(commandeId) array
        +create(data) int
        +update(id, data) bool
        +delete(id) bool
        +getChangeLog(commandeId) array
    }

    class Emprunt {
        -id: int
        -commandeId: int
        -materielId: int
        -quantity: int
        -loanDate: DateTime
        -expectedReturnDate: DateTime
        -actualReturnDate: DateTime
        -isReturned: bool
        -condition: string
        +__construct(db)
        +create(data) int
        +markAsReturned(id) bool
        +isOverdue() bool
        +getDaysOverdue() int
        +notifyOverdue() bool
        +getReplacementFee() float
    }

    class Historique {
        -id: int
        -commandeId: int
        -previousStatus: string
        -newStatus: string
        -changedBy: int
        -notes: string
        -changedAt: DateTime
        +__construct(db)
        +create(commandeId, previousStatus, newStatus, changedBy, notes) int
        +getByCommande(commandeId) array
        +getTimeline(commandeId) array
    }

    %% Classes pour stats et analytics (MongoDB)
    class StatistiquesCommandes {
        -commandeId: int
        -menuId: int
        -personnes: int
        -totalPrice: float
        -discount: float
        -deliveryFee: float
        -date: DateTime
        -status: string
        -userId: int
        +__construct(mongodb)
        +recordCommande(data) bool
        +getStatsByMenu(menuId, dateRange) array
        +getRevenueByMenu(menuId, dateRange) float
        +getTotalOrders(dateRange) int
        +getAverageOrderValue(dateRange) float
        +getOrdersTrend(dateRange) array
        +getMostPopularMenus(limit) array
    }

    %% Services métier
    class CommandeService {
        -commandeRepository: Commande
        -menuRepository: Menu
        -materielRepository: Materiel
        -statsRepository: StatistiquesCommandes
        -mailer: Mailer
        +__construct(db, mongodb, mailer)
        +createCommande(userData, menuId, personnes) int
        +updateCommandeStatus(commandeId, newStatus, notes) bool
        +cancelCommande(commandeId, reason, contactMode) bool
        +modifyCommande(commandeId, newData) bool
        +loanMaterialForCommande(commandeId, materialIds) bool
        +returnMaterialForCommande(commandeId) bool
        +validateCommande(commandeId) bool
        +getCommandeTimeline(commandeId) array
        +notifyUserOfStatusChange(commandeId, newStatus) bool
        +notifyMaterialOverdue(commandeId) bool
    }

    class AvisService {
        -avisRepository: Avis
        -commandeRepository: Commande
        -mongodb: MongoDBClient
        -mailer: Mailer
        +__construct(db, mongodb, mailer)
        +createAvis(userId, commandeId, rating, comment) int
        +validateAvis(avisId, validatedBy) bool
        +rejectAvis(avisId, reason) bool
        +getValidatedAvis() array
        +getAvisByMenu(menuId) array
        +getAverageRating(menuId) float
        +syncAvisToMongoDB(avisId) bool
        +archiveAvisToFallback() int
        +notifyUserValidation(avisId) bool
    }

    class MenuService {
        -menuRepository: Menu
        -platRepository: Plat
        -db: MySQLDatabase
        +__construct(db)
        +getMenusWithFilters(filters) array
        +getMenuDetails(menuId) Menu
        +createMenu(data) int
        +updateMenu(id, data) bool
        +deleteMenu(id) bool
        +associateDishesToMenu(menuId, dishIds) bool
        +getMenusByTheme(theme) array
        +getMenusByRegime(regime) array
        +searchMenus(keyword) array
        +getAvailableMenus() array
    }

    class UserService {
        -userRepository: User
        -authRepository: Auth
        -mailer: Mailer
        +__construct(db, mailer)
        +registerUser(data) int
        +loginUser(email, password) object
        +updateUserProfile(userId, data) bool
        +resetPassword(email) bool
        +changePassword(userId, oldPassword, newPassword) bool
        +disableUserAccount(userId) bool
        +getUser(userId) User
        +sendWelcomeEmail(userId) bool
        +sendPasswordResetEmail(email, token) bool
    }

    class Mailer {
        -smtpHost: string
        -smtpPort: int
        -smtpUser: string
        -smtpPassword: string
        +__construct(config)
        +sendMail(to, subject, body, htmlBody) bool
        +sendWelcomeEmail(email, firstName) bool
        +sendOrderConfirmation(email, commandeId) bool
        +sendStatusChangeNotification(email, commandeId, status) bool
        +sendPasswordResetLink(email, token) bool
        +sendMaterialOverdueNotice(email, commandeId) bool
        +sendValidationNotification(email, avisId) bool
    }

    %% Middleware et utilitaires
    class JWTManager {
        -secretKey: string
        -algorithm: string
        -expiry: int
        +__construct(secretKey, expiry)
        +encode(payload) string
        +decode(token) object
        +verify(token) bool
        +isExpired(token) bool
        +refresh(token) string
    }

    class Validator {
        <<utility>>
        +validateEmail(email)$ bool
        +validatePassword(password)$ bool
        +validatePhone(phone)$ bool
        +validateAddress(address)$ bool
        +sanitizeInput(input)$ string
        +validateDate(date, format)$ bool
        +validateRating(rating)$ bool
        +validateMinPersonnes(personnes, min)$ bool
    }

    class Logger {
        <<utility>>
        -logFile: string
        +__construct(logFile)
        +info(message)* void
        +warning(message)* void
        +error(message)* void
        +debug(message)* void
        +logAction(userId, action, details)* void
    }

    %% Relations
    Database <|-- MySQLDatabase
    MongoDBConnection <|-- MongoDBClient
    
    Auth --> MySQLDatabase: utilise
    Auth --> JWTManager: utilise
    Auth --> Validator: utilise
    Auth --> ResetToken: crée
    
    User --> MySQLDatabase: utilise
    User --> Validator: utilise
    User --> ResetToken: possède 0..*
    
    Theme --> MySQLDatabase: utilise
    Regime --> MySQLDatabase: utilise
    
    Menu --> MySQLDatabase: utilise
    Menu --> Theme: référence 1
    Menu --> Regime: référence 1
    Menu --> Plat: contient 0..*
    Menu --> ImageMenu: possède 0..*
    Menu --> Allergene: contient indirectement 0..*
    
    Plat --> MySQLDatabase: utilise
    Plat --> Allergene: contient 0..*
    
    Allergene --> MySQLDatabase: utilise
    
    ImageMenu --> MySQLDatabase: utilise
    ImageMenu --> Menu: appartient à
    
    Horaire --> MySQLDatabase: utilise
    
    Contact --> MySQLDatabase: utilise
    Contact --> User: envoyé par (optionnel)
    
    ResetToken --> MySQLDatabase: utilise
    
    Commande --> MySQLDatabase: utilise
    Commande --> MongoDBClient: utilise
    Commande --> User: passe par
    Commande --> Menu: comprend 1
    Commande --> Historique: génère
    Commande --> Emprunt: peut avoir 0..*
    Commande --> CommandeAnnulation: peut avoir 0..1
    Commande --> CommandeModification: peut avoir 0..*
    
    Materiel --> MySQLDatabase: utilise
    
    Emprunt --> MySQLDatabase: utilise
    Emprunt --> Materiel: emprunte
    Emprunt --> Commande: appartient à
    
    Historique --> MySQLDatabase: utilise
    Historique --> User: modifié par
    
    CommandeAnnulation --> MySQLDatabase: utilise
    CommandeAnnulation --> Commande: annule
    
    CommandeModification --> MySQLDatabase: utilise
    CommandeModification --> Commande: modifie
    
    Avis --> MySQLDatabase: utilise
    Avis --> MongoDBClient: utilise
    Avis --> Commande: évalue 1
    Avis --> User: rédigé par
    
    AvisFallback --> MySQLDatabase: utilise
    AvisFallback --> User: lié à
    AvisFallback --> Commande: lié à
    AvisFallback --> Menu: lié à
    
    StatistiquesCommandes --> MongoDBClient: utilise
    StatistiquesCommandes --> Commande: analyse
    
    CommandeService --> Commande: utilise
    CommandeService --> Menu: utilise
    CommandeService --> Materiel: utilise
    CommandeService --> Emprunt: utilise
    CommandeService --> StatistiquesCommandes: utilise
    CommandeService --> Mailer: utilise
    CommandeService --> Historique: utilise
    CommandeService --> CommandeAnnulation: utilise
    CommandeService --> CommandeModification: utilise
    CommandeService --> Validator: utilise
    
    AvisService --> Avis: utilise
    AvisService --> AvisFallback: utilise
    AvisService --> Commande: vérifie
    AvisService --> MongoDBClient: synchronise vers
    AvisService --> Mailer: utilise
    
    MenuService --> Menu: utilise
    MenuService --> Plat: utilise
    MenuService --> Theme: utilise
    MenuService --> Regime: utilise
    MenuService --> Allergene: utilise
    MenuService --> ImageMenu: utilise
    MenuService --> Validator: utilise
    
    UserService --> User: utilise
    UserService --> Auth: utilise
    UserService --> ResetToken: utilise
    UserService --> Mailer: utilise
    UserService --> Validator: utilise
    
    Mailer --> Logger: enregistre
    
    CommandeService --> Logger: utilise
    AvisService --> Logger: utilise
    UserService --> Logger: utilise
    MenuService --> Logger: utilise
```

---

## 📝 **Légende et Explications**

### **Hiérarchie des Classes**

| Type | Catégorie | Classes |
|------|-----------|---------|
| **Abstractions DB** | Core | Database, MongoDBConnection |
| **Implémentations DB** | Core | MySQLDatabase, MongoDBClient |
| **Référentiels (Repository)** | Données | User, Menu, Plat, Allergene, Theme, Regime, Commande, Avis, AvisFallback, Materiel, Emprunt, Historique, ImageMenu, Horaire, Contact, ResetToken, CommandeAnnulation, CommandeModification |
| **Métier (Service)** | Logique | Auth, CommandeService, AvisService, MenuService, UserService |
| **Analytics** | NoSQL | StatistiquesCommandes |
| **Utilitaires** | Support | JWTManager, Validator, Mailer, Logger |

### **Mappage MCD/MLD → Classes UML**

| Entité MCD/MLD | Classe UML | Type |
|---|---|---|
| UTILISATEUR | User | Repository |
| MENU | Menu | Repository |
| PLAT | Plat | Repository |
| THEME | Theme | Repository |
| REGIME | Regime | Repository |
| ALLERGENE | Allergene | Repository |
| ALLERGENE (N,M PLAT) | _(géré par Plat)_ | Association |
| IMAGE_MENU | ImageMenu | Repository |
| HORAIRE | Horaire | Repository |
| CONTACT | Contact | Repository |
| COMMANDE | Commande | Repository |
| COMMANDE_STATUT | Historique | Repository |
| COMMANDE_ANNULATION | CommandeAnnulation | Repository |
| COMMANDE_MODIFICATION | CommandeModification | Repository |
| MATERIEL | Materiel | Repository |
| EMPRUNT (N,M COMMANDE-MATERIEL) | Emprunt | Repository |
| AVIS | Avis | Repository |
| AVIS_FALLBACK | AvisFallback | Repository |
| RESET_TOKEN | ResetToken | Repository |
| Statistics (MongoDB) | StatistiquesCommandes | Analytics |

---

## 🔗 **Flux de Dépendances Principaux**

1. **Authentification & Tokens** 
   - Auth → JWTManager → Validator
   - ResetToken pour réinitialisation
   - User gère les profils

2. **Gestion des Menus** 
   - MenuService → Menu → Plat → Allergene
   - Menu liés à Theme + Regime
   - ImageMenu pour galeries

3. **Gestion des Commandes** 
   - CommandeService → Commande → Historique + CommandeAnnulation + CommandeModification
   - Emprunt pour matériel prêté
   - Snapshot de prix/quantités pour immuabilité

4. **Gestion des Avis** 
   - AvisService → Avis ↔ AvisFallback (sync MySQL/MongoDB)
   - StatistiquesCommandes pour analytics MongoDB

5. **Support Métier**
   - Horaire pour heures d'ouverture
   - Contact pour formulaire de contact
   - Mailer pour notifications

6. **Logging Centralisé**
   - Tous les services → Logger pour traçabilité

---

## 💾 **Séparation des Responsabilités**

| Pattern | Application |
|---------|------------|
| **Repository Pattern** | Chaque entité gère son accès DB + CRUD |
| **Service Pattern** | Logique métier encapsulée (CommandeService, AvisService, etc.) |
| **Dependency Injection** | Services reçoivent dépendances en constructeur |
| **Singleton** | JWTManager, Validator, Logger (réutilisables) |
| **Adapter Pattern** | MySQLDatabase + MongoDBClient → interfaces Database/MongoDBConnection |

---

## 📊 **Statistiques du Diagramme**

- **18 Classes Repository** (toutes entités MCD/MLD)
- **4 Classes Service** (logique métier)
- **2 Classes Abstraites** (interfaces DB)
- **2 Implémentations DB** (MySQL + MongoDB)
- **4 Classes Utilitaires** (Auth, JWT, Validation, Email)
- **70+ Relations** bidirectionnelles documentées
- **100% de couverture MCD/MLD** ✅
