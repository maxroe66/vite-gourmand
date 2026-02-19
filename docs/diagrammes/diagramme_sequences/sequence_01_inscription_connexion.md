# Diagramme de Séquence 1 : Inscription & Connexion

## 📋 Description

Flux complet d'inscription d'un visiteur et connexion au système.

---

## Diagramme

```mermaid
%%{init: { 'theme': 'base', 'themeVariables': { 'primaryColor':'#ffffff', 'primaryTextColor':'#000000', 'primaryBorderColor':'#333333', 'lineColor':'#666666', 'secondBkgColor':'#f0f0f0', 'tertiaryColor':'#ffffff'} } }%%
sequenceDiagram
    actor Visiteur
    participant Frontend as 🌐 Frontend<br/>HTML/CSS/JS
    participant Backend as 🖥️ Backend<br/>PHP
    participant UserService as UserService
    participant Auth as Auth
    participant Database as 🗄️ MySQL
    participant Mailer as Mailer
    participant User_Email as 📧 Email Utilisateur

    rect rgb(200, 220, 255)
    note over Visiteur,User_Email: FLUX INSCRIPTION

    Visiteur->>Frontend: Clic "S'inscrire"
    Frontend->>Frontend: Affiche formulaire
    Visiteur->>Frontend: Saisit : nom, prénom, téléphone, adresse, email, mot de passe
    Frontend->>Frontend: Valide client (email, password fort)
    
    Visiteur->>Frontend: Clique "S'inscrire"
    Frontend->>Backend: POST /api/auth/register<br/>(userData)
    
    Backend->>UserService: registerUser(userData)
    UserService->>Auth: hashPassword(password)
    Auth-->>UserService: passwordHash
    
    UserService->>Database: INSERT INTO users<br/>(email, firstName, lastName, phone, address, passwordHash, role='utilisateur')
    Database-->>UserService: userId
    
    UserService->>Mailer: sendWelcomeEmail(email, firstName)
    Mailer->>User_Email: Email bienvenue
    User_Email-->>Mailer: ✓ Envoyé
    
    UserService-->>Backend: userId
    Backend-->>Frontend: {success: true, userId, message: "Bienvenue!"}
    Frontend->>Frontend: Redirige vers login
    Visiteur->>Visiteur: Compte créé!

    end

    rect rgb(220, 200, 255)
    note over Visiteur,User_Email: FLUX CONNEXION

    Visiteur->>Frontend: Accès page login
    Frontend->>Frontend: Affiche formulaire email + password
    Visiteur->>Frontend: Saisit email + mot de passe
    
    Visiteur->>Frontend: Clique "Se connecter"
    Frontend->>Backend: POST /api/auth/login<br/>(email, password)
    
    Backend->>Auth: login(email, password)
    Auth->>Database: SELECT * FROM users WHERE email=?
    Database-->>Auth: user (avec passwordHash)
    
    Auth->>Auth: verifyPassword(password, user.passwordHash)
    
    alt Mot de passe correct
        Auth->>Auth: generateToken(user.id, user.role)
        Auth-->>Backend: {success: true, user: {id, email, role}}
        Backend-->>Frontend: {user, redirect_to: "/dashboard"} + cookie HttpOnly authToken
        Frontend->>Frontend: Token stocké automatiquement en cookie HttpOnly
        Frontend->>Frontend: Redirige vers dashboard
        Visiteur->>Visiteur: ✓ Connecté!
    else Mot de passe incorrect
        Auth-->>Backend: {success: false, error: "Identifiants invalides"}
        Backend-->>Frontend: {error: "Email ou mot de passe incorrect"}
        Frontend->>Frontend: Affiche message erreur
        Visiteur->>Visiteur: ❌ Erreur
    end

    end
```

---

## 📊 Détails du Flux

### **Flux Inscription**

| Étape | Acteur | Action |
|-------|--------|--------|
| 1 | Visiteur | Clic "S'inscrire" |
| 2 | Frontend | Affiche formulaire |
| 3 | Visiteur | Saisit données |
| 4 | Frontend | Valide données côté client |
| 5 | Visiteur | Clique "S'inscrire" |
| 6 | Frontend | POST /api/auth/register |
| 7 | Backend | Appelle UserService |
| 8 | UserService | Hash password + Crée user |
| 9 | Database | INSERT user |
| 10 | Mailer | Envoie email bienvenue |
| 11 | Visiteur | Reçoit email + Compte créé |

### **Flux Connexion**

| Étape | Acteur | Action |
|-------|--------|--------|
| 1 | Visiteur | Accès page login |
| 2 | Frontend | Affiche formulaire |
| 3 | Visiteur | Saisit email + password |
| 4 | Visiteur | Clique "Se connecter" |
| 5 | Frontend | POST /api/auth/login |
| 6 | Backend | Appelle Auth |
| 7 | AuthService | Récupère user + Verify password (Argon2ID) |
| 8 | AuthService | Génère JWT HS256, pose cookie `authToken` (HttpOnly, Secure) |
| 9 | CsrfService | Génère token CSRF, pose cookie `csrfToken` (non-HttpOnly) |
| 10 | Frontend | Redirigé vers espace personnel (cookie transmis automatiquement) |
| 11 | Visiteur | Connecté ! |

---

## 🔐 Sécurité

✅ **Validation côté client** (email, password fort)  
✅ **Hash password** (Argon2ID via `password_hash(PASSWORD_ARGON2ID)`)  
✅ **JWT HS256** stocké en cookie HttpOnly `authToken` (jamais accessible en JavaScript)  
✅ **CSRF Double Submit Cookie** : cookie `csrfToken` + header `X-CSRF-Token`  
✅ **Stockage cookie HttpOnly + Secure + SameSite** du token (protection XSS)  
✅ **Validation côté serveur** de tous les inputs

---

## 🔗 Classes Impliquées

- **UserRepository** : Accès données utilisateurs (MySQL)
- **UserService** : Logique d'inscription
- **AuthService** : Hash Argon2ID + JWT HS256 + gestion cookies
- **CsrfService** : Génération / rotation token CSRF
- **MailerService** : Notifications email (bienvenue, reset)
- **Database** : Persistance MySQL via PDO
