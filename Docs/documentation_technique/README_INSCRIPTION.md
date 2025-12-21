# 📋 Documentation Fonctionnelle & Technique — Inscription Utilisateur

## 1. Présentation

Ce document détaille le fonctionnement, l’API, la logique métier, les tests et la traçabilité de la fonctionnalité d’inscription utilisateur du projet **Vite & Gourmand**.

---

## 2. Flux d’inscription (backend)

- **Entrée** : Requête HTTP POST `/api/auth/register`
- **Traitement** :
  - Validation des données (backend + frontend)
  - Hashage sécurisé du mot de passe
  - Création de l’utilisateur en base MySQL (table `UTILISATEUR`)
  - Envoi d’un email de bienvenue
  - Log de chaque étape (succès, erreurs)
- **Sortie** : Réponse JSON structurée (succès ou erreur)

---

## 3. Spécification de l’API

### Endpoint
- **POST** `/api/auth/register`

### Body attendu (JSON)
```json
{
  "firstName": "Jean",
  "lastName": "Dupont",
  "email": "jean.dupont@email.fr",
  "password": "Password123",
  "phone": "0612345678",
  "address": "123 Rue de la Liberté",
  "city": "Bordeaux",
  "postalCode": "33000"
}
```

### Réponse — Succès
```json
{
  "success": true,
  "userId": 42,
  "message": "Inscription réussie. Email de bienvenue envoyé."
}
```

### Réponse — Erreur de validation
```json
{
  "success": false,
  "message": "Des champs sont invalides.",
  "mainError": "Le format de l'adresse email est invalide.",
  "errors": {
    "email": "Le format de l'adresse email est invalide.",
    "password": "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre."
  }
}
```

### Réponse — Email déjà utilisé
```json
{
  "success": false,
  "message": "Erreur lors de la création de l'utilisateur."
}
```

---

## 4. Logique métier (côté backend)

- **Contrôleur** : `App\Controllers\Auth\AuthController::register()`
- **Services utilisés** :
  - `UserService` (création utilisateur)
  - `AuthService` (hash mot de passe)
  - `MailerService` (email de bienvenue)
  - `UserValidator` (validation)
  - `MonologLogger` (logs)
- **Modèle** : `App\Models\User`
- **Table SQL** : `UTILISATEUR` (voir script de création)
- **Logs** : Tous les événements sont tracés dans `logs/app.log`

---

## 5. Tests réalisés (Postman)

- **Cas nominal** :
  - Tous les champs valides → Succès, code 201, utilisateur créé en base, email envoyé
- **Email déjà utilisé** :
  - Même email → Échec, code 400, message d’erreur
- **Mot de passe faible** :
  - Mot de passe trop simple → Échec, code 400, message d’erreur
- **Champs manquants** :
  - Un ou plusieurs champs absents → Échec, code 400, message d’erreur détaillée
- **Logs** :
  - Succès et erreurs visibles dans `logs/app.log`
- **Persistance** :
  - Vérification en base MySQL (table `UTILISATEUR`)

---

## 6. Diagramme de séquence

Voir : `Docs/diagrammes/diagramme_sequences/sequence_01_inscription_connexion.md`

---

## 7. Points de vigilance & bonnes pratiques

- Validation côté client ET côté serveur
- Hashage sécurisé (password_hash)
- Gestion des erreurs et des logs (Monolog)
- Respect du RGPD (pas de mot de passe en clair, email unique)
- Réponses API toujours structurées (succès/erreur)
- Tests automatisés et manuels (Postman)

---

## 8. Pour aller plus loin

- Export Postman disponible sur demande
- Extension possible : validation email, captcha, double opt-in, etc.

---

*Document rédigé le 21/12/2025 — à jour avec la dernière version du backend.*
