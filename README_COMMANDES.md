# 📦 Guide d'Implémentation : Gestion des Commandes

Ce document détaille les étapes techniques pour implémenter le module "Commandes" de l'application Vite & Gourmand, basé sur les diagrammes (Séquence 02, 03, 05) et le MLD.

## 🎯 Objectifs
- Permettre aux utilisateurs connectés de passer une commande depuis un Menu.
- Gérer le cycle de vie de la commande (8 statuts).
- Implémenter la logique tarifaire complexe (Snapshots, Réductions, Frais livraison).
- Assurer la traçabilité (Historique MySQL) et l'analyse (MongoDB).

---

## 🛠️ 1. Base de Données (Vérification)
Avant de commencer, assurez-vous que les tables SQL suivantes existent et correspondent au MLD :
- **COMMANDE** : Contient les champs de snapshot (`prix_menu_unitaire`, `nombre_personne_min_snapshot`) et les détails de livraison (`adresse_livraison`, `frais_livraison`, etc.).
- **HISTORIQUE** : `id_historique`, `id_commande`, `old_status`, `new_status`, `changed_at`, `changed_by`.
- **COMMANDE_MATERIEL** : Table de liaison si du matériel est prêté.
- **STATUT (Enum)** : `EN_ATTENTE`, `ACCEPTE`, `EN_PREPARATION`, `EN_LIVRAISON`, `LIVRE`, `EN_ATTENTE_RETOUR`, `TERMINEE`, `ANNULEE`.

---

## 🏗️ 2. Backend (PHP)

### Étape 2.1 : Modèles et Repositories
Créez les classes d'accès aux données.

**Fichiers à créer :**
1.  `src/Models/Commande.php` : Entity mapping (bête de somme).
2.  `src/Repositories/CommandeRepository.php` :
    - `create(array $data) : int`
    - `findById(int $id)`
    - `findAllByUserId(int $userId)`
    - `findAllByStatus(string $status)` (Pour employé)
    - `updateStatus(int $id, string $status)`
3.  `src/Repositories/HistoriqueRepository.php` :
    - `recordChange(int $commandeId, ?string $oldStatus, string $newStatus, int $userId)`
4.  `src/Repositories/CommandeMaterielRepository.php` : (Optionnel dans un premier temps)

### Étape 2.2 : Services Métier
C'est ici que réside la complexité (Prix, Géolocalisation).

**Fichiers à créer :**
1.  `src/Services/GeoLocationService.php` :
    - Méthode `getDistance(string $address)` : Retourne la distance en km par rapport au QG.
    - *Logique* : Utiliser une API (Google Maps/OpenRoute) ou une estimation simple pour le dev.
2.  `src/Services/CommandeService.php` :
    - `calculatePrice(int $menuId, int $nbPersonnes, string $adresse)` :
        - Récupère le Menu.
        - **Règle 10%** : Si `nbPersonnes >= menu.min + 5` → -10%.
        - **Frais Livraison** : Si hors Bordeaux → 5€ + (0.59€ * distance).
        - Retourne un DTO avec le détail du prix.
    - `createCommande(int $userId, array $data)` :
        - Appelle `calculatePrice` pour valider le montant.
        - **SNAPSHOT** : Enregistre le prix du menu *au moment de la commande*.
        - Transaction SQL : Insert Commande + Insert Historique (statut 'EN_ATTENTE').
        - **Sync MongoDB** : Insert dans `statistiques_commandes`.
    - `updateStatus(int $commandeId, string $newStatus, int $userId)` :
        - Vérifie les règles de transition (ex: user ne peut annuler que si 'EN_ATTENTE').
        - Met à jour SQL + Ajoute ligne Historique.

### Étape 2.3 : Contrôleur et Routes
Exposez l'API au Frontend.

**Fichiers à modifier/créer :**
1.  `backend/api/routes.commandes.php` :
    - `POST /api/commandes/calculate` (Calcul prix avant validation)
    - `POST /api/commandes` (Création)
    - `GET /api/commandes` (Liste mes commandes - filtre par token user)
    - `GET /api/commandes/{id}` (Détail)
    - `PATCH /api/commandes/{id}/status` (Changement statut - Employé/Admin ou User annulation)
2.  `src/Controllers/CommandeController.php` :
    - Méthodes correspondant aux routes.
    - Validation des entrées (`Validator` class).
    - Appel au `CommandeService`.

---

## 🎨 3. Frontend (JS Vanilla)

### Étape 3.1 : Page de Commande (Tunnel)
Lorsqu'on clique sur "Commander" depuis `menu-detail.html` :
1.  Rediriger vers `commander.html?menu_id=X`.
2.  Si non connecté -> Redirection Login.
3.  **Formulaire** :
    - Récapitulatif Menu (Titre, Prix unitaire).
    - Adresse (Pré-remplie avec celle du profil, modifiable).
    - Date/Heure prestation.
    - Nombre de personnes (Min dynamique selon menu).
4.  **AJAX Calcul** :
    - Au changement d'adresse ou de nombre de personnes, appel `POST /api/commandes/calculate`.
    - Afficher le détail : "Prix Menu x Qté", "Réduction (-10%)", "Frais livraison", "TOTAL".

### Étape 3.2 : Dashboard Utilisateur
Dans `profil.html` ou `mes-commandes.html` :
1.  Lister les commandes (Date, Menu, Montant, **Statut avec code couleur**).
2.  Bouton "Annuler" visible uniquement si statut = `EN_ATTENTE`.
3.  Clic sur une commande -> Voir l'historique (Timeline).

### Étape 3.3 : Dashboard Employé (Back-office)
Dans `admin/dashboard.html` (onglet Commandes) :
1.  Tableau des commandes triable par date/statut.
2.  Filtres : "À préparer", "À livrer", "En retard".
3.  Actions :
    - Changer statut (liste déroulante).
    - Si passage à "EN_ATTENTE_RETOUR" -> Déclenche email (via Service).

---

## 📅 Roadmap suggérée

| Jour | Tâche | Détails |
|------|-------|---------|
| J1 | **Backend Core** | Models, Repositories, et Route API `calculate` (mock geo). |
| J2 | **Backend Logic** | CommandeService (Create + Rules), Tests Postman. |
| J3 | **Frontend User** | Formulaire de commande + Liaison API Calcul + Validation. |
| J4 | **Frontend List** | Page "Mes Commandes" + Gestion Annulation. |
| J5 | **Back-office** | Gestion des statuts côté employé + Notifications (Emails). |

## ⚠️ Points de vigilance
1.  **Immutabilité (Snapshots)** : Ne jamais utiliser le prix actuel du menu pour calculer une commande passée. Utilisez les champs stockés dans la table `COMMANDE`.
2.  **Sécurité** : Vérifiez toujours que l'utilisateur qui consulte une commande est bien son propriétaire (ou un ADMIN/EMPLOYE).
3.  **Emails** : Simulez l'envoi d'email (Log ou Mailtrap) pour ne pas bloquer le dev.
