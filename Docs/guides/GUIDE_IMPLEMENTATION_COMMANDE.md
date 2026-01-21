# 📦 Guide d'Implémentation : Feature Commande (Vite & Gourmand)

**Version :** 1.1.0  
**Date :** 21 Janvier 2026  
**Responsable :** Équipe Backend / Lead Dev  
**Statut :** En cours (Mise à jour suite analyse manques)

---

## 📑 Table des Matières

1.  [Objectif de la Feature](#objectif-de-la-feature)
2.  [Prérequis Techniques](#prérequis-techniques)
3.  [Étape 1 : Vérification Base de Données](#étape-1--vérification-base-de-données)
4.  [Étape 2 : Couche Modèle (Models)](#étape-2--couche-modèle-models)
5.  [Étape 3 : Couche Accès Données (Repository)](#étape-3--couche-accès-données-repository)
6.  [Étape 4 : Logique Métier (Service)](#étape-4--logique-métier-service)
7.  [Étape 5 : API & Contrôleurs (Controller)](#étape-5--api--contrôleurs-controller)
8.  [Étape 6 : Intégration Frontend](#étape-6--intégration-frontend)
9.  [Sécurité & RGPD](#sécurité--rgpd)
10. [Stratégie de Tests](#stratégie-de-tests)

---

## 🎯 Objectif de la Feature

Fournir une gestion complète du cycle de vie des commandes : de la création par le client jusqu'au service après-vente (avis, retour matériel). Le module doit couvrir la commande, le paiement (simulé par statuts), le suivi en temps réel (timeline), la gestion du matériel prêté et les notifications automatiques.

---

## 🛠 Prérequis Techniques

Avant de commencer, validez les points suivants :

*   [ ] **Authentification** : Le système de login / registration fonctionne.
*   [ ] **Base de Données** : Tables `COMMANDE`, `COMMANDE_STATUT`, `COMMANDE_MATERIEL` créées.
*   [ ] **API Google Maps** : Clé API valide (avec fallback estimation configuré).
*   [ ] **Service Mailer** : Templates d'emails prêts :
    *   `order_confirmation` (Confirm commande)
    *   `status_update` (Notif changement statut)
    *   `material_return_alert` (Alerte caution 600€)
    *   `review_invitation` (Invitation à noter)

---

## Étape 1 : Vérification Base de Données

S'assurer que le schéma SQL supporte toutes les règles métiers.

**Table `COMMANDE` (Snapshots & Flags) :**
*   `prix_menu_unitaire`, `nombre_personne_min_snapshot`, `montant_reduction` : Pour figer le prix.
*   `materiel_pret` (Boolean) : Indicateur rapide pour savoir si du matériel est impliqué.
*   `statut` (Enum) : `EN_ATTENTE`, `ACCEPTE`, `EN_PREPARATION`, `EN_LIVRAISON`, `LIVRE`, `EN_ATTENTE_RETOUR`, `TERMINEE`, `ANNULEE`.

**Table `COMMANDE_STATUT` (Historique Traçabilité) :**
*   `id_commande`, `statut`, `modifie_par`, `changed_at`, `commentaire`.

---

## Étape 2 : Couche Modèle (Models)

### Fichier : `backend/src/Models/Commande.php`

```php
class Commande {
    // ... propriétés existantes ...
    public bool $materielPret; // Important pour la logique de retour
    public bool $hasAvis;      // Pour l'UI "Donner mon avis"
    
    // Relations (chargées à la demande ou via Repository)
    public ?array $historique = []; 
    public ?array $materiels = [];
}
```

---

## Étape 3 : Couche Accès Données (Repository)

### Fichier : `backend/src/Repositories/CommandeRepository.php`

Méthodes à implémenter ou compléter :

1.  **`findAllByUserId(int $userId): array`**
    *   Retourner la liste des commandes triées par date décroissante.
2.  **`findByIdWithDetails(int $id): ?Commande`** 
    *   Retourner la commande + son historique (`COMMANDE_STATUT`) + matériel (`COMMANDE_MATERIEL`).
3.  **`findByFilters(array $filters): array`** (Pour Employé)
    *   Permettre filtrage par `status` et recherche par `userId` (ou nom client).
4.  **`setMateriel(int $commandeId, array $materiels): void`**
    *   Insérer dans `COMMANDE_MATERIEL`.
    *   Décrémenter stock `MATERIEL`.
    *   Mettre à jour flag `materiel_pret = 1` dans `COMMANDE`.
5.  **`getTimeline(int $commandeId): array`**
    *   `SELECT * FROM COMMANDE_STATUT WHERE id_commande = ? ORDER BY date_changement ASC`.

---

## Étape 4 : Logique Métier (Service)

### Fichier : `backend/src/Services/CommandeService.php`

#### 1. Consultation & Suivi (`getUserOrders`, `getTimeline`)
*   **Objectif** : Permettre au client de voir "Mes Commandes" et le détail.
*   **Implémentation** :
    *   `getUserOrders($userId)` : Appel repo simple.
    *   `getOrderWithTimeline($userId, $cmdId)` :
        *   Vérifier que `$userId` est propriétaire.
        *   Récupérer commande + historique.
        *   Formatter la timeline pour le frontend (Date, Statut, Description).

#### 2. Mise à jour Statut & Notifications (`updateStatus`)
C'est ici que réside la complexité des règles métiers "Post-Commande".
*   **Logique** :
    *   Mettre à jour statut SQL + Historique.
    *   **Cas Spécial `EN_ATTENTE_RETOUR`** :
        *   Si déclenché, envoyer email **"Alerte Retour Matériel"** (Texte légal : "Restitution sous 10j ou prélèvement 600€").
    *   **Cas Spécial `TERMINEE`** :
        *   Si commande terminée (soit après livraison directe, soit après retour matériel) :
        *   Envoyer email **"Votre avis compte"** (Lien vers form avis).
    *   **Cas Spécial `ANNULEE` (Employé)** :
        *   Vérifier présence `motif` et `modeContact` (Requis).

#### 3. Gestion du Matériel (`addMaterielToOrder`)
*   **Entrée** : Employé ID, Commande ID, Liste Matériels.
*   **Action** :
    *   Appeler `repo->setMateriel()`.
    *   Le statut de la commande ne change pas immédiatement (reste souvent `EN_PREPARATION` ou `EN_LIVRAISON`), mais le flag est posé pour forcer le passage futur par `EN_ATTENTE_RETOUR` avant `TERMINEE`.

#### 4. Filtres Employé (`searchCommandes`)
*   Exposer la recherche multicritères pour le dashboard employé.

---

## Étape 5 : API & Contrôleurs (Controller)

### Fichier : `backend/src/Controllers/CommandeController.php`

Endpoints manquants à ajouter :

1.  **`GET /api/commandes`** (Client & Employé)
    *   Client : Renvoie `listMyOrders`.
    *   Employé : Renvoie `searchCommandes` (avec params `?status=EN_COURS&user=...`).
2.  **`GET /api/commandes/{id}`**
    *   Renvoie le détail complet (Prix, Produits, Adresse).
    *   Inclut champ `timeline` (Tableau d'étapes).
    *   Inclut champ `actions_possibles` (ex: `['annuler', 'modifier']` ou `['donner_avis']`) pour aider le front.
3.  **`POST /api/commandes/{id}/material`** (Employé)
    *   Body: `[{ "id": 1, "quantite": 2 }]`.
    *   Appelle service matériel.
4.  **`GET /api/menues-commandes-stats`** (Admin)
    *   Endpoint dédié aux stats MongoDB (CA par menu, nb commandes).

---

## Étape 6 : Intégration Frontend

### Pages à prévoir :
1.  **Mes Commandes (Client)** :
    *   Liste cartes avec : Date, Montant, Badge Statut (Couleur selon statut).
    *   Bouton "Voir le suivi".
2.  **Détail Commande & Timeline (Client)** :
    *   Visualisation verticale de l'historique (`EN_ATTENTE` -> `ACCEPTE` -> ...).
    *   Si `TERMINEE` et `!hasAvis` : Gros bouton CTA "Donner mon avis".
3.  **Gestionnaire Commandes (Employé)** :
    *   Tableau avec filtres.
    *   Modale "Ajout Matériel" sur une commande.
    *   Modale "Changer Statut" (Select avec statuts autorisés).
    *   Modale "Annuler" (Champs obligatoires : Motif, Mode Contact).

---

## ✅ Checklist Finale

- [ ] L'utilisateur voit sa timeline complète.
- [ ] L'employé peut filtrer les commandes "EN_ATTENTE".
- [ ] L'ajout de matériel décrémente le stock.
- [ ] Le passage à `EN_ATTENTE_RETOUR` envoie le mail de menace (600€).
- [ ] Le passage à `TERMINEE` envoie le mail d'invitation avis.
- [ ] Impossible d'annuler sans motif en tant qu'employé.