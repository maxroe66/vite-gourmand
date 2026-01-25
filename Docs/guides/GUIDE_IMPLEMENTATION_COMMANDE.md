# 📦 Guide d'Implémentation : Feature Commande & Matériel (Vite & Gourmand)

**Version :** 1.2.0 (Validée)  
**Date :** 25 Janvier 2026  
**Responsable :** Équipe Backend  
**Statut :** ✅ Implémentation Backend Terminée (En attente intégration Front)

---

## 📑 Table des Matières

1.  [Objectif](#objectif-de-la-feature)
2.  [Architecture & Cycle de Vie du Matériel](#architecture--cycle-de-vie-du-matériel)
3.  [Base de Données (Mise à jour)](#base-de-données)
4.  [Logique Métier Automatisée](#logique-métier-automatisée)
5.  [Flux de Communication (Emails)](#flux-de-communication-emails)
6.  [Endpoints API (Référence)](#endpoints-api-référence)
7.  [Intégration Frontend (Instructions)](#intégration-frontend-instructions)

---

## 🎯 Objectif de la Feature

Gérer de bout en bout le cycle de vie des commandes traiteur, incluant la complexité du **prêt de matériel** (Vaisselle, Appareils à fondue, etc.). 
Le système doit assurer que le stock est toujours exact, que les statuts de commande reflètent la réalité (Retours en attente), et que les clients sont notifiés de leurs engagements (Caution 600€).

---

## 🏗 Architecture & Cycle de Vie du Matériel

Le module matériel repose sur 3 piliers :
1.  **Configuration (Menu)** : Le matériel est défini *par défaut* dans le menu (ex: "Menu Fondue" inclut automatiquement "1 Appareil").
2.  **Sortie (Commande)** : À la commande, le matériel est réservé et déstocké **automatiquement**.
3.  **Entrée (Retour)** : L'employé valide manuellement le retour physique, ce qui clôture la commande.

---

## 💾 Base de Données

Le schéma relationnel a été mis à jour pour supporter cette logique :

### 1. `MENU_MATERIEL` (Nouvelle Table)
Définit le "Kit" matériel associé à un menu.
*   `id_menu`, `id_materiel`, `quantite`

### 2. `COMMANDE_MATERIEL` (Log)
Trace chaque objet prêté pour une commande spécifique.
*   `id_commande`, `id_materiel`, `quantite`
*   `date_pret` (Automatique à la création)
*   `date_retour_prevu` (J+10 par défaut)
*   `date_retour_effectif` (**CRITIQUE** : NULL tant que pas rendu)

### 3. `MATERIEL` (Stock)
*   `stock_disponible` : Compteur temps réel. Décrémenté à la commande, Incrémenté au retour.

---

## 🧠 Logique Métier Automatisée

### 1. Création de Commande (`CommandeService::createCommande`)
*   Le système vérifie si le Menu choisi a du matériel associé (`MENU_MATERIEL`).
*   Si OUI -> Appelle `loanMaterial()` automatiquement.
*   **Résultat** : Stock -1, Commande flag `materiel_pret=1`.

### 2. Retour de Matériel (`CommandeService::returnMaterial`)
Action manuelle déclenchée par l'employé quand le client ramène le matériel.
*   Vérifie les lignes `COMMANDE_MATERIEL` non rendues.
*   Met à jour `date_retour_effectif = NOW()`.
*   **Résultat** : Stock +1, Commande passe à `TERMINEE`.

---

## 📧 Flux de Communication (Emails)

Les notifications sont désormais **transactionnelles** et automatiques :

| Événement | Template Email | Contenu Clé | Statut |
|-----------|----------------|-------------|--------|
| **Commande (Création)** | `material_loan.html` | ✅ Liste html du matériel emprunté<br>⚠️ Avertissement délai 10j | Implémenté |
| **Passage Statut** `EN_ATTENTE_RETOUR` | `material_return_alert.html` | 🚨 **ALERTE CAUTION 600€**<br>Rappel date butoir | Implémenté |
| **Validation Retour** | `material_return_confirmation.html` | ✅ Confirmation de réception<br>Clôture dossier | Implémenté |

---

## 🔌 Endpoints API (Référence pour Frontend)

### 1. 🟢 Gestion Matériel (Nouveau)

#### **Valider le Retour Matériel (Employé)**
Permet de clôturer une commande "matériel" et remonter le stock.
*   **POST** `/api/commandes/{id}/return-material`
*   **Auth** : Employé / Admin
*   **Effet** : Passe commande à `TERMINEE`.

#### **Ajout Manuel Matériel (Employé - Cas Exceptionnel)**
Si l'employé veut ajouter un truc en plus hors menu.
*   **POST** `/api/commandes/{id}/material`
*   **Body** : `[{ "id": 10, "quantite": 1 }]`

### 2. 🟢 Configuration Menu (Mise à jour)

#### **Créer/Modifier Menu avec Matériel**
*   **POST/PUT** `/api/menus`
*   **Body** : 
    ```json
    {
      "titre": "Menu Raclette",
      "prix": 25,
      "materiels": [
        { "id": 5, "quantite": 1 } 
      ]
    }
    ```

---

## 🎨 Intégration Frontend (Instructions)

### Pour le Dashboard Employé :
1.  **Page "Gestion Menus"** : Ajouter un sélecteur multiple de matériel dans le formulaire de création de menu (comme pour les plats).
2.  **Page "Commandes"** :
    *   Si la commande a `materiel_pret = 1` et n'est pas `TERMINEE`.
    *   Afficher un bouton **"📦 Valider Retour Matériel"**.
    *   Ce bouton doit appeler `POST /api/commandes/{id}/return-material`.

### Pour le Profil Client :
1.  **Détail Commande** : Afficher la liste du matériel emprunté (récupérable via `GET /api/commandes/{id}`).
2.  **Alerte** : Si statut `EN_ATTENTE_RETOUR`, afficher un bandeau rouge : *"En attente de restitution sous 10j"*.