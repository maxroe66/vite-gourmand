# Diagramme de Séquence 4 : Validation d'Avis (Employé)

## 📋 Description

Flux complet de validation d'avis : création par utilisateur, modération par employé, synchronisation MongoDB et affichage page accueil.

---

## Diagramme

```mermaid
%%{init: { 'theme': 'base', 'themeVariables': { 'primaryColor':'#ffffff', 'primaryTextColor':'#000000', 'primaryBorderColor':'#333333', 'lineColor':'#666666', 'secondBkgColor':'#f0f0f0', 'tertiaryColor':'#ffffff'} } }%%
sequenceDiagram
    actor Utilisateur
    participant Frontend_User as 🌐 Frontend Utilisateur
    participant Backend as 🖥️ Backend
    participant AvisService as AvisService
    participant MySQL as 🗄️ MySQL
    participant MongoDB as 📊 MongoDB Avis
    participant Frontend_Emp as 🌐 Frontend Employé
    actor Employe
    participant Accueil as 🏠 Page Accueil

    rect rgb(255, 255, 200)
    note over Utilisateur,Accueil: FLUX CRÉATION AVIS

    Utilisateur->>Frontend_User: Reçoit email "Donnez votre avis"
    Utilisateur->>Frontend_User: Clique lien "Donner un avis"
    Frontend_User->>Frontend_User: Affiche formulaire : note (1-5) + commentaire
    
    Utilisateur->>Frontend_User: Saisit note=5, commentaire="Excellent!"
    Frontend_User->>Backend: POST /api/avis<br/>(userId, commandeId, rating, comment)
    
    Backend->>AvisService: createAvis(userId, commandeId, rating, comment)
    
    AvisService->>MySQL: INSERT INTO avis<br/>(user_id, commande_id, rating, comment, isValidated=false, createdAt=NOW())
    MySQL-->>AvisService: avisId
    
    AvisService-->>Backend: {avisId, status: "En attente de validation"}
    Backend-->>Frontend_User: {success: true, message: "Avis enregistré"}
    Frontend_User->>Frontend_User: Affiche "Avis en attente de validation"
    Utilisateur->>Utilisateur: ✓ Avis créé

    end

    rect rgb(255, 200, 200)
    note over Employe,Accueil: FLUX VALIDATION AVIS

    Employe->>Frontend_Emp: Accède "Gestion Avis"
    Frontend_Emp->>Backend: GET /api/avis/pending
    
    Backend->>MySQL: SELECT * FROM avis WHERE isValidated=false
    MySQL-->>Backend: avis_list
    Frontend_Emp->>Frontend_Emp: Affiche liste avis en attente
    
    Employe->>Frontend_Emp: Clique avis, lit commentaire
    Employe->>Frontend_Emp: Clique "Valider"
    Frontend_Emp->>Backend: POST /api/avis/{avisId}/validate<br/>(validatedBy=employeId)
    
    Backend->>AvisService: validateAvis(avisId, employeId)
    
    AvisService->>MySQL: UPDATE avis SET isValidated=true, validatedBy=?, validatedAt=NOW()
    MySQL-->>AvisService: ✓
    
    AvisService->>MongoDB: db.avis.insertOne({<br/>_id: ObjectId(), avisId, userId, commandeId,<br/>rating, comment, isValidated: true, validatedAt: NOW()})
    MongoDB-->>AvisService: ✓ Synchronisé
    
    AvisService-->>Backend: {success: true, message: "Avis validé"}
    Backend-->>Frontend_Emp: {success: true}
    Frontend_Emp->>Frontend_Emp: Retire de liste en attente (AJAX)
    
    Employe->>Employe: ✓ Avis validé
    
    Accueil->>Backend: GET /api/avis/validated
    Backend->>AvisService: getValidatedAvis()
    AvisService->>MongoDB: db.avis.find({isValidated: true}).limit(5)
    MongoDB-->>AvisService: avis_list
    AvisService-->>Backend: avis_list
    Backend-->>Accueil: avis_list
    Accueil->>Accueil: Affiche avis en page d'accueil
    
    end
```

---

## 📊 Détails du Flux

### **Phase 1 : Création d'Avis**

| Étape | Acteur | Action |
|-------|--------|--------|
| 1 | Utilisateur | Reçoit email notification |
| 2-3 | Utilisateur | Clique lien + Formulaire |
| 4-5 | Utilisateur | Saisit note + commentaire |
| 6-7 | Frontend | POST /api/avis |
| 8-9 | Backend | Appelle AvisService |
| 10 | AvisService | INSERT avis (isValidated=false) |
| 11 | Utilisateur | Reçoit confirmation |

### **Phase 2 : Validation par Employé**

| Étape | Acteur | Action |
|-------|--------|--------|
| 1-3 | Employé | Accès + Liste avis en attente |
| 4 | Employé | Lit avis |
| 5-6 | Employé | Clique "Valider" |
| 7-9 | Backend | Appelle AvisService |
| 10 | AvisService | UPDATE avis (isValidated=true) |
| 11 | AvisService | INSERT MongoDB avis validé |
| 12 | Frontend | Retire de liste |

### **Phase 3 : Affichage Page Accueil**

| Étape | Système | Action |
|-------|---------|--------|
| 1-2 | Frontend Accueil | GET /api/avis/validated |
| 3-5 | Backend | Récupère depuis MongoDB |
| 6-7 | Frontend | Affiche avis récents validés |

---

## 💾 Données Sauvegardées

### **MySQL (AVIS table) - Avant validation**

```sql
INSERT INTO avis (
  user_id, commande_id, rating, comment,
  isValidated, createdAt
)
VALUES (userId, commandeId, 5, 'Excellent!', false, NOW())
```

### **MySQL (AVIS table) - Après validation**

```sql
UPDATE avis 
SET isValidated=true, validatedBy=employeId, validatedAt=NOW()
WHERE id=avisId
```

### **MongoDB (avis collection) - Avis validés uniquement**

```javascript
db.avis.insertOne({
  _id: ObjectId(),
  avisId, userId, commandeId,
  rating: 5, comment: 'Excellent!',
  isValidated: true,
  validatedAt: ISODate(NOW)
})
```

---

## 🎯 Règles de Gestion

- ✅ Avis créé avec statut "En attente"
- ✅ Employé doit valider avant publication
- ✅ Seuls avis validés apparaissent en accueil
- ✅ Sync MySQL ↔ MongoDB pour avis validés
- ✅ Fallback table AVIS_FALLBACK en cas de panne MongoDB

---

## 🔐 Sécurité

✅ **Modération** : Avis validés uniquement par employé  
✅ **Validation input** : Rating 1-5, commentaire sanitisé  
✅ **Authentification** : Utilisateur connecté requis  
✅ **Duplication DB** : MySQL + MongoDB pour redondance  
✅ **Audit trail** : Trace validatedBy + validatedAt

---

## 🔗 Classes Impliquées

- **Avis** : Crée/récupère avis
- **AvisService** : Logique métier avis
- **Mailer** : Notification création avis
- **MySQLDatabase** : Persistance MySQL
- **MongoDBClient** : Synchronisation analytics
