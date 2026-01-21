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
*   `status` (Enum) : EN_ATTENTE, ACCEPTE, ENV_LIVRAISON...

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
        *   Si Bordeaux (Distance < X ou Code Postal) : Gratuit? (Vérifier règles).
        *   Sinon : `5€ + (0.59€ * km)`.
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
    4.  **Sync MongoDB** (Optionnel/Async) : Pousser dans collection `analytics_commandes` (RGPD: Anonymiser données perso).
    5.  **Notification** : Envoyer email confirmation via `MailerService`.

---

## Étape 5 : API & Contrôleurs (Controller)

Exposer les fonctionnalités via HTTP.

### Fichier : `backend/src/Controllers/CommandeController.php`

Méthodes :
*   `calculate(Request $request)` : POST /api/commandes/actions/calculate
*   `create(Request $request)` : POST /api/commandes
*   `listMyOrders(Request $request)` : GET /api/commandes/me
*   `show(Request $request, $id)` : GET /api/commandes/{id} (Vérifier que l'user est propriétaire ou Admin).

### Fichier : `backend/api/routes.commandes.php`

Définir les routes et appliquer les Middlewares :
*   `AuthMiddleware` : Obligatoire partout.
*   `CorsMiddleware` : Pour le frontend.

---

## Étape 6 : Intégration Frontend

1.  **Formulaire de Commande** :
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
