# 📚 Documentation Technique - Vite & Gourmand

**Date :** 11 décembre 2025  
**Version :** 1.0.0  
**Auteur :** FastDev Team  
**Statut :** En cours de développement

---

## 📋 Table des Matières

1. [Choix Technologiques](#choix-technologiques)
2. [Architecture Générale](#architecture-générale)
3. [Modèle de Données](#modèle-de-données)
4. [Architecture OOP](#architecture-oop)
5. [Sécurité](#sécurité)
6. [API Géolocalisation](#api-géolocalisation)
7. [Dual Database](#dual-database)
8. [Flux Métier](#flux-métier)
9. [Performance](#performance)
10. [Maintenance](#maintenance)

---

## 🏗️ Choix Technologiques

### 1️⃣ Backend : PHP 8.0+ (Vanilla OOP)

**Décision :** PHP sans framework (Symfony, Laravel)

**Justification :**
| Avantage | Détail |
|----------|--------|
| ✅ **Simplicité** | Pas de dépendance framework heavy = code transparent |
| ✅ **Apprentissage** | Pour un junior = moins d'abstraction à maîtriser |
| ✅ **Flexibilité** | Architecture libre = peut évoluer facilement |
| ✅ **Déploiement** | Moins de ressources que framework lourd |
| ✅ **Sécurité** | Total contrôle = moins de vuln cache |

**Contre-partie :**
- ❌ Plus de code boilerplate (pas de helper ORM)
- ❌ Responsabilité manuelle de sécurité
- ❌ Pas de routing/validation auto

**Mitigation :**
- Utiliser pattern Repository + Service
- Validation manuelle mais stricte
- Logging complète
- Prepared statements systématiquement

### 2️⃣ Frontend : HTML5/CSS3/JavaScript Vanilla

**Décision :** Pas de framework (Vue, React, Angular)

**Justification :**
| Aspect | Choix | Raison |
|--------|-------|--------|
| **Technologie** | JavaScript Vanilla | Fetch API suffit pour async calls |
| **Build** | Aucun build tool | Pas de webpack/babel = direct au navigateur |
| **CSS** | CSS3 Grid/Flexbox | Responsive sans Tailwind |
| **Compatibilité** | IE11+ | Utiliser polyfills si nécessaire |

**Approche :**
```javascript
// Au lieu de Vue/React, utiliser Fetch + DOM APIs
fetch('/api/commandes', { method: 'GET', headers: { 'Authorization': 'Bearer ' + token } })
  .then(r => r.json())
  .then(data => { document.getElementById('list').innerHTML = renderHTML(data); })
  .catch(e => console.error(e));
```

**Avantages :**
- ✅ Zero dépendance JavaScript
- ✅ Chargement très rapide
- ✅ Pas de compilation
- ✅ Facile à debug (DevTools native)

**Limitation :**
- ❌ Plus de DOM manipulation manuelle
- ❌ Pas de réactivité automatique
- ❌ State management manuel

**Mitigation :**
- Créer utilitaires (helpers) pour DOM
- Utiliser data-attributes pour state
- Convention de nommage classes stricte

### 3️⃣ Database Relationnelle : MySQL 8.0+

**Décision :** MySQL (pas PostgreSQL, SQLite)

**Justification :**
| Critère | MySQL | PostgreSQL | SQLite |
|---------|-------|-----------|--------|
| **Stabilité** | ✅ Excellent | ✅ Excellent | ❌ Desktop |
| **Scalabilité** | ✅ Bon | ✅ Très bon | ❌ Limité |
| **ACID** | ✅ InnoDB | ✅ Natif | ❌ Partiel |
| **JSON** | ✅ Support | ✅ Support | ❌ Pas natif |
| **Coût** | ✅ Gratuit | ✅ Gratuit | ✅ Gratuit |
| **Hosting** | ✅ Partout | ❌ Moins commun | ❌ N/A |

**Choix MySQL car :**
- ✅ Présent sur quasi tous les serveurs
- ✅ Suffisant pour Vite & Gourmand (< 100k commandes/mois)
- ✅ Replication master-slave facile en prod
- ✅ InnoDB = transactions ACID complètes

### 4️⃣ Database NoSQL : MongoDB 4.4+ (Analytics)

**Décision :** MongoDB pour analytics uniquement

**Architecture :**
```
MySQL (transactionnel)  ←→  MongoDB (analytics)
├─ COMMANDE            └─ statistiques_commandes
├─ AVIS                └─ avis (validated only)
└─ HISTORIQUE
```

**Justification :**

| Use Case | MySQL | MongoDB |
|----------|-------|---------|
| **Commandes** | ✅ ACID required | ❌ Loose |
| **Statistiques** | ❌ Slow aggregates | ✅ MapReduce |
| **CA par menu** | ❌ Complex query | ✅ Simple lookup |
| **Avis publics** | ❌ Avec perfs | ✅ Rapide |

**Implémentation :**
- CommandeService : INSERT MySQL PUIS sync MongoDB
- AvisService : Validation → MySQL UPDATE → MongoDB INSERT
- MongoDB fallback : Si MongoDB down → Utiliser AVIS_FALLBACK (MySQL)

**Bénéfices :**
- ✅ Analytics en temps réel sans charger MySQL
- ✅ Flexibilité schéma (pas de migration)
- ✅ Scalabilité horizontale (replica sets)
- ✅ Fallback sécurisé

### 5️⃣ Authentication : JWT Tokens

**Décision :** JWT (JSON Web Tokens) vs Sessions

| Aspect | JWT | Session |
|--------|-----|---------|
| **Stateless** | ✅ Oui | ❌ Serveur stocke |
| **Scalabilité** | ✅ Facile | ❌ Shared memory |
| **Mobile API** | ✅ Parfait | ❌ CORS complex |
| **Sécurité** | ✅ Si HTTPS | ⚠️ CSRF risk |
| **Revocation** | ❌ Difficulty | ✅ Immédiat |

**Implémentation :**
```php
// Login
$token = Auth::generateToken($userId, $userRole);
// Frontend stocke en localStorage
// Chaque requête : Authorization: Bearer $token

// Logout
// Frontend supprime localStorage
// Ou : Liste noire JWT si token pas expiré

// Refresh
// Avant expiration (24h) : POST /refresh-token
```

**Sécurité :**
- ✅ Token signé avec HS256 (HMAC) ou RS256 (RSA)
- ✅ Expiration automatique (24h)
- ✅ Stored in localStorage (accessible JS mais HTTPS only)
- ✅ HttpOnly cookies pour sensible (contre XSS)

### 6️⃣ API Géolocalisation : Google Maps API

**Décision :** API externe vs estimation simple

| Approche | Précision | Coût | Fiabilité |
|----------|-----------|------|-----------|
| **Google Maps** | ✅ Réelle (route) | ⚠️ Payante | ✅ 99.9% |
| **Estimation** | ❌ ±10km | ✅ Gratuit | ✅ 100% |
| **OpenStreetMap** | ✅ Route libre | ✅ Gratuit | ⚠️ 95% |

**Choix :** API avec fallback estimation

```php
// Try API
try {
    $distance = GoogleMapsAPI::distance(
        $userAddress, 
        'Bordeaux',
        env('GOOGLE_MAPS_API_KEY')
    );
} catch (ApiException $e) {
    // Fallback estimation
    $distance = estimateDistance($userAddress);
    Log::warning("Geolocation API failed, using estimation");
}
// Calcul frais = 5 + (distance * 0.59)
```

**Bénéfices :**
- ✅ Précision réelle pour clients
- ✅ Fallback = jamais bloqué
- ✅ Coût minime (1000 requêtes gratuites/jour)
- ✅ Production-ready

---

## 🏛️ Architecture Générale

### Diagram Couches

```
┌─────────────────────────────────────┐
│         FRONTEND                    │
│  HTML5 / CSS3 / JavaScript Vanilla  │
│  (Responsive Design + AJAX)         │
└──────────────┬──────────────────────┘
               │ HTTP/JSON
┌──────────────▼──────────────────────┐
│      API REST (HTTP Endpoints)      │
│  /api/commandes, /api/menus, etc    │
└──────────────┬──────────────────────┘
               │ Routes
┌──────────────▼──────────────────────┐
│    Controllers (Route Handlers)     │
│  - Request validation               │
│  - Call Services                    │
│  - Response formatting              │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│    Services (Business Logic)        │
│  - CommandeService                  │
│  - AvisService                      │
│  - MenuService                      │
│  - UserService                      │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│  Repositories (Data Access)         │
│  - CommandeRepository               │
│  - AvisRepository                   │
│  - MenuRepository                   │
│  - UserRepository                   │
└──────────────┬──────────────────────┘
               │
      ┌────────┴────────┐
      │                 │
┌─────▼────┐   ┌────────▼──────┐
│   MySQL  │   │    MongoDB    │
│  (ACID)  │   │  (Analytics)  │
└──────────┘   └───────────────┘
```

### Flux Requête Utilisateur

```
1. Frontend envoie request
   fetch('/api/commandes', { method: 'POST', body: JSON.stringify(data) })

2. Router reçoit, match route
   /api/commandes → CommandeController::create

3. Controller valide input
   if (!validate($data)) throw BadRequest()

4. Controller appelle Service
   $service->createCommande($data)

5. Service exécute logique métier
   - Vérify user exists
   - Calculate price (réduction, frais)
   - Create snapshots
   - Prepare data

6. Service appelle Repository
   $repo->create($commandeData)

7. Repository persiste
   INSERT INTO commandes (...)
   INSERT INTO historique (...)

8. Repository retourne ID
   return $commandeId

9. Service sync MongoDB (optionnel)
   MongoDB::insertOne('statistiques_commandes', {...})

10. Service envoie email
    Mailer::send('order-confirmation', ...)

11. Service retourne résultat
    return ['success' => true, 'commandeId' => $id]

12. Controller formate réponse
    return response()->json(['success' => true, ...], 201)

13. Frontend affiche succès
    Show confirmation, redirect to dashboard
```

---

## 🗄️ Modèle de Données

### Schéma Global

**17 Tables Principales :**

| Table | Purpose | Clés |
|-------|---------|------|
| **UTILISATEUR** | Authentification + profil | PK: id_utilisateur |
| **RESET_TOKEN** | Password reset | FK: id_utilisateur |
| **MENU** | Catalogue menus | FK: id_theme, id_regime |
| **PLAT** | Dishes library | - |
| **PROPOSE** | Menu ↔ Plat | FK: id_menu, id_plat |
| **THEME** | Menu categories | - |
| **REGIME** | Dietary options | - |
| **ALLERGENE** | Allergen list | - |
| **PLAT_ALLERGENE** | Plat ↔ Allergene | FK: id_plat, id_allergene |
| **IMAGE_MENU** | Menu gallery | FK: id_menu |
| **COMMANDE** | Orders + pricing | FK: id_utilisateur, id_menu |
| **HISTORIQUE** | Order status timeline | FK: id_commande, id_utilisateur |
| **MATERIEL** | Loaned equipment | - |
| **COMMANDE_MATERIEL** | Commande ↔ Materiel | FK: id_commande, id_materiel |
| **AVIS** | User reviews (with moderation) | FK: id_utilisateur, id_commande |
| **AVIS_FALLBACK** | MongoDB fallback | - |
| **HORAIRE** | Business hours | - |
| **CONTACT** | Contact form submissions | - |

### Snapshots Pricing

**Concept :** Gel le prix du menu au moment de la commande

```sql
COMMANDE table :
├─ id_menu                    (menu commandé)
├─ prix_menu_unitaire         (prix du menu SNAPSHOT)
├─ nombre_personne_min_snapshot (min SNAPSHOT)
├─ nombre_personnes           (qty commandée)
├─ montant_reduction          (10% si applicable)
├─ frais_livraison            (5 + 0.59/km)
└─ prix_total                 (prix final)

Avantage :
- Menu prix change demain
- Commande d'hier conserve son prix
- Immuabilité = satisfaction client
```

### Historique Traçabilité

```sql
HISTORIQUE table :
├─ id_commande
├─ previousStatus           (état avant)
├─ newStatus                (état après)
├─ changedBy                (id utilisateur/employé)
├─ notes                    (motif si annulation)
└─ changedAt                (timestamp)

Timeline Complète :
2025-01-01 10:00 - EN_ATTENTE (Système) [creation]
2025-01-01 11:30 - ACCEPTE (Marie - Employé) [manuel]
2025-01-01 14:00 - EN_PREPARATION (Jean - Employé) [manuel]
2025-01-02 09:00 - EN_LIVRAISON (Logistique) [manuel]
2025-01-02 14:30 - LIVRE (Logistique) [manuel]
```

### Règles de Gestion (30+ RG)

| RG | Règle | Impact |
|----|-------|--------|
| **RG1** | User = 1 rôle unique | Validation CREATE user |
| **RG2** | Utilisateur soft-delete | UPDATE actif=false |
| **RG3** | Réduction 10% si pers >= min+5 | CommandeService::calculatePrice() |
| **RG4** | Frais 5€ + 0,59€/km hors Bordeaux | CommandeService::calculateDeliveryFees() |
| **RG5** | Snapshots immuables | INSERT avec snapshot_fields |
| **RG6** | 8 statuts commande | ENUM validation |
| **RG7** | Annulation si EN_ATTENTE | User level |
| **RG8** | Modification (sauf menu) si EN_ATTENTE | User level |
| **RG9** | Matériel = 10j deadline | Cron job alert |
| **RG10** | Matériel non retourné = 600€ penalty | Email + note commande |
| **RG11** | Avis validés seulement publics | WHERE isValidated=true |
| **RG12** | Employé ne peut créer Admin | Code check |
| **RG13** | Contact → email entreprise | Mailer |
| **RG14** | Password min 10 chars + majuscule+minuscule+chiffre+spécial | Frontend + Backend validation |

---

## 🎭 Architecture OOP

### Pattern Repository

**Concept :** Chaque entité = 1 Repository (accès données isolé)

```php
// src/Repositories/CommandeRepository.php
class CommandeRepository {
    private MySQLDatabase $db;
    
    public function __construct(MySQLDatabase $db) {
        $this->db = $db;
    }
    
    // CRUD
    public function create(array $data) : int { ... }
    public function findById(int $id) : ?Commande { ... }
    public function findByUserId(int $userId) : array { ... }
    public function update(int $id, array $data) : bool { ... }
    public function delete(int $id) : bool { ... }
    
    // Spécifiques
    public function findByStatus(string $status) : array { ... }
    public function findByDateRange(DateTime $start, DateTime $end) : array { ... }
}
```

**Avantages :**
- ✅ Data access centralisé
- ✅ Testable
- ✅ Remplacement DB facile
- ✅ Réutilisable

### Pattern Service

**Concept :** Logique métier = 1 Service (utilise Repositories)

```php
// src/Services/CommandeService.php
class CommandeService {
    private CommandeRepository $commandeRepo;
    private MenuRepository $menuRepo;
    private Mailer $mailer;
    
    public function __construct(
        CommandeRepository $commandeRepo,
        MenuRepository $menuRepo,
        Mailer $mailer
    ) {
        $this->commandeRepo = $commandeRepo;
        $this->menuRepo = $menuRepo;
        $this->mailer = $mailer;
    }
    
    public function createCommande(array $data) : int {
        // 1. Valider
        if (!$this->validate($data)) throw new InvalidData();
        
        // 2. Récup menu (pour snapshots)
        $menu = $this->menuRepo->findById($data['menu_id']);
        
        // 3. Calculer prix
        $pricing = $this->calculatePrice(
            $menu->prix,
            $menu->minPersonnes,
            $data['personnes'],
            $data['adresse']
        );
        
        // 4. Préparer données avec snapshots
        $commande = [
            'user_id' => $data['user_id'],
            'menu_id' => $data['menu_id'],
            'personnes' => $data['personnes'],
            'prix_menu_unitaire' => $menu->prix,              // SNAPSHOT
            'nombre_personne_min_snapshot' => $menu->minPersonnes,  // SNAPSHOT
            'montant_reduction' => $pricing['reduction'],
            'frais_livraison' => $pricing['deliveryFees'],
            'prix_total' => $pricing['total'],
            'status' => 'EN_ATTENTE',
        ];
        
        // 5. Créer en MySQL
        $commandeId = $this->commandeRepo->create($commande);
        
        // 6. Historique
        $this->commandeRepo->insertHistorique([
            'commande_id' => $commandeId,
            'newStatus' => 'EN_ATTENTE',
            'changedAt' => now(),
        ]);
        
        // 7. Sync MongoDB
        $this->syncMongoDBStatistics($commandeId);
        
        // 8. Email confirmation
        $this->mailer->send('order-confirmation', ['commandeId' => $commandeId]);
        
        return $commandeId;
    }
    
    private function calculatePrice(
        float $basePrice, 
        int $minPersonnes, 
        int $personnes,
        string $address
    ) : array {
        $subtotal = $basePrice * $personnes;
        
        // Réduction 10% si pers >= min+5
        $reduction = ($personnes >= $minPersonnes + 5) ? $subtotal * 0.10 : 0;
        
        // Frais livraison
        $isOutsideBordeaux = !$this->isInBordeaux($address);
        $deliveryFees = 0;
        if ($isOutsideBordeaux) {
            $distance = $this->getDistance($address, 'Bordeaux');  // API
            $deliveryFees = 5 + ($distance * 0.59);
        }
        
        return [
            'reduction' => $reduction,
            'deliveryFees' => $deliveryFees,
            'total' => ($subtotal - $reduction) + $deliveryFees,
        ];
    }
}
```

**Avantages :**
- ✅ Logique métier centralisée
- ✅ Testable en isolation
- ✅ Réutilisable par Controllers/APIs
- ✅ Facile à maintenir

### Injection de Dépendances

**Concept :** Classes reçoivent dépendances en constructor (pas new)

```php
// BAD
class CommandeService {
    private CommandeRepository $repo;
    public function __construct() {
        $this->repo = new CommandeRepository();  // ❌ Tight coupling
    }
}

// GOOD
class CommandeService {
    private CommandeRepository $repo;
    public function __construct(CommandeRepository $repo) {
        $this->repo = $repo;  // ✅ Dependency injection
    }
}

// Usage
$repo = new CommandeRepository($db);
$service = new CommandeService($repo);
$service->createCommande($data);
```

**Avantages :**
- ✅ Loose coupling
- ✅ Testable (mock repos facilement)
- ✅ Configuration flexible
- ✅ Composition over inheritance

---

## 🔐 Sécurité

### 1. Password Hashing

```php
// Register
$hash = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,      // 64 MB
    'time_cost' => 4,
    'threads' => 3
]);
// Stocke $hash en BDD (jamais le password!)

// Login
if (password_verify($inputPassword, $storedHash)) {
    // Correct
} else {
    // Wrong
}
```

**Avantages :**
- ✅ Argon2 = résistant aux attacks GPU
- ✅ Salting automatique
- ✅ Adaptive (peut augmenter coût si CPU progresse)

### 2. JWT Tokens

```php
// Generate token
$payload = [
    'sub' => $userId,
    'role' => $userRole,
    'iat' => time(),
    'exp' => time() + 86400,  // 24h
];
$token = JWT::encode($payload, env('JWT_SECRET'), 'HS256');

// Header: Authorization: Bearer $token

// Verify
try {
    $decoded = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
    $userId = $decoded->sub;
    $role = $decoded->role;
} catch (ExpiredException $e) {
    // Token expiré
} catch (SignatureInvalidException $e) {
    // Token invalide
}
```

### 3. Input Validation

```php
// Validator class
class Validator {
    public static function email(string $email) : bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function password(string $pwd) : bool {
        // 10 chars min + majuscule + minuscule + chiffre + spécial
        return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*])(.{10,})$/', $pwd);
    }
    
    public static function integer(mixed $value) : bool {
        return is_int($value) || (is_string($value) && ctype_digit($value));
    }
}

// Usage
if (!Validator::email($email)) throw new InvalidEmail();
if (!Validator::password($password)) throw new WeakPassword();
```

### 4. SQL Prepared Statements

```php
// BAD ❌
$query = "SELECT * FROM users WHERE email = '$email'";  // SQL injection!

// GOOD ✅
$query = "SELECT * FROM users WHERE email = ?";
$result = $db->prepare($query)->execute([$email]);
```

### 5. API Key Security

```env
# .env (never commit!)
GOOGLE_MAPS_API_KEY=AIzaSyD...xxxxx
```

```php
// Code
$apiKey = env('GOOGLE_MAPS_API_KEY');
// ✅ Jamais exposé frontend
// ✅ Stocké variables env
// ✅ Rotation facile
```

### 6. CSRF Protection

```html
<!-- Form -->
<form method="POST" action="/api/commandes">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="text" name="adresse" required>
    <button type="submit">Commander</button>
</form>
```

```php
// Backend
if ($_POST['csrf_token'] !== session('csrf_token')) {
    throw new CsrfTokenMismatch();
}
```

### 7. HTTPS & HSTS

```apache
# Apache config
# Forcer HTTPS
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

# HSTS Header (1 year)
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

### 8. RGPD Compliance

✅ **Soft Delete :** Jamais delete réel, UPDATE actif=false  
✅ **Historique :** Tracer chaque changement  
✅ **Consentement :** Checkbox acceptation CGV/Politique confidentialité  
✅ **Data Export :** User peut télécharger ses données  
✅ **Right to Forget :** Anonymiser user (non supprimer, RGPD)  

---

## 🌍 API Géolocalisation

### Implémentation Fallback

```php
class GeoLocationService {
    private string $apiKey;
    private int $timeout;
    
    public function __construct(string $apiKey, int $timeout = 5000) {
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }
    
    public function getDistance(string $from, string $to) : float {
        try {
            // API Call
            $response = $this->callGoogleMapsAPI($from, $to);
            $distance = $response['distance_km'];
            
            // Log success
            Logger::info("Distance calculated", ['from' => $from, 'to' => $to, 'km' => $distance]);
            
            return $distance;
            
        } catch (ApiTimeoutException | ApiException $e) {
            // FALLBACK
            Logger::warning("Geolocation API failed, using estimation", ['error' => $e->getMessage()]);
            return $this->estimateDistance($from, $to);
        }
    }
    
    private function callGoogleMapsAPI(string $from, string $to) : array {
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json";
        $params = [
            'origins' => $from,
            'destinations' => $to,
            'key' => $this->apiKey,
            'units' => 'metric',
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url . '?' . http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout / 1000,
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode !== 200) {
            throw new ApiException("Google Maps API returned $httpCode");
        }
        
        $data = json_decode($response, true);
        
        if ($data['status'] !== 'OK') {
            throw new ApiException("API status: " . $data['status']);
        }
        
        $distanceMeters = $data['rows'][0]['elements'][0]['distance']['value'];
        return ['distance_km' => $distanceMeters / 1000];
    }
    
    private function estimateDistance(string $from, string $to) : float {
        // Simple estimation based on postal codes
        // À améliorer avec géocodage local
        $bordelayPostals = ['33000', '33100', '33200', '33300'];
        $fromPostal = substr($from, -5);
        
        if (in_array($fromPostal, $bordelayPostals)) {
            return 0;  // Bordeaux = 0km
        }
        
        // Estimation basique (à affiner)
        return 15;  // ±15km moyenne
    }
}
```

**Utilisation dans CommandeService :**

```php
public function createCommande(array $data) {
    // ...
    $distance = $this->geoLocationService->getDistance(
        $data['adresse'],
        'Bordeaux'
    );
    
    $deliveryFees = 0;
    if ($distance > 0) {  // Hors Bordeaux
        $deliveryFees = 5 + ($distance * 0.59);
    }
    // ...
}
```

---

## 💾 Dual Database

### Architecture Sync

```
User creates commande
         ↓
MySQL INSERT commande
         ↓
MySQL INSERT historique
         ↓
Try: MongoDB INSERT statistiques
     ↓
     If error: Log + Continue (fallback)
         ↓
Return success to user
(MySQL guaranteed, MongoDB best-effort)
```

**Code :**

```php
class CommandeService {
    private MongoDBClient $mongodb;
    private MySQLDatabase $mysql;
    private Logger $logger;
    
    public function createCommande(array $data) {
        // 1. MySQL (guaranteed)
        $commandeId = $this->mysql->insert('commandes', $data);
        
        // 2. MongoDB (best-effort)
        try {
            $this->mongodb->insert('statistiques_commandes', [
                'commandeId' => $commandeId,
                'menuId' => $data['menu_id'],
                'personnes' => $data['personnes'],
                'totalPrice' => $data['prix_total'],
                'createdAt' => now(),
            ]);
        } catch (MongoException $e) {
            $this->logger->warning("MongoDB sync failed", ['error' => $e]);
            // Continue! User's commande is in MySQL = safe
        }
        
        return $commandeId;
    }
}
```

**Fallback Avis :**

```php
class AvisService {
    public function getPublicAvis() : array {
        try {
            // Try MongoDB (fast)
            return $this->mongodb->find('avis', ['isValidated' => true]);
        } catch (MongoException $e) {
            // Fallback MySQL
            $this->logger->warning("MongoDB down, using MySQL fallback");
            return $this->mysql->query(
                "SELECT * FROM avis_fallback WHERE isValidated = true"
            );
        }
    }
}
```

---

## 🔄 Flux Métier

### Cycle de Vie Commande

```
[1] Utilisateur crée
    Status: EN_ATTENTE
    Email confirmation
    
[2] Employé accepte
    Status: ACCEPTE
    Historique + Email
    
[3] Employé prépare
    Status: EN_PREPARATION
    Email
    
[4] Logistique expédie
    Status: EN_LIVRAISON
    Email
    
[5] Client reçoit
    Status: LIVRE
    Email
    
[6a] Si PAS matériel prêté
    Status: TERMINEE
    Email "vous pouvez donner avis"
    
[6b] Si matériel prêté
    Status: EN_ATTENTE_RETOUR
    Email "retourner sous 10j ou 600€"
    
[7] Retour matériel
    Status: TERMINEE
    Email "avis possible"

[8] Utilisateur donne avis
    isValidated = false (en attente)
    Email employé
    
[9] Employé valide avis
    isValidated = true
    Sync MongoDB
    Public sur accueil
```

---

## ⚡ Performance

### Indexation MySQL

```sql
-- Clés étrangères (automatiquement indexées)
ALTER TABLE commandes ADD INDEX idx_user_id (user_id);
ALTER TABLE commandes ADD INDEX idx_menu_id (menu_id);

-- Recherches fréquentes
ALTER TABLE commandes ADD INDEX idx_status (statut);
ALTER TABLE commandes ADD INDEX idx_user_status (user_id, statut);

-- Dates
ALTER TABLE commandes ADD INDEX idx_created (date_commande);

-- Avis
ALTER TABLE avis ADD INDEX idx_validated (isValidated);
ALTER TABLE avis ADD INDEX idx_commande (id_commande);
```

### Caching Stratégies

```php
// Cache menus (changent peu)
$menus = Cache::remember('all_menus', 3600, function() {
    return $this->menuRepository->findAll();
});

// Cache horaires
$hours = Cache::remember('business_hours', 86400, function() {
    return $this->horaireRepository->findAll();
});

// Cache avis validés (accueil)
$avis = Cache::remember('public_avis', 300, function() {
    return $this->avisService->getPublicAvis(5);
});
```

### Query Optimization

```php
// ❌ N+1 queries
$commandes = $repo->findAll();  // 1 query
foreach ($commandes as $cmd) {
    $user = $userRepo->findById($cmd->user_id);  // 100 queries!
}

// ✅ Single query with JOIN
$commandes = $db->query(
    "SELECT c.*, u.nom, u.email FROM commandes c
     INNER JOIN utilisateurs u ON c.user_id = u.id
     WHERE c.user_id = ?"
);
```

---

## 🔧 Maintenance

### Logging Strategy

```php
// Tous les événements importants
Logger::info("User created", ['userId' => $id, 'email' => $email]);
Logger::warning("API timeout", ['api' => 'Google Maps']);
Logger::error("Database connection failed", ['host' => $dbHost]);

// Fichiers logs
logs/
├─ info.log
├─ warning.log
└─ error.log
```

### Error Handling

```php
try {
    $service->createCommande($data);
} catch (InvalidData $e) {
    return response()->json(['error' => $e->getMessage()], 400);
} catch (DatabaseException $e) {
    Logger::error("Database error", ['error' => $e]);
    return response()->json(['error' => 'Server error'], 500);
} catch (Throwable $e) {
    Logger::critical("Unexpected error", ['error' => $e]);
    return response()->json(['error' => 'Server error'], 500);
}
```

### Monitoring

- ✅ Logs applicatif (errors, warnings)
- ✅ Database monitoring (slow queries)
- ✅ API monitoring (response time, errors)
- ✅ Uptime monitoring (HTTP endpoints)

---

**Status :** ✅ Complete  
**Last Updated :** 11 décembre 2025

