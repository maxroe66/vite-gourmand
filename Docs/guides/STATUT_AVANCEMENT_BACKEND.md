# Statut d'Avancement Backend (Module Commande)

Dernière mise à jour : [Date du jour]

## Fonctionnalités Implémentées

### 1. Gestion des Commandes (Core)
- [x] Création de commande avec calcul de prix (Règles métiers complexes, frais livraison, snapshot prix).
- [x] Validation des données (Validator).
- [x] Persistance MySQL (Transactionnel).
- [x] Synchronisation MongoDB (Analytique/Stats).

### 2. Consultation & Suivi (Consultation)
- [x] Endpoint `GET /api/my-orders` : Liste des commandes utilisateur.
- [x] Endpoint `GET /api/commandes/{id}` : Détail commande + Timeline + Matériel.
- [x] Gestion de l'historique des statuts (`COMMANDE_STATUT`).

### 3. Gestion du Matériel (Logistique)
- [x] Endpoint `POST /api/commandes/{id}/material` : Ajout de matériel prêté (Employé).
- [x] Décrémentation du stock matériel.
- [x] Mise à jour du flag `materiel_pret` sur la commande.

### 4. Cycle de Vie & Notifications
- [x] Endpoint `PUT /api/commandes/{id}/status` : Changement de statut.
- [x] Logique Alerte Retour Matériel (Statut `EN_ATTENTE_RETOUR` -> Email Caution 600€).
- [x] Logique Invitation Avis (Statut `TERMINEE` -> Email Invitation).
- [x] Annulation commande (Client/Employé) avec motif obligatoire et mode de contact.

## À Faire (Prochaines étapes)

### 1. Module Avis (Post-Commande)
- Implémenter `AvisController` et `AvisService`.
- Endpoint `POST /api/avis` pour soumettre un avis (nécessite commande `TERMINEE` et `!hasAvis`).
- Vérification que l'utilisateur est bien l'auteur de la commande.

### 2. Tests
- Compléter les tests unitaires pour la nouvelle méthode `loanMaterial`.
- Tester le flux complet via Postman.
