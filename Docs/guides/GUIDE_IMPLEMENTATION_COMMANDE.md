# 📦 Guide d'Implémentation : Feature Commande (Vite & Gourmand)

**Version :** 1.0.0  
**Date :** 21 Janvier 2026  
**Responsable :** Équipe Backend / Lead Dev  
**Statut :** À faire

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

Permettre à un utilisateur authentifié de commander un menu pour une date et un lieu spécifiques. Le système doit garantir la cohérence des prix (snapshot), calculer les frais de livraison (via API ou estimation), gérer les statuts de commande et assurer la traçabilité complète (RGPD & historique).

---

## 🛠 Prérequis Techniques

Avant de commencer, validez les points suivants :

*   [ ] **Authentification** : Le système de login / registration fonctionne et génère un JWT valide.
*   [ ] **Base de Données** : Les tables `COMMANDE`, `COMMANDE_STATUT`, `COMMANDE_MATERIEL` existent.
*   [ ] **API Google Maps** : Une clé API valide (ou un mock) est configurée dans `.env`.
*   [ ] **Service Mailer** : Le service d'envoi d'emails transactionnels est opérationnel.

---

## Étape 1 : Vérification Base de Données

S'assurer que le schéma SQL correspond aux besoins de snapshotting des prix et de traçabilité.

**Vérifier la table `COMMANDE` :**
Elle doit contenir les champs snapshot pour figer les conditions au moment de l'achat :
*   `prix_menu_unitaire` (Decimal) : Prix du menu *au moment de l'achat*.
*   `nombre_personne_min_snapshot` (Int) : Minimum requis *au moment de l'achat*.
*   `montant_reduction` (Decimal) : Montant de la remise appliquée.
*   `statut` (Enum) : Liste stricte (`EN_ATTENTE`, `ACCEPTE`, `EN_PREPARATION`, `EN_LIVRAISON`, `LIVRE`, `EN_ATTENTE_RETOUR`, `TERMINEE`, `ANNULEE`).

**Vérifier la table `COMMANDE_STATUT` (Historique) :**
Elle doit permettre de logger chaque changement d'état (Qui, Quand, Quoi).

*Référence : Voir `backend/database/sql/database_creation.sql`.*

---

## Étape 2 : Couche Modèle (Models)

Créer les classes PHP représentant les entités en mémoire.

### Fichier : `backend/src/Models/Commande.php`

Propriétés à implémenter (typage strict PHP 8) :
```php
class Commande {
    public ?int $id;
    public int $userId;
    public int $menuId;
    public float $prixTotal;
    public string $statut;
    public bool $reductionAppliquee; // RG20
    public float $montantReduction;
    public array $detailsLivraison; // Objet ou Array (Adresse, Date, Tel)
    public array $pricingSnapshot; // (Prix unitaire, Reductions, Frais)
    // ... Getters & Setters
}
```

### Autres Modèles Requis :
*   `CommandeStatut.php` : Pour l'historique.
*   `CommandeMateriel.php` : Si gestion de prêt matériel.

---

## Étape 3 : Couche Accès Données (Repository)

Le pattern Repository isole les requêtes SQL.

### Fichier : `backend/src/Repositories/CommandeRepository.php`

**Note d'implémentation** : Ce repository doit gérer l'accès aux deux tables liées `COMMANDE` et `COMMANDE_STATUT` (Historique), conformément au diagramme UML qui centralise souvent la persistance d'un agrégat.

Méthodes obligatoires :
1.  **`create(Commande $commande): int`**
    *   Insérer dans `COMMANDE`.
    *   Déclencher le trigger ou insérer manuellement dans `COMMANDE_STATUT` (Initialisation).
2.  **`findById(int $id): ?Commande`**
    *   Retourner l'objet complet avec jointures (Menu, User).
3.  **`findAllByUserId(int $userId): array`**
    *   Pour l'historique client.
4.  **`updateStatus(int $id, string $newStatus, int $modifiedBy, string $motif): bool`**
    *   Mettre à jour `COMMANDE`.
    *   Insérer une ligne dans `COMMANDE_STATUT` (ou `COMMANDE_ANNULATION` si annulé).
6.  **`setMateriel(int $commandeId, array $materiels): void`**
    *   Gérer l'insertion dans `COMMANDE_MATERIEL` et la mise à jour des stocks.
7.  **`update(int $id, array $data, int $modifiedBy): bool`**
    *   Mettre à jour les champs autorisés dans `COMMANDE`.
    *   **Traçabilité** : Insérer une ligne dans `COMMANDE_MODIFICATION` avec les valeurs changées (format JSON), pour respecter le MLD.

---

## Étape 4 : Logique Métier (Service)

C'est le cœur de la feature. 

### Fichier : `backend/src/Services/CommandeService.php`

#### Fonctionnalité 1 : Calcul du Prix (`calculatePrice`)
*   **Entrée** : `menuId`, `nombrePersonnes`, `adresseLivraison`.
*   **Logique** :
    1.  Récupérer le Menu (Prix de base, Min Personnes).
    2.  Check quantité : `if (personnes < menu.min) throw Exception`.
    3.  Règle Réduction : `if (personnes >= min + 5) -10%`.
    4.  Frais Livraison :
        *   Appeler `GoogleMapsService->getDistance()`.
        *   **Strategie Fallback (Doc Tech)** : Si l'API Google Maps échoue ou est injoignable, utiliser une méthode d'`estimateDistance` (ex: calcul vol d'oiseau ou forfaitaire) pour ne pas bloquer la commande.
        *   **Règle MLD (RG21)** :
            *   Si Bordeaux (Code Postal "33000" ou ville "Bordeaux") : **5.00€ fixes**.
            *   Sinon (Hors Bordeaux) : **5.00€ + (0.59€ * distance_km)**.
*   **Sortie** : DTO avec le détail des coûts.

#### Fonctionnalité 2 : Création de Commande (`createCommande`)
*   **Entrée** : `userId`, `menuData`, `deliveryData`.
*   **Logique** :
    1.  **Validation** : Vérifier inputs, stocks, dates disponibles.
    2.  **Snapshot** : Figer les prix actuels (ne pas utiliser ID menu pour prix futur).
    3.  **Transaction SQL** :
        *   `START TRANSACTION`
        *   Insert Commande.
        *   Insert Historique.
        *   Update Stock Menu (si applicable).
        *   `COMMIT`.
    4.  **Sync MongoDB** (Optionnel/Async) : Pousser dans collection `statistiques_commandes` (RGPD: Anonymiser données perso).
    5.  **Notification** : Envoyer email confirmation via `MailerService`.

#### Fonctionnalité 3 : Gestion Statuts & Annulation
*   **Annulation Client** :
    *   Méthode : `cancelByUser(int $userId, int $commandeId)`
    *   **Règle stricte** : Possible UNIQUEMENT si statut actuel est `EN_ATTENTE`. Sinon `ForbiddenException`.
*   **Action Employé (Mise à jour Générique)** :
    *   Méthode : `updateStatus(int $employeId, int $commandeId, string $newStatus)`
    *   **Logique** :
        *   Mettre à jour statut SQL + Historique.
        *   **Sync MongoDB** : Mettre à jour le champ `status` dans `statistiques_commandes`.
        *   **Notification** : Envoyer un email de notification au client "Votre commande est passée à [Statut]".
*   **Action Employé (Annulation)** :
    *   Méthode : `cancelByEmployee(int $employeId, int $commandeId, string $motif, string $modeContact)`
    *   **Obligation** : Le motif et le mode de contact (`GSM`, `MAIL`) sont requis par l'énoncé.
*   **Flux Spécifique** :
    *   Statut `EN_ATTENTE_RETOUR` : Déclencher email de rappel pour restitution matériel (pénalité 600€ mentionnée).        *   Statut `TERMINEE` : Déclencher **"Email Invitation Avis"** invitant le client à noter sa commande (requis par l'énoncé).
#### Fonctionnalité 4 : Modification de Commande (Client)
*   **Méthode** : `update(int $userId, int $commandeId, array $newData)`
*   **Champs modifiables** : Adresse, date/heure, téléphone, nombre de personnes (recalcul du prix obligatoire).
*   **Interdit** : Changer le MENU (nécessite annulation + nouvelle commande, voir RG).
*   **Condition** :Possible UNIQUEMENT si statut est `EN_ATTENTE`.

#### Fonctionnalité 5 : Gestion du Matériel (Employé)
*   **Méthode** : `loanMaterial(int $commandeId, array $materiels)`
*   **Structure** : `$materiels` est un tableau de `['id' => int, 'quantite' => int]`.
*   **Logique** :
    *   Lier les matériels à la commande dans `COMMANDE_MATERIEL` (avec quantité et dates).
    *   Décrémenter le stock du matériel.
    *   Le statut de la commande passera plus tard par `EN_ATTENTE_RETOUR`.

#### Fonctionnalité 6 : Préparation pour les Avis (Point d'Extension)
*   **Objectif** : Permettre au Frontend de savoir quand afficher le bouton "Donner un avis".
*   **Implémentation** :
    *   Dans `listMyOrders` et `show`, le DTO de retour doit inclure un champ booléen `can_review`.
    *   **Logique** : `can_review = (statut == 'TERMINEE' && has_avis == false)`.
    *   *Note : La logique complète de création/validation des avis sera gérée dans une feature dédiée (Service `AvisService`), conformément au diagramme `sequence_04`.*

---

## Étape 5 : API & Contrôleurs (Controller)

Exposer les fonctionnalités via HTTP.

### Fichier : `backend/src/Controllers/CommandeController.php`

Méthodes :
*   `calculate(Request $request)` : POST /api/commandes/calculate-price
*   `create(Request $request)` : POST /api/commandes
*   `updateStatus(Request $request, $id)` : POST /api/commandes/{id}/status (Réservé aux employés).
*   `listMyOrders(Request $request)` : GET /api/commandes/me
*   `show(Request $request, $id)` : GET /api/commandes/{id} (Vérifier que l'user est propriétaire ou Admin).
*   `update(Request $request, $id)` : PUT /api/commandes/{id} (Modification client : adresse/date/nb_personnes uniquement).
*   `loanMaterial(Request $request, $id)` : POST /api/commandes/{id}/material (Employé uniquement).
*   `getTimeline(Request $request, $id)` : GET /api/commandes/{id}/timeline (Flux suivi commande : historique complet).

### Fichier : `backend/api/routes.commandes.php`

Définir les routes et appliquer les Middlewares :
*   `AuthMiddleware` : Obligatoire partout.
*   `CorsMiddleware` : Pour le frontend.

---

## Étape 6 : Intégration Frontend

1.  **Formulaire de Commande** :
    *   **Pré-remplissage** : Les champs Nom, Prénom, Mail, GSM doivent être pré-remplis avec les infos du compte utilisateur (Lecture seule ou editable selon besoin UX, mais requis par énoncé).
    *   Récapitulatif du Menu choisi.
    *   Champs: Date, Heure, Adresse (Autocomplete Google Maps si possible), Nombre personnes.
2.  **Mise à jour dynamique** :
    *   À chaque changement du nombre de personnes ou adresse -> Appel `calculate` -> Afficher nouveau prix total.
3.  **Confirmation** :
    *   Afficher un résumé clair AVANT validation finale.
    *   Gestion des erreurs (Date indisponible, hors zone, etc.).

---

## 🔐 Sécurité & RGPD

Cette section est critique pour la production.

1.  **Validation des Entrées** :
    *   Utiliser `CommandeValidator`.
    *   Sanitiser les adresses et commentaires (XSS).
    *   Vérifier que `date_prestation` > `NOW() + 24h` (règle métier).
2.  **Protection des Données (RGPD)** :
    *   **Minimisation** : Ne stocker que le nécessaire. Si l'adresse est celle du profil, ne pas la dupliquer inutilement sauf si l'adresse de livraison diffère.
    *   **Accès** : Un utilisateur ne doit JAMAIS pouvoir voir la commande d'un autre (Check ID dans Controller).
    *   **Logs** : Ne pas logger de données sensibles (Mots de passe, CB) dans les logs système.
3.  **Authentification** :
    *   Vérifier le token JWT à chaque requête.
4.  **SQL Injection** :
    *   Utiliser **toujours** les requêtes préparées (PDO) dans le Repository.

---

## 🧪 Stratégie de Tests

### Unit Tests (`tests/Services/CommandeServiceTest.php`)
*   Test calcul prix (nominal).
*   Test calcul réduction (-10%).
*   Test frais livraison (distance).
*   Test validation quantité < min.

### Integration Tests (`tests/Controllers/CommandeControllerTest.php`)
*   Flux complet création commande avec Mock base de données.
*   Tentative accès commande autrui (doit retourner 403 Forbidden).

---

## ✅ Checklist de Validation

Avant de merger la feature :
- [ ] Le prix facturé ne change pas si l'admin change le prix du menu le lendemain.
- [ ] L'historique trace bien "Créée par client X".
- [ ] L'email de confirmation part bien.
- [ ] Les frais kilométriques sont justes.
- [ ] Aucun champ HTML/JS n'est exécuté si injecté dans l'adresse.
