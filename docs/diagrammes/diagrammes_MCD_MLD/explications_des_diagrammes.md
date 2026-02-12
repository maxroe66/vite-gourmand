# Documentation Complète des Modèles de Données
## Projet : Vite & Gourmand

---

## Table des matières
1. [Introduction](#introduction)
2. [Modèle Conceptuel de Données (MCD)](#modèle-conceptuel-de-données-mcd)
   - [Définition et objectifs du MCD](#définition-et-objectifs-du-mcd)
   - [Entités métier identifiées](#entités-métier-identifiées)
   - [Associations et cardinalités conceptuelles](#associations-et-cardinalités-conceptuelles)
   - [Règles de gestion métier](#règles-de-gestion-métier)
3. [Modèle Logique de Données (MLD)](#modèle-logique-de-données-mld)
   - [Vue d'ensemble du MLD](#vue-densemble-du-mld)
   - [Détail des tables et choix de conception](#détail-des-tables-et-choix-de-conception)
   - [Relations et cardinalités](#relations-et-cardinalités)
   - [Contraintes et règles métier](#contraintes-et-règles-métier)
4. [Justifications techniques](#justifications-techniques)
5. [Passage du MCD au MLD](#passage-du-mcd-au-mld)

---

## Introduction

Ce document explique en détail la conception des bases de données pour l'application "Vite & Gourmand", en couvrant **deux niveaux de modélisation** :

1. **MCD (Modèle Conceptuel)** : Vision métier pure, indépendante de toute technologie
2. **MLD (Modèle Logique)** : Traduction relationnelle avec contraintes SQL

### Méthodologie adoptée
1. **Analyse des besoins** : Lecture approfondie de l'énoncé pour identifier toutes les entités et leurs relations
2. **Modélisation conceptuelle (MCD)** : Identification des entités métier et associations
3. **Modélisation logique (MLD)** : Traduction en tables relationnelles
4. **Normalisation** : Application des formes normales (3NF) pour éviter la redondance
5. **Ajout des contraintes** : Définition des règles métier via contraintes SQL
6. **Traçabilité** : Mise en place d'un système d'historisation complet

---

## Modèle Conceptuel de Données (MCD)

### Définition et objectifs du MCD

Le **Modèle Conceptuel de Données (MCD)** représente la **vision métier pure** du système d'information, indépendamment de toute considération technique ou technologique.

**Objectifs du MCD :**
- 🎯 Identifier les **entités** (objets métier : Client, Menu, Commande...)
- 🔗 Définir les **associations** entre entités (Un client passe des commandes)
- 📊 Établir les **cardinalités** (combien ? un seul ou plusieurs ?)
- 📋 Formaliser les **règles de gestion** métier

**Différence MCD vs MLD :**

| Aspect | MCD (Conceptuel) | MLD (Logique) |
|--------|------------------|---------------|
| **Vocabulaire** | Entités, Associations | Tables, Clés étrangères |
| **Types** | Abstrait (string, int) | Précis (VARCHAR(100), INT) |
| **Clés** | Implicites | Explicites (PK, FK) |
| **Contraintes SQL** | Absentes | CHECK, NOT NULL, DEFAULT |
| **Détails techniques** | Aucun | Index, Triggers, Vues |

---

### Entités métier identifiées

Voici les **12 entités principales** issues de l'analyse de l'énoncé :

#### 1. **UTILISATEUR**
**Description** : Personne utilisant l'application (client, employé ou administrateur)

**Attributs métier :**
- Identité : nom, prénom
- Contact : email, téléphone (GSM), adresse postale
- Authentification : mot de passe
- Rôle : UTILISATEUR / EMPLOYE / ADMINISTRATEUR
- État : actif ou désactivé

**Justification énoncé :**
> "Un visiteur peut se créer un compte, pour cela, il devra communiquer des informations : Nom ainsi que le prénom, Numéro de GSM, Adresse mail et postale"

---

#### 2. **MENU**
**Description** : Offre culinaire proposée par l'entreprise pour un événement

**Attributs métier :**
- Présentation : titre, description
- Tarification : prix pour nombre minimum de personnes
- Disponibilité : stock disponible, actif/inactif
- Conditions : délais de commande, précautions

**Justification énoncé :**
> "un menu dispose des caractéristiques suivantes : Un titre, Une galerie d'image, Une description, Thème (Noel, Pâques...), Un nombre de personne minimale, Le prix pour le nombre de personne minimale"

---

#### 3. **THEME**
**Description** : Catégorie événementielle du menu (Noël, Pâques, classique, événement)

**Justification énoncé :**
> "Thème (Noel, Pâques, classique, évènement)"

---

#### 4. **REGIME**
**Description** : Type d'alimentation proposé (végétarien, vegan, classique)

**Justification énoncé :**
> "Un Regime (vegetarien, vegan, classique) : vous pouvez alimenter d'avantage cette catégorie"

---

#### 5. **PLAT**
**Description** : Élément composant un menu (entrée, plat principal ou dessert)

**Attributs métier :**
- Identification : libellé, type (ENTREE/PLAT/DESSERT)
- Description : détails du plat

**Justification énoncé :**
> "Une liste de plat possible (entrée, plat ainsi que dessert)"
> "Une entrée ou un plat / dessert peuvent être présent dans plusieurs menus"

---

#### 6. **ALLERGENE**
**Description** : Substance allergène présente dans un plat (obligation légale européenne)

**Justification énoncé :**
> "Chaque plat peut posséder une liste d'allergènes"

---

#### 7. **COMMANDE**
**Description** : Demande de prestation passée par un client

**Attributs métier :**
- Identification : date de commande
- Livraison : adresse, ville, date/heure de prestation
- Tarification : nombre de personnes, prix total, réduction, frais de livraison
- Suivi : statut (EN_ATTENTE → TERMINEE)

**Justification énoncé :**
> "Il est possible de commander un menu [...] il sera demandé les informations de la prestation : Nom, mail et prénom du client, Adresse et date de la prestation, Heure souhaitée de livraison"

---

#### 8. **MATERIEL**
**Description** : Équipement prêté aux clients pour la prestation

**Attributs métier :**
- Identification : libellé, description
- Gestion : valeur unitaire, stock disponible

**Justification énoncé :**
> "en attente du retour de matériel : si du matériel a été prêté au client. Il doit le restituer"

---

#### 9. **AVIS**
**Description** : Évaluation laissée par un client après une prestation

**Attributs métier :**
- Évaluation : note (1 à 5), commentaire
- Modération : statut de validation (EN_ATTENTE/VALIDE/REFUSE)
- Traçabilité : date d'avis, date de validation

**Justification énoncé :**
> "Il doit pouvoir donner entre note entre 1 et 5, suivi d'un commentaire"
> "L'employé peut également, valider les avis reçus par les utilisateurs afin qu'ils soient visibles sur la page d'accueil"

---

#### 10. **HORAIRE**
**Description** : Planning d'ouverture de l'entreprise (lundi à dimanche)

**Justification énoncé :**
> "Les horaires doivent être visible sur le pied de page, du lundi au dimanche"

---

#### 11. **CONTACT**
**Description** : Message envoyé par un visiteur via le formulaire de contact

**Attributs métier :**
- Contenu : titre, description, email
- Suivi : date d'envoi, traité ou non

**Justification énoncé :**
> "Un visiteur peut contacter l'entreprise [...] un formulaire qui va lui demander un titre, une description ainsi que son mail"

---

#### 12. **IMAGE_MENU**
**Description** : Photographie illustrant un menu (galerie)

**Attributs métier :**
- Fichier : URL de l'image
- Accessibilité : texte alternatif (RGAA)
- Ordre : position dans la galerie

**Justification énoncé :**
> "Une galerie d'image"

---

### Associations et cardinalités conceptuelles

#### Notation des cardinalités

**Format utilisé : (min, max)**
- `(1,1)` : exactement un
- `(0,1)` : zéro ou un
- `(1,N)` : un ou plusieurs (N = "many" = plusieurs)
- `(0,N)` : zéro ou plusieurs
- `(N,M)` : plusieurs à plusieurs

**Note :** `(1,N)` et `(1,M)` signifient la même chose ("un à plusieurs"). On utilise M quand N est déjà utilisé dans la relation.

---

#### Associations principales

##### 1. **Un UTILISATEUR passe des COMMANDES**
```
UTILISATEUR (1,1) ----< (0,N) COMMANDE
```
- **Lecture** : Un utilisateur peut passer zéro, une ou plusieurs commandes
- **Lecture inverse** : Une commande est passée par un et un seul utilisateur
- **Règle métier** : On doit toujours savoir qui a commandé

---

##### 2. **Un MENU est commandé dans des COMMANDES**
```
MENU (1,1) ----< (0,N) COMMANDE
```
- **Lecture** : Un menu peut être commandé zéro, une ou plusieurs fois
- **Lecture inverse** : Une commande porte sur un et un seul menu
- **Règle métier** : Pas de commande multi-menus

---

##### 3. **Un THEME catégorise des MENUS**
```
THEME (1,1) ----< (0,N) MENU
```
- **Lecture** : Un thème peut catégoriser plusieurs menus
- **Lecture inverse** : Un menu appartient à un seul thème
- **Règle métier** : Un menu Noël ne peut pas être aussi Pâques

---

##### 4. **Un REGIME catégorise des MENUS**
```
REGIME (1,1) ----< (0,N) MENU
```
- **Lecture** : Un régime peut catégoriser plusieurs menus
- **Lecture inverse** : Un menu appartient à un seul régime
- **Règle métier** : Un menu ne peut pas être à la fois végétarien ET vegan

---

##### 5. **Un MENU propose des PLATS (N:M)**
```
MENU (1,N) >----< (1,M) PLAT
```
- **Lecture** : Un menu propose plusieurs plats
- **Lecture inverse** : Un plat peut être présent dans plusieurs menus
- **Règle métier** : "Une entrée ou un plat / dessert peuvent être présent dans plusieurs menus"
- **Association ternaire** : Contient aussi la position du plat dans le menu

---

##### 6. **Un PLAT contient des ALLERGENES (N:M)**
```
PLAT (0,N) >----< (0,M) ALLERGENE
```
- **Lecture** : Un plat peut contenir zéro ou plusieurs allergènes
- **Lecture inverse** : Un allergène peut être présent dans plusieurs plats
- **Règle métier** : Obligation légale d'informer (sécurité alimentaire)

---

##### 7. **Un MENU possède des IMAGES**
```
MENU (1,1) ----< (1,N) IMAGE_MENU
```
- **Lecture** : Un menu possède une ou plusieurs images (galerie)
- **Lecture inverse** : Une image appartient à un seul menu
- **Règle métier** : "Une galerie d'image"

---

##### 8. **Une COMMANDE emprunte du MATERIEL (N:M)**
```
COMMANDE (0,N) >----< (0,M) MATERIEL
```
- **Lecture** : Une commande peut emprunter zéro ou plusieurs matériels
- **Lecture inverse** : Un matériel peut être prêté dans plusieurs commandes
- **Règle métier** : Gestion des dates de prêt/retour

---

##### 9. **Une COMMANDE reçoit un AVIS**
```
COMMANDE (1,1) ----< (0,1) AVIS
```
- **Lecture** : Une commande peut recevoir zéro ou un avis
- **Lecture inverse** : Un avis est lié à une seule commande
- **Règle métier** : Un client peut donner un avis après prestation terminée

---

##### 10. **Un UTILISATEUR rédige des AVIS**
```
UTILISATEUR (1,1) ----< (0,N) AVIS
```
- **Lecture** : Un utilisateur peut rédiger plusieurs avis
- **Lecture inverse** : Un avis est rédigé par un seul utilisateur
- **Règle métier** : Traçabilité de l'auteur

---

### Règles de gestion métier

Les **règles de gestion (RG)** formalisent les contraintes métier identifiées dans l'énoncé.

#### RG01 à RG05 : Utilisateurs
- **RG01** : Un utilisateur possède un rôle unique (UTILISATEUR, EMPLOYE ou ADMINISTRATEUR)
- **RG02** : Un compte peut être désactivé mais jamais supprimé (conservation historique RGPD)
- **RG03** : À la création, un compte reçoit automatiquement le rôle UTILISATEUR
- **RG04** : Seul un administrateur peut créer un compte EMPLOYE
- **RG05** : L'email sert d'identifiant unique de connexion

#### RG06 à RG13 : Menus
- **RG06** : Un menu appartient obligatoirement à un THEME
- **RG07** : Un menu appartient obligatoirement à un REGIME
- **RG08** : Un menu propose au minimum 1 plat
- **RG09** : Un plat peut être réutilisé dans plusieurs menus
- **RG10** : Un menu possède une galerie d'au moins 1 image
- **RG11** : Le nombre minimum de personnes doit être > 0
- **RG12** : Le prix du menu correspond au nombre minimum de personnes
- **RG13** : Un menu peut être désactivé temporairement sans suppression

#### RG14 à RG16 : Plats & Allergènes
- **RG14** : Un plat est typé : ENTREE, PLAT ou DESSERT
- **RG15** : Un plat peut contenir 0 à N allergènes
- **RG16** : L'affichage des allergènes est obligatoire (réglementation européenne)

#### RG17 à RG26 : Commandes
- **RG17** : Une commande est passée par un seul utilisateur authentifié
- **RG18** : Une commande porte sur un seul menu
- **RG19** : Le nombre de personnes commandé ≥ nombre_personne_min du menu
- **RG20** : Réduction de 10% si nombre_personnes ≥ (nombre_personne_min + 5)
- **RG21** : Frais de livraison = 5€ fixes à Bordeaux, 5€ + 0.59€/km ailleurs
- **RG22** : Prix total = (prix_unitaire × nb_personnes) - réduction + frais_livraison
- **RG23** : Cycle de vie : EN_ATTENTE → ACCEPTE → EN_PREPARATION → EN_LIVRAISON → LIVRE → (EN_ATTENTE_RETOUR si matériel) → TERMINEE
- **RG24** : Le client peut annuler si statut = EN_ATTENTE
- **RG25** : L'employé peut annuler après contact client (GSM ou MAIL obligatoire)
- **RG26** : Le client peut modifier (sauf le menu) si statut = EN_ATTENTE

#### RG27 à RG30 : Matériel
- **RG27** : Une commande peut emprunter 0 à N matériels
- **RG28** : Un matériel peut être prêté dans plusieurs commandes (si stock disponible)
- **RG29** : Si matériel prêté, la commande passe par le statut EN_ATTENTE_RETOUR
- **RG30** : Frais de 600€ si non restitué sous 10 jours ouvrés

#### RG31 à RG35 : Avis
- **RG31** : Un avis est rédigé uniquement pour une commande TERMINEE
- **RG32** : La note est obligatoirement entre 1 et 5
- **RG33** : Un commentaire est obligatoire
- **RG34** : Un avis doit être validé par un EMPLOYE ou ADMIN avant affichage
- **RG35** : Seuls les avis VALIDES sont visibles sur la page d'accueil

---

## Modèle Logique de Données (MLD)

### Vue d'ensemble du MLD

Le MLD est organisé en **6 domaines fonctionnels** :

### 1. **UTILISATEURS & AUTHENTIFICATION**
Gestion des comptes utilisateurs (clients, employés, administrateurs) et de la sécurité.

### 2. **RÉFÉRENTIELS & MENU**
Catalogues des menus proposés avec leurs caractéristiques (thèmes, régimes, images).

### 3. **PLATS & ALLERGÈNES**
Composition détaillée des menus avec gestion des allergènes pour la sécurité alimentaire.

### 4. **HORAIRES & CONTACT**
Informations pratiques (horaires d'ouverture) et formulaire de contact.

### 5. **COMMANDES & MATÉRIEL**
Gestion complète du cycle de commande incluant le prêt de matériel.

### 6. **TRAÇABILITÉ**
Historisation de toutes les modifications et changements de statut.

---

## Détail des tables et choix de conception

### 🔐 DOMAINE 1 : UTILISATEURS & AUTHENTIFICATION

#### **Table UTILISATEUR**

```sql
UTILISATEUR {
    INT id_utilisateur PK
    VARCHAR(100) nom NOT NULL
    VARCHAR(100) prenom NOT NULL
    VARCHAR(20) gsm NOT NULL
    VARCHAR(255) email UNIQUE NOT NULL
    VARCHAR(255) adresse_postale NOT NULL
    VARCHAR(255) mot_de_passe NOT NULL
    ENUM('UTILISATEUR','EMPLOYE','ADMINISTRATEUR') role DEFAULT 'UTILISATEUR' NOT NULL
    BOOLEAN actif DEFAULT TRUE NOT NULL
    DATETIME date_creation DEFAULT CURRENT_TIMESTAMP NOT NULL
}
```

**💡 Justifications :**

- **`id_utilisateur` (PK)** : Clé primaire auto-incrémentée pour identifier uniquement chaque utilisateur
- **`email UNIQUE`** : L'email sert d'identifiant de connexion, donc doit être unique dans la base
- **`role ENUM`** : Type énuméré pour garantir que seules les valeurs valides sont acceptées (UTILISATEUR, EMPLOYE, ADMINISTRATEUR)
  - *Énoncé* : "il lui sera confié le role de 'utilisateur'" et "créer un compte de type 'employe'"
- **`actif BOOLEAN`** : Permet de désactiver un compte sans le supprimer (soft delete)
  - *Énoncé* : "rendre inutilisable un compte employé en cas de départ"
- **`DEFAULT CURRENT_TIMESTAMP`** : Enregistre automatiquement la date de création du compte
- **`NOT NULL`** : Tous les champs sont obligatoires car nécessaires pour l'inscription

**📋 Réponse à l'énoncé :**
> "Un visiteur peut se créer un compte, pour cela, il devra communiquer des informations : Nom ainsi que le prénom, Numéro de GSM, Adresse mail et postale, Mot de passe sécurisé"

---

#### **Table RESET_TOKEN**

```sql
RESET_TOKEN {
    INT id_token PK
    VARCHAR(255) token UNIQUE NOT NULL
    INT id_utilisateur FK NOT NULL
    DATETIME expiration NOT NULL
    BOOLEAN utilise DEFAULT FALSE NOT NULL
}
```

**💡 Justifications :**

- **Table séparée** : Sépare la gestion des tokens de réinitialisation pour des raisons de sécurité
- **`token UNIQUE`** : Chaque token doit être unique pour éviter les collisions
- **`expiration`** : Limite la durée de validité du token (généralement 1h ou 24h)
- **`utilise`** : Empêche la réutilisation d'un même token
- **Relation 1:N avec UTILISATEUR** : Un utilisateur peut avoir plusieurs tokens (demandes successives)

**📋 Réponse à l'énoncé :**
> "Si le mot de passe est oublié, il pourra le réinitialiser via un bouton prévu à cet effet : un lien par mail lui sera envoyé"

---

### 🍽️ DOMAINE 2 : RÉFÉRENTIELS & MENU

#### **Tables THEME et REGIME**

```sql
THEME {
    INT id_theme PK
    VARCHAR(100) libelle UNIQUE NOT NULL
}

REGIME {
    INT id_regime PK
    VARCHAR(100) libelle UNIQUE NOT NULL
}
```

**💡 Justifications :**

- **Tables de référence** : Séparation en tables distinctes pour faciliter l'ajout de nouveaux thèmes/régimes
- **`UNIQUE`** : Évite les doublons (ex: deux thèmes "Noël")
- **Normalisation** : Évite de répéter "Noël", "Végétarien" dans chaque menu

**📋 Réponse à l'énoncé :**
> "Thème (Noel, Pâques, classique, évènement)"
> "Un Regime (vegetarien, vegan, classique) : vous pouvez alimenter d'avantage cette catégorie"

---

#### **Table MENU**

```sql
MENU {
    INT id_menu PK
    VARCHAR(120) titre NOT NULL
    TEXT description NOT NULL
    INT nombre_personne_min NOT NULL CHECK(nombre_personne_min > 0)
    DECIMAL(10,2) prix NOT NULL CHECK(prix > 0)
    INT stock_disponible DEFAULT 0 NOT NULL CHECK(stock_disponible >= 0)
    TEXT conditions
    INT id_theme FK NOT NULL
    INT id_regime FK NOT NULL
    BOOLEAN actif DEFAULT TRUE NOT NULL
    DATETIME date_publication DEFAULT CURRENT_TIMESTAMP NOT NULL
}
```

**💡 Justifications :**

- **`titre` (120 caractères)** : Suffisant pour des noms de menus descriptifs
- **`description TEXT`** : Type TEXT pour descriptions longues sans limite stricte
- **`nombre_personne_min`** : Nombre minimum de personnes pour commander ce menu
- **`prix DECIMAL(10,2)`** : Prix pour le nombre minimum de personnes
  - Format monétaire précis (ex: 125.50 €)
  - `CHECK(prix > 0)` : Un menu ne peut pas être gratuit ou négatif
- **`stock_disponible`** : Limite le nombre de commandes possibles
  - *Énoncé* : "Stock disponible (par exemple, il reste 5 commande possible de ce menu)"
  - `DEFAULT 0` : Par défaut, aucun stock si non renseigné
- **`conditions TEXT`** : Conditions spécifiques au menu (délai de commande, précautions)
  - *Énoncé* : "Les conditions de ce menu (par exemple, nécessité de commander ce menu x jours / semaines avant la prestation ou encore des précautions de stockage)"
- **`actif`** : Permet de masquer temporairement un menu sans le supprimer
- **`date_publication`** : Pour trier les menus (nouveautés en premier)

**Relations :**
- **`id_theme` FK → THEME** : Chaque menu appartient à un thème
- **`id_regime` FK → REGIME** : Chaque menu appartient à un régime

---

#### **Table IMAGE_MENU**

```sql
IMAGE_MENU {
    INT id_image PK
    INT id_menu FK NOT NULL
    VARCHAR(255) url NOT NULL
    VARCHAR(255) alt_text
    INT position NOT NULL
}
```

**💡 Justifications :**

- **Relation 1:N** : Un menu peut avoir plusieurs images (galerie)
  - *Énoncé* : "Une galerie d'image"
- **`url`** : Chemin vers l'image stockée (ex: `/uploads/menus/noel-1.jpg`)
  - **Stockage sur système de fichiers**, pas en BLOB dans la base
- **`alt_text`** : Texte alternatif pour l'accessibilité (RGAA)
- **`position`** : Ordre d'affichage des images dans la galerie

**🖼️ Pourquoi stocker l'URL et non l'image en BLOB ?**

| Critère | URL vers fichier (✅ choisi) | BLOB en base de données (❌) |
|---------|------------------------------|------------------------------|
| **Performance** | ✅ Rapide : serveur web optimisé pour fichiers | ❌ Lent : requête SQL pour chaque image |
| **Cache navigateur** | ✅ Cache HTTP natif (304 Not Modified) | ❌ Pas de cache possible |
| **CDN** | ✅ Compatible Cloudflare, CloudFront | ❌ Impossible d'utiliser un CDN |
| **Taille BDD** | ✅ Base légère (quelques Mo) | ❌ Base volumineuse (plusieurs Go) |
| **Backup** | ✅ Backup BDD rapide + backup fichiers séparé | ❌ Backup très lent et lourd |
| **Formats optimisés** | ✅ WebP, AVIF, redimensionnement facile | ❌ Traitement complexe |
| **Bande passante** | ✅ Serveur web/CDN gère la compression | ❌ Passe par PHP/Node.js (lent) |
| **Scalabilité** | ✅ Stockage S3, Azure Blob, Cloudinary | ❌ Limite de la base de données |

**Architecture professionnelle choisie :**

```
Upload image
     ↓
Backend API (Node.js/PHP)
     ↓
Stockage : /uploads/menus/noel-2024-thumb.webp
     ↓
BDD MySQL : INSERT INTO IMAGE_MENU (url, alt_text, position)
     ↓
Frontend : <img src="/uploads/menus/noel-2024-thumb.webp" alt="Menu de Noël">
```

**Exemple de stockage :**
- **Chemin physique** : `/var/www/vite-gourmand/public/uploads/menus/noel-2024.webp`
- **URL publique** : `https://vite-gourmand.fr/uploads/menus/noel-2024.webp`
- **En base** : `url = "/uploads/menus/noel-2024.webp"`

**Si BLOB était utilisé (non recommandé) :**
```sql
CREATE TABLE IMAGE_MENU (
    id_image INT AUTO_INCREMENT PRIMARY KEY,
    id_menu INT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    data_image LONGBLOB NOT NULL,        -- Image binaire (lourd !)
    type_mime VARCHAR(50) NOT NULL,      -- image/jpeg, image/png
    taille INT NOT NULL,                 -- Taille en octets
    alt_text VARCHAR(255),
    position INT NOT NULL
);
```

**Problèmes du stockage BLOB :**
1. ❌ **Performance catastrophique** : Chaque affichage nécessite une requête SQL lourde
2. ❌ **Pas de cache** : Le navigateur ne peut pas mettre en cache (rechargement à chaque visite)
3. ❌ **Backup lent** : Un backup de 100 menus avec 5 images chacun = plusieurs Go
4. ❌ **Limitations** : MySQL limite LONGBLOB à 4 Go par enregistrement
5. ❌ **Pas de CDN** : Impossible d'utiliser Cloudflare ou CloudFront pour accélérer le chargement

---

### 🥗 DOMAINE 3 : PLATS & ALLERGÈNES

#### **Table PLAT**

```sql
PLAT {
    INT id_plat PK
    VARCHAR(150) libelle UNIQUE NOT NULL
    ENUM('ENTREE','PLAT','DESSERT') type NOT NULL
    TEXT description
}
```

**💡 Justifications :**

- **`libelle UNIQUE`** : Évite les doublons de plats
- **`type ENUM`** : Catégorisation stricte (entrée, plat, dessert)
  - *Énoncé* : "Une liste de plat possible (entrée, plat ainsi que dessert)"
- **`description`** : Détails du plat (ingrédients, préparation)

---

#### **Table PROPOSE (Association Menu-Plat)**

```sql
PROPOSE {
    INT id_menu FK NOT NULL
    INT id_plat FK NOT NULL
    INT position NOT NULL
    PK(id_menu, id_plat)
}
```

**💡 Justifications :**

- **Table d'association Many-to-Many** : 
  - Un menu contient plusieurs plats
  - Un plat peut être dans plusieurs menus
  - *Énoncé* : "Une entrée ou un plat / dessert peuvent être présent dans plusieurs menus"
- **Clé primaire composite** : `(id_menu, id_plat)` garantit qu'un plat n'apparaît qu'une fois par menu
- **`position`** : Ordre d'affichage (entrée en premier, dessert en dernier)

---

#### **Tables ALLERGENE et PLAT_ALLERGENE**

```sql
ALLERGENE {
    INT id_allergene PK
    VARCHAR(100) libelle UNIQUE NOT NULL
}

PLAT_ALLERGENE {
    INT id_plat FK NOT NULL
    INT id_allergene FK NOT NULL
    PK(id_plat, id_allergene)
}
```

**💡 Justifications :**

- **Table de référence ALLERGENE** : Liste des 14 allergènes obligatoires (règlementation européenne)
- **Association Many-to-Many** : Un plat peut contenir plusieurs allergènes
  - *Énoncé* : "Chaque plat peut posséder une liste d'allergènes"
- **Importance légale** : Obligation d'informer les clients (sécurité alimentaire)

---

### 🕐 DOMAINE 4 : HORAIRES & CONTACT

#### **Table HORAIRE**

```sql
HORAIRE {
    INT id_horaire PK
    ENUM('LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI','SAMEDI','DIMANCHE') jour UNIQUE NOT NULL
    TIME heure_ouverture
    TIME heure_fermeture
    BOOLEAN ferme DEFAULT FALSE NOT NULL
}
```

**💡 Justifications :**

- **`jour UNIQUE`** : Chaque jour n'apparaît qu'une fois
- **`TIME`** : Format horaire précis (ex: 09:00:00)
- **`ferme`** : Permet de marquer un jour comme fermé (dimanche par exemple)
  - *Énoncé* : "Les horaires doivent être visible sur le pied de page, du lundi au dimanche"

---

#### **Table CONTACT**

```sql
CONTACT {
    INT id_contact PK
    VARCHAR(150) titre NOT NULL
    TEXT description NOT NULL
    VARCHAR(255) email NOT NULL
    DATETIME date_envoi DEFAULT CURRENT_TIMESTAMP NOT NULL
    BOOLEAN traite DEFAULT FALSE NOT NULL
}
```

**💡 Justifications :**

- **Stockage des messages** : Conservation de l'historique des demandes
- **`traite`** : Permet aux employés de marquer les messages comme traités
- **`date_envoi`** : Horodatage automatique pour le suivi

**📋 Réponse à l'énoncé :**
> "Un visiteur peut contacter l'entreprise s'il le souhaite, pour cela, il devra accéder à la page contact [...] un formulaire qui va lui demander un titre, une description ainsi que son mail"

---

### 📦 DOMAINE 5 : COMMANDES & MATÉRIEL

#### **Table COMMANDE**

```sql
COMMANDE {
    INT id_commande PK
    INT id_utilisateur FK NOT NULL
    INT id_menu FK NOT NULL
    DATETIME date_commande DEFAULT CURRENT_TIMESTAMP NOT NULL
    
    -- Informations de livraison
    DATE date_prestation NOT NULL
    TIME heure_livraison NOT NULL
    VARCHAR(255) adresse_livraison NOT NULL
    VARCHAR(100) ville NOT NULL
    VARCHAR(10) code_postal NOT NULL
    VARCHAR(20) gsm NOT NULL
    
    -- Tarification (snapshots)
    INT nombre_personnes NOT NULL CHECK(nombre_personnes >= nombre_personne_min_snapshot)
    INT nombre_personne_min_snapshot NOT NULL
    DECIMAL(10,2) prix_menu_unitaire NOT NULL CHECK(prix_menu_unitaire > 0)
    DECIMAL(10,2) montant_reduction DEFAULT 0 CHECK(montant_reduction >= 0)
    BOOLEAN reduction_appliquee DEFAULT FALSE NOT NULL
    DECIMAL(10,2) frais_livraison DEFAULT 0 NOT NULL CHECK(frais_livraison >= 0)
    DECIMAL(10,2) prix_total NOT NULL CHECK(prix_total > 0)
    
    -- Livraison hors Bordeaux
    BOOLEAN hors_bordeaux DEFAULT FALSE NOT NULL
    DECIMAL(6,2) distance_km DEFAULT 0 CHECK(distance_km >= 0)
    
    -- Statut et suivi
    ENUM statut DEFAULT 'EN_ATTENTE' NOT NULL
    BOOLEAN has_avis DEFAULT FALSE NOT NULL
    BOOLEAN materiel_pret DEFAULT FALSE NOT NULL
    DATETIME date_livraison_effective
    DATETIME date_retour_materiel
}
```

**💡 Justifications majeures :**

##### **Snapshots des prix**
- **`nombre_personne_min_snapshot`** : Copie du nombre min au moment de la commande
- **`prix_menu_unitaire`** : Copie du prix au moment de la commande
- **Pourquoi ?** : Si le prix du menu change après la commande, le client paie le prix d'origine
- **Principe** : Immutabilité des données de facturation

##### **Calcul de la réduction**
- **Règle métier** : Réduction de 10% si `nombre_personnes >= nombre_personne_min + 5`
  - *Énoncé* : "une réduction de 10% est appliquée pour toutes commandes ayant 5 personnes de plus que le nombre de personnes minimum"
- **`reduction_appliquee`** : Indicateur booléen pour savoir si la réduction a été appliquée
- **`montant_reduction`** : Montant exact de la réduction en euros

##### **Calcul des frais de livraison**
- **Règle métier** : 
  - Bordeaux : 5€ fixes
  - Hors Bordeaux : 5€ + (0.59€ × distance_km)
  - *Énoncé* : "facturation de 5 euros (majoré de 59 centimes par kilomètre parcouru) si la livraison n'est pas dans la ville de bordeaux"
- **`hors_bordeaux`** : Identifie rapidement les livraisons hors zone
- **`distance_km`** : Stocke la distance calculée pour transparence

##### **Statuts de commande**
```
EN_ATTENTE → ACCEPTE → EN_PREPARATION → EN_LIVRAISON → LIVRE → EN_ATTENTE_RETOUR → TERMINEE
                                                            ↓
                                                        ANNULEE
```

**📋 Réponse à l'énoncé :**
> "accepté: lorsque la commande reçue est validée par l'équipe"
> "en préparation: la commande est en cours de préparation"
> "en cours de livraison: la commande est en cours de livraison"
> "livré: l'équipe livraison a livré le client"
> "en attente du retour de matériel: si du matériel a été prêté"
> "terminée: soit quand la commande est livrée sans prêt de matériel"

---

#### **Tables MATERIEL et COMMANDE_MATERIEL**

```sql
MATERIEL {
    INT id_materiel PK
    VARCHAR(100) libelle NOT NULL
    TEXT description
    DECIMAL(10,2) valeur_unitaire NOT NULL CHECK(valeur_unitaire > 0)
    INT stock_disponible DEFAULT 0 NOT NULL CHECK(stock_disponible >= 0)
}

COMMANDE_MATERIEL {
    INT id_commande_materiel PK
    INT id_commande FK NOT NULL
    INT id_materiel FK NOT NULL
    INT quantite NOT NULL CHECK(quantite > 0)
    DATETIME date_pret NOT NULL
    DATETIME date_retour_prevu NOT NULL
    DATETIME date_retour_effectif
    BOOLEAN retourne DEFAULT FALSE NOT NULL
}
```

**💡 Justifications :**

- **Gestion du matériel prêté** : 
  - *Énoncé* : "Matériel de service disponible en prêt"
  - "si du matériel a été prêté au client. Il doit le restituer"
- **`valeur_unitaire`** : Pour facturer 600€ si non restitué après 10 jours
- **`date_retour_prevu` vs `date_retour_effectif`** : Suivi des retards
- **`retourne`** : Permet de savoir rapidement si le matériel est de retour
- **Relation 1:N** : Une commande peut avoir plusieurs types de matériel

---

### 📊 DOMAINE 6 : TRAÇABILITÉ

#### **Table COMMANDE_STATUT**

```sql
COMMANDE_STATUT {
    INT id_statut PK
    INT id_commande FK NOT NULL
    ENUM statut NOT NULL
    DATETIME date_changement DEFAULT CURRENT_TIMESTAMP NOT NULL
    INT modifie_par FK NOT NULL
    VARCHAR(255) commentaire
}
```

**💡 Justifications :**

- **Historique complet** : Conserve TOUS les changements de statut
- **Traçabilité** : Qui a modifié ? Quand ? Pourquoi ?
- **`modifie_par`** : Identifie l'employé/admin responsable
- **Usage** : Affichage du suivi de commande pour le client
  - *Énoncé* : "Le suivi de la commande énumère tous les états de sa commande suivi de la date et l'heure de modification"

---

#### **Table COMMANDE_ANNULATION**

```sql
COMMANDE_ANNULATION {
    INT id_annulation PK
    INT id_commande FK NOT NULL
    INT annule_par FK NOT NULL
    ENUM('GSM','MAIL') mode_contact NOT NULL
    TEXT motif NOT NULL
    DATETIME date_annulation DEFAULT CURRENT_TIMESTAMP NOT NULL
}
```

**💡 Justifications :**

- **Obligation de contact avant annulation** :
  - *Énoncé* : "il ne peut pas modifier / annuler les commandes avant d'avoir contacté le client par appel GSM ou mail. (Il devra mettre un motif d'annulation en spécifiant le mode de contact ainsi que le motif)"
- **`mode_contact`** : GSM ou MAIL (preuve du contact)
- **`motif`** : Explication obligatoire de l'annulation
- **`annule_par`** : Responsable de l'annulation (client ou employé)

---

#### **Table COMMANDE_MODIFICATION**

```sql
COMMANDE_MODIFICATION {
    INT id_modif PK
    INT id_commande FK NOT NULL
    INT modifie_par FK NOT NULL
    DATETIME date_modif DEFAULT CURRENT_TIMESTAMP NOT NULL
    JSON champs_modified NOT NULL
}
```

**💡 Justifications :**

- **Historique des modifications** : Qui a modifié quoi et quand
- **`champs_modified` (JSON)** : Stocke les changements sous forme :
  ```json
  {
    "nombre_personnes": {"old": 6, "new": 8},
    "date_prestation": {"old": "2024-12-20", "new": "2024-12-22"}
  }
  ```
- **Audit trail** : Conformité RGPD (traçabilité des modifications de données)

---

#### **Table AVIS_FALLBACK**

```sql
AVIS_FALLBACK {
    INT id_avis_fallback PK
    TINYINT note NOT NULL CHECK(note BETWEEN 1 AND 5)
    TEXT commentaire NOT NULL
    ENUM('VALIDE','REFUSE','EN_ATTENTE') statut_validation DEFAULT 'EN_ATTENTE' NOT NULL
    DATETIME date_avis DEFAULT CURRENT_TIMESTAMP NOT NULL
    INT id_utilisateur NOT NULL
    INT id_commande NOT NULL
    INT id_menu NOT NULL
    INT modere_par
    DATETIME date_validation
    VARCHAR(24) mongo_id
}
```

**💡 Justifications :**

- **Fallback MySQL** : Backup en cas de panne MongoDB
  - *Énoncé* : "Les données doivent venir d'une base de données non relationnelle"
  - Mais nécessité d'un fallback pour la fiabilité
- **`note CHECK(1-5)`** : Notation sur 5 étoiles
  - *Énoncé* : "Il doit pouvoir donner entre note entre 1 et 5"
- **Modération obligatoire** :
  - *Énoncé* : "L'employé peut également, valider les avis reçus par les utilisateurs afin qu'ils soient visibles sur la page d'accueil. Il peut également en refuser."
- **`statut_validation`** : EN_ATTENTE → VALIDE ou REFUSE
- **`modere_par`** : Identifie l'employé qui a validé/refusé
- **`mongo_id`** : Synchronisation avec MongoDB (si disponible)

---

## Relations et cardinalités

### Notation des cardinalités

- **||--o{** : Un à plusieurs (1:N)
  - Exemple : `UTILISATEUR ||--o{ COMMANDE` = Un utilisateur peut passer plusieurs commandes
- **}o--o{** : Plusieurs à plusieurs (N:M)
  - Exemple : `MENU }o--o{ PLAT` = Un menu contient plusieurs plats, un plat peut être dans plusieurs menus

### Relations principales

```
UTILISATEUR ||--o{ RESET_TOKEN : "possède"
UTILISATEUR ||--o{ COMMANDE : "passe"
UTILISATEUR ||--o{ COMMANDE_STATUT : "modifie"

THEME ||--o{ MENU : "catégorise"
REGIME ||--o{ MENU : "catégorise"
MENU ||--o{ IMAGE_MENU : "galerie"
MENU }o--o{ PLAT : "propose"
MENU ||--o{ COMMANDE : "commandé"

PLAT }o--o{ ALLERGENE : "contient"

COMMANDE ||--o{ COMMANDE_STATUT : "historise"
COMMANDE ||--o{ COMMANDE_ANNULATION : "annulation"
COMMANDE ||--o{ COMMANDE_MODIFICATION : "modification"
COMMANDE ||--o{ COMMANDE_MATERIEL : "matériel_prêté"

MATERIEL ||--o{ COMMANDE_MATERIEL : "prêté"
```

---

## Contraintes et règles métier

### Contraintes CHECK

```sql
-- Prix et montants positifs
CHECK(prix > 0)
CHECK(frais_livraison >= 0)
CHECK(prix_total > 0)

-- Note entre 1 et 5
CHECK(note BETWEEN 1 AND 5)

-- Nombre de personnes cohérent
CHECK(nombre_personnes >= nombre_personne_min_snapshot)

-- Stocks non négatifs
CHECK(stock_disponible >= 0)
```

### Contraintes NOT NULL

Tous les champs essentiels sont `NOT NULL` pour garantir l'intégrité des données :
- Informations utilisateur (nom, prénom, email)
- Détails de commande (adresse, date, prix)
- Clés étrangères (relations obligatoires)

### Valeurs par défaut (DEFAULT)

```sql
role DEFAULT 'UTILISATEUR'           -- Nouveau compte = utilisateur
actif DEFAULT TRUE                    -- Compte actif par défaut
date_creation DEFAULT CURRENT_TIMESTAMP  -- Horodatage automatique
statut DEFAULT 'EN_ATTENTE'          -- Commande en attente par défaut
reduction_appliquee DEFAULT FALSE     -- Pas de réduction par défaut
```

---

## Justifications techniques

### Pourquoi MySQL et pas uniquement MongoDB ?

**Choix d'une architecture hybride :**

1. **MySQL (relationnel)** :
   - ✅ Relations complexes entre entités (commandes, menus, utilisateurs)
   - ✅ Intégrité référentielle (clés étrangères)
   - ✅ Transactions ACID (commandes avec paiement)
   - ✅ Requêtes complexes (calculs CA, statistiques)

2. **MongoDB (NoSQL)** pour les avis :
   - ✅ Flexibilité du schéma (champs variables)
   - ✅ Performance en lecture (page d'accueil)
   - ✅ Scalabilité horizontale
   - ⚠️ **Fallback MySQL** : En cas de panne MongoDB, les avis continuent de fonctionner

### Normalisation

Le MLD respecte la **3ème forme normale (3NF)** :

1. **1NF** : Atomicité des données (pas de listes dans les champs)
2. **2NF** : Pas de dépendance partielle (toutes les colonnes dépendent de la clé primaire complète)
3. **3NF** : Pas de dépendance transitive (pas de colonnes dépendant d'autres colonnes non-clés)

**Exemple de normalisation appliquée :**
- ❌ Stocker le libellé du thème dans MENU → Redondance
- ✅ Stocker `id_theme` FK → Référence à la table THEME

### Indexes

Des index sont créés sur :
- Clés primaires (automatique)
- Clés étrangères (performances des JOIN)
- Colonnes de recherche fréquente (email, statut, date_commande)
- Colonnes de tri (date_publication, position)

### Triggers

**Automatisation de l'historique :**

```sql
-- Création automatique de l'historique lors d'une nouvelle commande
TRIGGER after_commande_insert
  → INSERT INTO COMMANDE_STATUT

-- Mise à jour automatique lors du changement de statut
TRIGGER after_commande_update_statut
  → INSERT INTO COMMANDE_STATUT (si statut modifié)
```

**Avantages :**
- ✅ Aucun oubli possible
- ✅ Historique complet garanti
- ✅ Moins de code applicatif

---

## Conclusion

Ce MLD répond à **tous les besoins fonctionnels** de l'énoncé :

✅ Gestion des utilisateurs (3 rôles)
✅ Catalogue de menus avec filtres
✅ Système de commande complet avec calculs automatiques
✅ Gestion du matériel prêté
✅ Historisation et traçabilité complète
✅ Modération des avis
✅ Contact et horaires
✅ Sécurité et contraintes métier

Le modèle est :
- **Normalisé** : Pas de redondance
- **Scalable** : Facile d'ajouter de nouvelles fonctionnalités
- **Sécurisé** : Contraintes et validations strictes
- **Traçable** : Historique complet de toutes les actions
- **Performant** : Index sur colonnes clés

---

## Passage du MCD au MLD

### Transformations appliquées

Le passage du **MCD (conceptuel)** au **MLD (logique)** nécessite plusieurs transformations :

#### 1. **Entités → Tables**
Chaque entité devient une table avec :
- Une clé primaire (PK) auto-incrémentée
- Des attributs typés précisément (VARCHAR, INT, DECIMAL, ENUM, etc.)
- Des contraintes de domaine (NOT NULL, CHECK, DEFAULT)

**Exemple :**
```
MCD : UTILISATEUR { nom, prenom, email, role }
      ↓
MLD : UTILISATEUR (
        id_utilisateur INT PK,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        role ENUM('UTILISATEUR','EMPLOYE','ADMINISTRATEUR') DEFAULT 'UTILISATEUR' NOT NULL
      )
```

---

#### 2. **Associations 1:N → Clé étrangère**
Une relation "un à plusieurs" devient une clé étrangère dans la table "plusieurs".

**Exemple :**
```
MCD : UTILISATEUR (1,1) ----< (0,N) COMMANDE
      ↓
MLD : COMMANDE (
        id_commande INT PK,
        id_utilisateur INT FK REFERENCES UTILISATEUR(id_utilisateur),
        ...
      )
```

---

#### 3. **Associations N:M → Table intermédiaire**
Une relation "plusieurs à plusieurs" devient une table d'association avec deux clés étrangères.

**Exemple :**
```
MCD : MENU (1,N) >----< (1,M) PLAT
      ↓
MLD : PROPOSE (
        id_menu INT FK REFERENCES MENU(id_menu),
        id_plat INT FK REFERENCES PLAT(id_plat),
        position INT NOT NULL,
        PRIMARY KEY (id_menu, id_plat)
      )
```

---

#### 4. **Règles métier → Contraintes SQL**

| Règle métier | Contrainte SQL |
|--------------|----------------|
| "Le prix doit être positif" | `CHECK(prix > 0)` |
| "L'email doit être unique" | `UNIQUE(email)` |
| "Le nom est obligatoire" | `NOT NULL` |
| "Le rôle par défaut est UTILISATEUR" | `DEFAULT 'UTILISATEUR'` |
| "La note est entre 1 et 5" | `CHECK(note BETWEEN 1 AND 5)` |

---

#### 5. **Ajouts techniques (non présents dans le MCD)**

Le MLD ajoute des **tables techniques** absentes du MCD conceptuel :

##### **RESET_TOKEN**
- **Pourquoi ?** : Gestion technique de la réinitialisation de mot de passe
- **MCD** : Non présent (détail d'implémentation)
- **MLD** : Table nécessaire pour stocker les tokens temporaires

##### **COMMANDE_STATUT**
- **Pourquoi ?** : Historisation de tous les changements de statut
- **MCD** : Implicite dans "suivi de commande"
- **MLD** : Table explicite pour traçabilité

##### **COMMANDE_ANNULATION**
- **Pourquoi ?** : Traçabilité des annulations avec motif obligatoire
- **MCD** : Non présent (règle de gestion documentée ailleurs)
- **MLD** : Table nécessaire pour audit trail

##### **COMMANDE_MODIFICATION**
- **Pourquoi ?** : Historique des modifications (RGPD)
- **MCD** : Non présent
- **MLD** : Table avec champ JSON pour stocker les changements

##### **COMMANDE_MATERIEL**
- **Pourquoi ?** : Détails du prêt (quantités, dates de retour)
- **MCD** : Juste l'association COMMANDE >----< MATERIEL
- **MLD** : Table avec attributs de gestion (date_pret, date_retour_prevu, etc.)

##### **AVIS_FALLBACK**
- **Pourquoi ?** : Backup MySQL si MongoDB est en panne
- **MCD** : L'entité AVIS suffit
- **MLD** : Table de fallback pour résilience (architecture hybride)

---

#### 6. **Optimisations techniques**

Le MLD ajoute des éléments d'optimisation absents du MCD :

**Index** :
```sql
INDEX idx_email ON UTILISATEUR(email);
INDEX idx_statut ON COMMANDE(statut);
INDEX idx_date_commande ON COMMANDE(date_commande);
```

**Triggers** :
```sql
TRIGGER after_commande_insert
  → Crée automatiquement un enregistrement dans COMMANDE_STATUT
```

**Vues** :
```sql
VIEW v_menus_actifs
  → Pré-calcul des menus actifs avec images et nombre de plats
```

---

### Tableau récapitulatif MCD → MLD

| Concept MCD | Devient en MLD | Exemple |
|-------------|----------------|---------|
| Entité | Table avec PK | UTILISATEUR → table UTILISATEUR |
| Attribut | Colonne typée | nom → VARCHAR(100) NOT NULL |
| Identifiant | Clé primaire (PK) | id_utilisateur INT AUTO_INCREMENT PK |
| Association 1:N | Clé étrangère (FK) | UTILISATEUR → COMMANDE : FK id_utilisateur |
| Association N:M | Table d'association | MENU >< PLAT → table PROPOSE |
| Cardinalité (1,1) | NOT NULL sur FK | id_utilisateur NOT NULL |
| Cardinalité (0,1) | FK nullable | modere_par INT (peut être NULL) |
| Règle métier | Contrainte CHECK | "Note 1-5" → CHECK(note BETWEEN 1 AND 5) |
| Valeur par défaut | DEFAULT | role DEFAULT 'UTILISATEUR' |

---

## Fichiers associés

### Diagrammes
- `diagramme_mcd.md` : Modèle Conceptuel de Données (Mermaid)
- `diagramme_mld.md` : Modèle Logique de Données complet (Mermaid)
- `diagramme_mld_correct.md` : MLD simplifié pour visualisation

### Scripts SQL
- `../backend/database/sql/database_creation.sql` : Script de création des tables MySQL
- `../backend/database/sql/database_fixtures.sql` : Données de test pour démonstration
- `../backend/database/mongoDB/database_mongodb_setup.js` : Configuration MongoDB (avis, statistiques)

### Documentation
- `explications_des_diagrammes.md` : Ce fichier (documentation complète)
- `README_DIAGRAMMES.md` : Guide de visualisation des diagrammes
