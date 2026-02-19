# Manuel d'utilisation — Vite & Gourmand

> **Version :** 1.1  
> **Date :** 19 février 2026  
> **Auteur :** Max Roe  
> **Public cible :** Utilisateurs finaux (clients), Employés, Administrateurs

---

## Table des matières

1. [Présentation de l'application](#1-présentation-de-lapplication)
2. [Accès à l'application](#2-accès-à-lapplication)
3. [Parcours visiteur (non connecté)](#3-parcours-visiteur-non-connecté)
4. [Créer un compte](#4-créer-un-compte)
5. [Se connecter / Se déconnecter](#5-se-connecter--se-déconnecter)
6. [Consulter les menus](#6-consulter-les-menus)
7. [Passer une commande](#7-passer-une-commande)
8. [Suivre ses commandes](#8-suivre-ses-commandes)
9. [Laisser un avis](#9-laisser-un-avis)
10. [Modifier son profil](#10-modifier-son-profil)
11. [Réinitialiser son mot de passe](#11-réinitialiser-son-mot-de-passe)
12. [Contacter l'entreprise](#12-contacter-lentreprise)
13. [Mentions légales et CGV](#13-mentions-légales-et-cgv)
14. [Espace de gestion (Employés et Administrateurs)](#14-espace-de-gestion-employés-et-administrateurs)
15. [Accessibilité](#15-accessibilité)
16. [Comptes de démonstration](#16-comptes-de-démonstration)

---

## 1. Présentation de l'application

**Vite & Gourmand** est une application web de traiteur/catering basée à Bordeaux. Elle permet de :

- **Consulter** les menus proposés (composition, prix, allergènes)
- **Commander** des prestations traiteur pour vos événements
- **Suivre** l'état de vos commandes en temps réel
- **Laisser des avis** après une prestation

L'application est accessible depuis tout navigateur web moderne (Chrome, Firefox, Safari, Edge) sur ordinateur, tablette et mobile.

---

## 2. Accès à l'application

| Environnement | URL |
|---|---|
| **Production** | `https://vite-et-gourmand.me` |
| **Développement local** | `http://localhost:8000` |

### Navigation principale

La **barre de navigation** (en haut de chaque page) propose :

- **Logo** : retour à la page d'accueil
- **Accueil** : page d'accueil
- **Menu** : accès direct à la section des menus
- **Contact** : formulaire de contact

Si vous n'êtes pas connecté, deux boutons s'affichent :
- **Inscription** : créer un compte
- **Connexion** : accéder à votre espace

Si vous êtes connecté, la barre affiche :
- **Mon Profil** : accès à votre espace personnel
- **Déconnexion** : se déconnecter
- **Espace Gestion** : visible uniquement pour les Employés et Administrateurs

> **Sur mobile**, le menu est accessible via l'icône hamburger (☰) en haut à droite.

---

## 3. Parcours visiteur (non connecté)

En tant que visiteur, vous pouvez :

1. **Parcourir la page d'accueil** :
   - Découvrir l'entreprise via le cube 3D interactif (Notre Histoire, Nos Engagements...)
   - Lire les avis clients dans le carrousel dédié
   - Explorer les menus disponibles avec les filtres (prix, thème, régime, nombre de personnes)

2. **Consulter le détail d'un menu** en cliquant sur sa carte

3. **Contacter l'entreprise** via le formulaire de contact

4. **Consulter les mentions légales** et CGV via le lien du pied de page

> Pour passer une commande, vous devez créer un compte ou vous connecter.

---

## 4. Créer un compte

1. Cliquez sur **« Inscription »** dans la barre de navigation
2. Remplissez le formulaire :

   | Champ | Format attendu | Obligatoire |
   |---|---|---|
   | Prénom | Lettres uniquement, max 100 caractères | ✅ |
   | Nom | Lettres uniquement, max 100 caractères | ✅ |
   | Email | Format email valide (ex: jean.dupont@email.fr) | ✅ |
   | Mot de passe | Minimum 10 caractères, avec majuscule, minuscule, chiffre et caractère spécial | ✅ |
   | GSM / Téléphone | 10 à 20 chiffres | ✅ |
   | Adresse | Minimum 5 caractères | ✅ |
   | Ville | Max 100 caractères | ✅ |
   | Code postal | 5 chiffres (ex: 33000) | ✅ |

3. Observez la **jauge de force** sous le champ mot de passe : elle devient verte quand le mot de passe est suffisamment robuste

4. Cliquez sur le bouton œil (👁) à droite du champ mot de passe pour afficher/masquer le texte

5. Cliquez sur **« Créer mon compte »**

6. Vous êtes automatiquement connecté et redirigé vers la page d'accueil. Un **email de bienvenue** est envoyé.

---

## 5. Se connecter / Se déconnecter

### Connexion

1. Cliquez sur **« Connexion »** dans la barre de navigation
2. Saisissez votre **email** et votre **mot de passe**
3. Cliquez sur **« Connexion »**
4. Vous êtes redirigé vers la page d'accueil

### Déconnexion

1. Cliquez sur **« Déconnexion »** dans la barre de navigation
2. Vous êtes déconnecté et redirigé vers la page d'accueil

> **Durée de session :** votre session est valide pendant **1 heure**. Passé ce délai, vous serez invité à vous reconnecter.

---

## 6. Consulter les menus

### Depuis la page d'accueil

1. Faites défiler la page jusqu'à la section **« Nos Menus »** (ou cliquez sur « Menu » dans la navigation)
2. Utilisez les **filtres** pour affiner votre recherche :
   - **Prix minimum** : définir un prix plancher
   - **Prix maximum** : définir un budget plafond
   - **Thème** : type d'événement (Mariage, Entreprise, Anniversaire…)
   - **Régime** : préférence alimentaire (Végétarien, Végan, Sans gluten…)
   - **Nombre de personnes minimum** : capacité requise
3. Parcourez les menus avec les flèches **◀ / ▶** du carrousel
4. Cliquez sur une **carte de menu** pour voir son détail

### Page détail d'un menu

La page de détail affiche :

- **Galerie d'images** : naviguez avec les boutons « Précédent » / « Suivant »
- **Titre** et **description** du menu
- **Composition** : liste des plats inclus (entrées, plats principaux, desserts)
- **Allergènes** : substances allergènes présentes dans le menu
- **Prix** : au format « XX€ / X personnes » (prix de base pour le nombre minimum de convives)
- **Thème** et **Régime** : catégorisation du menu
- **Stock** : disponibilité du menu
- **Conditions** : informations complémentaires éventuelles

Pour commander :
1. Cliquez sur le bouton **« Commander »**
2. Vous êtes redirigé vers le formulaire de commande (connexion requise)

---

## 7. Passer une commande

> **Prérequis :** être connecté.

1. Depuis la page détail d'un menu, cliquez sur **« Commander »**
2. Remplissez le formulaire de commande :

   **Informations du client (auto-remplies depuis votre compte) :**
   | Champ | Description |
   |---|---|
   | Nom | Votre nom (lecture seule) |
   | Prénom | Votre prénom (lecture seule) |
   | Email | Votre adresse email (lecture seule) |

   **Informations de livraison :**
   | Champ | Description |
   |---|---|
   | Adresse de livraison | L’adresse complète de l’événement |
   | Code postal | Code postal de la ville (5 chiffres) |
   | Ville | Ville de livraison |
   | Téléphone / GSM | Numéro pour le jour J (10-15 chiffres, auto-rempli) |

   **Détails de la prestation :**
   | Champ | Description |
   |---|---|
   | Date de la prestation | Choisir une date dans le calendrier |
   | Heure souhaitée | L'heure de début de votre événement |
   | Nombre de personnes | Le nombre de convives (minimum imposé par le menu) |

3. Le **récapitulatif** (colonne de droite) se met à jour en temps réel :
   - Prix de base du menu
   - Nombre de personnes × prix unitaire
   - **Réduction -10%** : appliquée automatiquement si vous commandez pour au moins 5 personnes de plus que le minimum du menu
   - **Frais de livraison** : gratuits pour Bordeaux, sinon 5€ + 0,59€/km
   - **Total à payer**

4. Cliquez sur **« Valider la commande »**

5. Un **email de confirmation** est envoyé. Votre commande est en statut **« En attente »**.

### Astuce réduction

> Invitez **5 personnes de plus** que le nombre minimum du menu pour bénéficier automatiquement d'une **réduction de 10%** sur le prix du menu !

---

## 8. Suivre ses commandes

1. Cliquez sur **« Mon Profil »** dans la barre de navigation
2. L'onglet **« Mes Commandes »** est affiché par défaut
3. Chaque commande affiche :
   - Le nom du menu commandé
   - La date de la prestation
   - Le statut actuel
   - Le montant total

4. Cliquez sur une commande pour voir son **détail complet** (modal)

### Statuts possibles

| Statut | Signification |
|---|---|
| 🟡 **En attente** | Commande reçue, en cours de validation par l'équipe |
| 🟢 **Acceptée** | Commande validée par l'équipe |
| 🔵 **En préparation** | Le menu est en cours de préparation en cuisine |
| 🚚 **En livraison** | La prestation est en cours de livraison |
| ✅ **Livrée** | La prestation a été livrée |
| 📦 **En attente retour matériel** | Du matériel prêté doit être restitué (délai 10 jours ouvrés) |
| ✔️ **Terminée** | Prestation entièrement finalisée |
| ❌ **Annulée** | Commande annulée |

### Modifier une commande

Tant que votre commande est en statut **« En attente »**, vous pouvez la modifier :
1. Cliquez sur votre commande → détail
2. Cliquez sur **« Modifier »**
3. Modifiez l'adresse, la ville, le code postal, le nombre de personnes ou la date
4. Cliquez sur **« Enregistrer les modifications »**

### Annuler une commande

Tant que votre commande est en statut **« En attente »**, vous pouvez l'annuler librement. Au-delà de ce statut, l'annulation est gérée par l'équipe.

---

## 9. Laisser un avis

> **Prérequis :** avoir une commande en statut **« Terminée »**.

Un email d'invitation à laisser un avis est envoyé automatiquement lorsque votre commande est finalisée.

1. Allez dans **« Mon Profil »** → onglet **« Mes Commandes »**
2. Sur une commande terminée, cliquez sur **« Laisser un avis »**
3. Dans la fenêtre qui s'ouvre :
   - Sélectionnez une **note** de 1 à 5 étoiles (cliquez sur les étoiles)
   - Rédigez votre **commentaire**
4. Cliquez sur **« Envoyer »**

Votre avis est soumis à validation par l'équipe avant d'apparaître publiquement sur la page d'accueil.

---

## 10. Modifier son profil

1. Cliquez sur **« Mon Profil »** dans la barre de navigation
2. Cliquez sur l'onglet **« Mon Profil »**
3. Modifiez les informations souhaitées :
   - Prénom, Nom
   - Téléphone
   - Adresse, Ville, Code postal

   > L'adresse **email** n'est **pas modifiable** pour des raisons de sécurité.

4. Cliquez sur **« Enregistrer les modifications »**

---

## 11. Réinitialiser son mot de passe

Si vous avez oublié votre mot de passe :

1. Depuis la page **Connexion**, cliquez sur **« Mot de passe oublié ? »**
2. Saisissez votre **adresse email** dans la fenêtre qui s'ouvre
3. Cliquez sur **« Envoyer le lien »**
4. Consultez votre boîte de réception (vérifiez aussi les spams)
5. Cliquez sur le **lien de réinitialisation** dans l'email
6. Saisissez votre **nouveau mot de passe** et confirmez-le
7. Cliquez sur **« Réinitialiser mon mot de passe »**

> Le lien de réinitialisation est **à usage unique** et a une durée de validité limitée.

---

## 12. Contacter l'entreprise

### Via le formulaire de contact

1. Cliquez sur **« Contact »** dans la barre de navigation
2. Remplissez :
   - **Titre** : sujet de votre demande (ex: « Demande de devis pour un événement »)
   - **Adresse email** : votre email de contact
   - **Votre message** : décrivez votre demande
3. Cliquez sur **« Envoyer le message »**

### Contact direct

| Moyen | Coordonnées |
|---|---|
| Email | contact@vite-et-gourmand.me |
| Téléphone | 05 56 00 00 00 |
| Adresse | Bordeaux, 33000, Gironde, France |

### Horaires

L'entreprise est joignable du **lundi au dimanche, de 9h à 19h**.

---

## 13. Mentions légales et CGV

Accessible via le lien **« Mentions légales »** dans le pied de page de chaque page.

Cette page contient :
- **Mentions légales** : éditeur du site, hébergeur, propriété intellectuelle
- **Politique de cookies** : seuls 2 cookies techniques sont utilisés (`authToken` pour la session, `csrfToken` pour la sécurité) — aucun cookie publicitaire
- **CGU** : conditions générales d'utilisation
- **CGV** : conditions générales de vente, incluant notamment :
  - Les modalités de commande et de paiement
  - Les conditions de livraison
  - Le **prêt de matériel** : restitution sous 10 jours ouvrés, **pénalité de 600 €** en cas de non-restitution
  - Le droit de rétractation et les réclamations
  - Les informations sur les allergènes

---

## 14. Espace de gestion (Employés et Administrateurs)

> **Accès :** Cliquez sur **« Espace Gestion »** dans la barre de navigation (visible uniquement pour les rôles Employé et Administrateur).

L'espace de gestion se présente sous forme d'un **tableau de bord avec un menu latéral** permettant de naviguer entre les différents modules.

### 14.1 Gestion des Menus

| Action | Description |
|---|---|
| **Voir la liste** | Tableau de tous les menus (titre, prix, stock, thème, régime, état actif) |
| **Créer un menu** | Bouton « + Nouveau menu » → formulaire avec titre, description, prix, nombre minimum de personnes, stock, thème, régime, sélection des plats par catégorie, matériel inclus, galerie d'images |
| **Modifier un menu** | Bouton « Modifier » sur une ligne → même formulaire pré-rempli |
| **Activer/Désactiver** | Toggle d'activation d'un menu |

### 14.2 Gestion des Plats

| Action | Description |
|---|---|
| **Voir la liste** | Tableau de tous les plats (libellé, type, allergènes) |
| **Créer un plat** | Bouton « + Nouveau plat » → libellé, description, type (Entrée/Plat Principal/Dessert), allergènes (checkboxes) |
| **Modifier un plat** | Bouton « Modifier » → formulaire pré-rempli |
| **Supprimer un plat** | Bouton « Supprimer » (avec confirmation) |

### 14.3 Gestion des Commandes

| Action | Description |
|---|---|
| **Voir les commandes** | Tableau de toutes les commandes (client, menu, date, statut, montant) |
| **Filtrer les commandes** | Par statut (En attente, Acceptée, etc.) et/ou par client (recherche par nom ou email) |
| **Détail d'une commande** | Informations complètes (adresse livraison, horaire, nombre de personnes, prix détaillé) |
| **Changer le statut** | Progression dans le workflow : En attente → Acceptée → En préparation → En livraison → Livrée → Terminée |
| **Annuler une commande** | Après acceptation : motif et mode de contact (GSM/Mail) obligatoires |
| **Gérer le matériel** | Enregistrer le prêt et le retour de matériel |

### 14.4 Gestion des Avis

| Action | Description |
|---|---|
| **Voir les avis** | Liste de tous les avis (client, note, commentaire, statut, date) |
| **Valider un avis** | L'avis apparaîtra publiquement sur la page d'accueil |
| **Refuser un avis** | L'avis ne sera pas publié |

### 14.5 Gestion du Matériel

| Action | Description |
|---|---|
| **Voir le matériel** | Liste du matériel disponible (nom, description, quantité, état) |
| **Créer/Modifier** | Ajouter ou modifier une fiche matériel |
| **Suivi des prêts** | Visualiser les matériels prêtés et les retards de restitution |

### 14.6 Gestion des Horaires

| Action | Description |
|---|---|
| **Voir les horaires** | Horaires d'ouverture par jour de la semaine |
| **Modifier** | Mettre à jour les horaires pour chaque jour |

### 14.7 Gestion de l'Équipe (Administrateur uniquement)

| Action | Description |
|---|---|
| **Voir les employés** | Liste des membres de l'équipe (nom, email, rôle, statut) |
| **Créer un employé** | Formulaire de création d'un compte employé. Un **email de notification** est envoyé automatiquement à l'employé avec son identifiant (email). Le mot de passe **n'est pas communiqué** dans l'email — l'administrateur doit le transmettre en personne |
| **Désactiver un compte** | Désactivation d'un compte (le compte n'est pas supprimé) |

> Seul un **Administrateur** peut accéder à cet onglet. Les employés ne le voient pas dans le menu.
> ⚠️ **Il n'est pas possible de créer un compte Administrateur depuis l'application.** Le compte administrateur initial est provisionné lors du déploiement.

### 14.8 Statistiques (Administrateur uniquement)

| Donnée | Description |
|---|---|
| **Commandes** | Nombre total, par statut, par période |
| **Chiffre d'affaires** | Montant total, évolution dans le temps |
| **Menus populaires** | Classement des menus les plus commandés |
| **Avis** | Note moyenne, répartition des notes |

Les statistiques sont présentées sous forme de **graphiques interactifs** (Chart.js).

---

## 15. Accessibilité

L'application Vite & Gourmand s'engage à respecter les bonnes pratiques d'accessibilité conformément au **Référentiel Général d'Amélioration de l'Accessibilité (RGAA)** :

- **Navigation au clavier** : tous les éléments interactifs (boutons, liens, formulaires) sont accessibles via la touche Tab
- **Attributs ARIA** : les composants dynamiques (navigation mobile, carrousels, modales) utilisent des attributs `aria-label`, `aria-expanded`, `aria-hidden` et `aria-controls` pour les technologies d'assistance
- **Focus visible** : un indicateur visuel de focus est présent sur tous les éléments interactifs
- **Langue de la page** : l'attribut `lang="fr"` est défini sur toutes les pages
- **Textes alternatifs** : les images disposent d'attributs `alt` descriptifs
- **Formulaires accessibles** : les champs sont associés à leurs labels et les erreurs de validation sont signalées via `aria-invalid` et `aria-describedby`
- **Contraste** : les couleurs respectent un ratio de contraste suffisant pour la lisibilité

> L'application vise un niveau de conformité partiel au RGAA. Des améliorations continues sont prévues pour renforcer l'accessibilité.

---

## 16. Comptes de démonstration

Pour tester l'application, les comptes suivants sont disponibles en environnement de développement :

| Rôle | Email | Mot de passe | Accès |
|---|---|---|---|
| **Administrateur** | `jose@vite-gourmand.fr` | `Password123!` | Toutes les fonctionnalités + Gestion équipe + Statistiques |
| **Employé** | `julie@vite-gourmand.fr` | `Password123!` | Gestion menus, plats, commandes, avis, matériel, horaires |
| **Utilisateur** | `marie.dupont@email.fr` | `Password123!` | Consultation, commandes, avis, profil |

> Ces comptes sont créés par les fixtures de base de données (`database_fixtures.sql`). En production, seul le compte administrateur initial est provisionné automatiquement via le pipeline CI/CD.

---

*Document mis à jour le 19 février 2026.*
