# Index des Diagrammes de Séquences

Bienvenue dans la section des **diagrammes de séquences**. Chaque fichier couvre un flux clé de l'application.

---

## 📋 Liste des Séquences

### [1️⃣ Inscription & Connexion](./sequence_01_inscription_connexion.md)

**Acteurs** : Visiteur  
**Systèmes** : Frontend, Backend, Auth, MySQL, Mailer  
**Flux** : 
- Inscription (email + password)
- Création compte
- Email bienvenue
- Connexion (JWT token)

**Durée** : ~5 min utilisateur  
**Classes** : User, UserService, Auth, Mailer

---

### [2️⃣ Passer une Commande](./sequence_02_passer_commande.md)

**Acteurs** : Utilisateur authentifié  
**Systèmes** : Frontend, Backend, CommandeService, MySQL, MongoDB, Mailer  
**Flux** :
- Affichage formulaire commande
- Calcul prix (réduction 10% si nb personnes ≥ min+5)
- Calcul frais livraison (5€ + 0,59€/km hors Bordeaux)
- Création commande + Historique
- Sync MongoDB statistiques
- Email confirmation

**Durée** : ~2-3 min utilisateur  
**Classes** : Menu, MenuService, Commande, CommandeService, Mailer, Historique

**Règles Métier** :
- RG_REDUCTION : 10% si personnes ≥ minPersonnes + 5
- RG_LIVRAISON : 5€ + 0,59€/km si hors Bordeaux
- RG_PRIX_SNAPSHOT : Prix du menu gelé pour immuabilité

---

### [3️⃣ Modification Statut Commande](./sequence_03_modification_statut.md)

**Acteurs** : Employé  
**Systèmes** : Frontend Employé, Backend, CommandeService, MySQL, MongoDB, Mailer  
**Flux** :
- Accès gestion commandes
- Sélection commande + nouveau statut
- Update MySQL + Historique
- Sync MongoDB
- Email notification utilisateur
- Rafraîchissement liste (AJAX)

**Durée** : ~1-2 min employé  
**Classes** : Commande, CommandeService, Historique, Mailer

**Statuts Possibles** :
- en_attente → acceptée
- acceptée → en_préparation
- en_préparation → en_livraison
- en_livraison → livrée
- livrée → matériel_pending (si matériel prêté)
- matériel_pending → terminée

---

### [4️⃣ Validation d'Avis](./sequence_04_validation_avis.md)

**Acteurs** : Utilisateur, Employé  
**Systèmes** : Frontend User, Frontend Employé, Backend, AvisService, MySQL, MongoDB, Accueil  
**Flux Phase 1 (Utilisateur)** :
- Email "Donnez votre avis"
- Lien vers formulaire avis
- Saisie note (1-5) + commentaire
- POST /api/avis
- Création en MySQL (isValidated=false)
- Confirmation

**Flux Phase 2 (Employé)** :
- Accès "Gestion Avis"
- Liste avis en attente
- Lecture + Validation
- UPDATE MySQL (isValidated=true)
- INSERT MongoDB avis validé
- Retire de liste (AJAX)

**Flux Phase 3 (Affichage)** :
- GET /api/avis/validated
- Fetch depuis MongoDB
- Affiche en page accueil

**Durée** : ~1-2 min (création + modération)  
**Classes** : Avis, AvisService, Mailer

**Règles** :
- Avis créé = "En attente validation"
- Seuls avis validés → affichage public
- Sync MySQL ↔ MongoDB pour avis validés
- Fallback AVIS_FALLBACK en cas panne MongoDB

---

### [5️⃣ Suivi de Commande](./sequence_05_suivi_commande.md)

**Acteurs** : Utilisateur  
**Systèmes** : Frontend, Backend, CommandeService, MySQL (Historique)  
**Flux** :
- Accès "Mes Commandes"
- GET /api/commandes?userId=X
- Affiche liste commandes
- Sélectionne commande
- GET /api/commandes/{id}/timeline
- Récupère historique complet
- Affiche timeline graphique avec dates/heures/responsables

**Durée** : ~1-2 min utilisateur  
**Classes** : Commande, CommandeService, Historique

**Affichage Timeline** :
```
✓ En attente (Système) - 2025-01-01 10:00
✓ Acceptée (Marie) - 2025-01-01 11:30
⏳ En préparation (Jean) - 2025-01-01 14:00
🚚 En livraison - 2025-01-02 09:00
✓ Livrée - 2025-01-02 14:30
```

---

## 🎯 Couverture Totale

| Feature Énoncé | Séquence | ✅ |
|---|---|---|
| Créer compte + Connexion | #1 | ✅ |
| Voir menus | Diagramme classes | ✅ |
| Passer commande | #2 | ✅ |
| Modifier commande | Diagramme classes | ✅ |
| Annuler commande | Diagramme classes | ✅ |
| Suivi commande | #5 | ✅ |
| Donner avis | #4 | ✅ |
| Valider avis | #4 | ✅ |
| Modifier statut (employé) | #3 | ✅ |
| Notifications | #1, #2, #3, #4 | ✅ |

---

## 📊 Technologie Utilisée

| Couche | Technologie |
|--------|------------|
| Frontend | HTML/CSS/JavaScript (Fetch API) |
| Backend | PHP 8.0+ (POO) |
| Persistance | MySQL 8.0+ (relationnel) |
| Analytics | MongoDB 4.4+ (NoSQL) |
| Auth | JWT tokens |
| Email | PHPMailer/SMTP |

---

## 🔗 Relations avec Autres Diagrammes

```
Diagramme MCD (Conceptuel)
        ↓
Diagramme MLD (Logique)
        ↓
Diagramme Classes UML (POO)
        ↓
Diagramme Cas d'Utilisation (Acteurs)
        ↓
Diagrammes Séquences (Flux Détaillés) ← Vous êtes ici
```

---

## 💡 Comment Lire les Diagrammes

Chaque fichier de séquence contient :

1. **Diagramme Mermaid** : Visualisation temporelle de l'interaction
2. **Tableau Flux** : Étapes numérotées pour compréhension rapide
3. **SQL/Code** : Requêtes et commandes exécutées
4. **Sécurité** : Validations et authentifications
5. **Classes Impliquées** : Qui implémente chaque étape

Les flèches pointent du haut vers le bas (temporalité).
