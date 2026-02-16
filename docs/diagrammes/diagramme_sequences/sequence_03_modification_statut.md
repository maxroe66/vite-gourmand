# Diagramme de Séquence 3 : Modification Statut Commande (Employé)

## 📋 Description

Flux de modification du statut d'une commande par un employé : mise à jour, historisation, synchronisation MongoDB et notification utilisateur.

---

## Diagramme

```mermaid
%%{init: { 'theme': 'base', 'themeVariables': { 'primaryColor':'#ffffff', 'primaryTextColor':'#000000', 'primaryBorderColor':'#333333', 'lineColor':'#666666', 'secondBkgColor':'#f0f0f0', 'tertiaryColor':'#ffffff'} } }%%
sequenceDiagram
    actor Employe
    participant Frontend as 🌐 Frontend Employé
    participant Backend as 🖥️ Backend
    participant CommandeService as CommandeService
    participant MySQL as 🗄️ MySQL
    participant MongoDB as 📊 MongoDB
    participant Mailer as Mailer
    actor Utilisateur

    rect rgb(255, 240, 200)
    note over Employe,Utilisateur: FLUX MODIFICATION STATUT COMMANDE

    Employe->>Frontend: Accède "Gestion Commandes"
    Frontend->>Frontend: Affiche liste commandes avec filtres
    
    Employe->>Frontend: Sélectionne commande + nouveau statut
    Frontend->>Frontend: Affiche dropdown : {en_attente, acceptée, en_préparation, livraison, livrée, matériel_pending, terminée}
    
    Employe->>Frontend: Sélectionne "acceptée"
    Frontend->>Backend: POST /api/commandes/{commandeId}/status<br/>(newStatus='acceptée', changedBy=employeId)
    
    Backend->>CommandeService: updateCommandeStatus(commandeId, 'acceptée', employeId)
    
    CommandeService->>MySQL: SELECT * FROM commandes WHERE id=?
    MySQL-->>CommandeService: commande (oldStatus, userId)
    
    CommandeService->>MySQL: UPDATE commandes SET status='acceptée' WHERE id=?
    MySQL-->>CommandeService: ✓
    
    CommandeService->>MySQL: INSERT INTO historique<br/>(commande_id, previousStatus, newStatus='acceptée', changedBy=employeId, notes=NULL, changedAt=NOW())
    MySQL-->>CommandeService: ✓
    
    CommandeService->>MongoDB: db.statistiques_commandes.updateOne<br/>({commandeId}, {$set: {status: 'acceptée'}})
    MongoDB-->>CommandeService: ✓
    
    CommandeService->>Mailer: sendStatusUpdate(user.email, commandeId, 'acceptée')
    Mailer-->>CommandeService: ✓ Email envoyé
    
    CommandeService-->>Backend: {success: true, newStatus}
    Backend-->>Frontend: {success: true, message: "Statut modifié"}
    Frontend->>Frontend: Rafraîchit liste (AJAX)
    
    Employe->>Frontend: Voit statut "acceptée"
    Utilisateur->>Utilisateur: Reçoit email notification de changement
    
    end
```

---

## 📊 Détails du Flux

### **Cycle de Vie Commande**

| Statut | Signification | Qui Modifie |
|--------|--------------|-------------|
| **en_attente** | Nouvelle commande | Système |
| **acceptée** | Validée par employé | Employé |
| **en_préparation** | En cours de préparation | Employé |
| **en_livraison** | Livrée au client | Logistique |
| **livrée** | Réceptionnée | Logistique |
| **matériel_pending** | En attente retour matériel | Système |
| **terminée** | Complètement achevée | Système |

### **Flux d'Exécution**

| Étape | Système | Action |
|-------|---------|--------|
| 1-3 | Frontend | Accès + Sélection commande |
| 4-5 | Frontend | Choix nouveau statut |
| 6-8 | Frontend + Backend | POST requête |
| 9-12 | CommandeService | UPDATE commande |
| 13 | CommandeService | INSERT historique |
| 14 | CommandeService | UPDATE MongoDB |
| 15 | Mailer | Envoie notification |
| 16 | Frontend | Rafraîchit liste |
| 17 | Employé + Utilisateur | Voir changement |

---

## 💾 Données Mises à Jour

### **MySQL (COMMANDES table)**

```sql
UPDATE commandes 
SET status = 'acceptée' 
WHERE id = {commandeId}
```

### **MySQL (HISTORIQUE table)**

```sql
INSERT INTO historique (
  commande_id, previousStatus, newStatus,
  changedBy, notes, changedAt
)
VALUES (
  {commandeId}, 'en_attente', 'acceptée',
  {employeId}, NULL, NOW()
)
```

### **MongoDB (statistiques_commandes)**

```javascript
db.statistiques_commandes.updateOne(
  { commandeId: {commandeId} },
  { $set: { status: 'acceptée' } }
)
```

---

## 📧 Notifications

| Événement | Destinataire | Email |
|-----------|--------------|-------|
| Commande acceptée | Utilisateur | "Votre commande a été acceptée" |
| En préparation | Utilisateur | "Votre commande est en préparation" |
| En livraison | Utilisateur | "Votre commande est en cours de livraison" |
| Livrée | Utilisateur | "Votre commande a été livrée" |
| Matériel pending | Utilisateur | "N'oubliez pas de retourner le matériel sous 10j" |
| Terminée | Utilisateur | "Commande terminée - Donnez votre avis" |

---

## 🔐 Sécurité

✅ **Vérification permissions** : Employé uniquement  
✅ **Traçabilité complète** : Historique + Qui + Quand  
✅ **Synchronisation DB** : MySQL + MongoDB cohérent  
✅ **Notifications** : Utilisateur informé de chaque changement  
✅ **AJAX** : Pas de rechargement page

---

## 🔗 Classes Impliquées

- **Commande** : Gère commande
- **CommandeService** : Logique métier
- **Historique** : Trace changements
- **Mailer** : Notifications
- **MySQLDatabase** : Persistance MySQL
- **MongoDBClient** : Synchronisation analytics
