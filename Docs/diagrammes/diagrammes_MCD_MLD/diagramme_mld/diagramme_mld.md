```mermaid
%%{init: { 'theme': 'neutral' } }%%
erDiagram

    %% ==== UTILISATEURS & AUTH =====
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
    RESET_TOKEN {
        INT id_token PK
        VARCHAR(255) token UNIQUE NOT NULL
        INT id_utilisateur FK NOT NULL
        DATETIME expiration NOT NULL
        BOOLEAN utilise DEFAULT FALSE NOT NULL
    }

    %% ==== REFERENTIELS & MENU =====
    THEME {
        INT id_theme PK
        VARCHAR(100) libelle UNIQUE NOT NULL
    }
    REGIME {
        INT id_regime PK
        VARCHAR(100) libelle UNIQUE NOT NULL
    }
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
    IMAGE_MENU {
        INT id_image PK
        INT id_menu FK NOT NULL
        VARCHAR(255) url NOT NULL
        VARCHAR(255) alt_text
        INT position NOT NULL
    }

    %% ==== PLATS & ALLERGENES =====
    PLAT {
        INT id_plat PK
        VARCHAR(150) libelle UNIQUE NOT NULL
        ENUM('ENTREE','PLAT','DESSERT') type NOT NULL
        TEXT description
    }
    PROPOSE {
        INT id_menu FK NOT NULL
        INT id_plat FK NOT NULL
        INT position NOT NULL
        PK(id_menu, id_plat)
    }
    ALLERGENE {
        INT id_allergene PK
        VARCHAR(100) libelle UNIQUE NOT NULL
    }
    PLAT_ALLERGENE {
        INT id_plat FK NOT NULL
        INT id_allergene FK NOT NULL
        PK(id_plat, id_allergene)
    }

    %% ==== HORAIRES & CONTACT =====
    HORAIRE {
        INT id_horaire PK
        ENUM('LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI','SAMEDI','DIMANCHE') jour UNIQUE NOT NULL
        TIME heure_ouverture
        TIME heure_fermeture
        BOOLEAN ferme DEFAULT FALSE NOT NULL
    }
    CONTACT {
        INT id_contact PK
        VARCHAR(150) titre NOT NULL
        TEXT description NOT NULL
        VARCHAR(255) email NOT NULL
        DATETIME date_envoi DEFAULT CURRENT_TIMESTAMP NOT NULL
        BOOLEAN traite DEFAULT FALSE NOT NULL
    }

    %% ==== COMMANDES (tarif, livraison, snapshots) =====
    COMMANDE {
        INT id_commande PK
        INT id_utilisateur FK NOT NULL
        INT id_menu FK NOT NULL
        DATETIME date_commande DEFAULT CURRENT_TIMESTAMP NOT NULL

        DATE date_prestation NOT NULL
        TIME heure_livraison NOT NULL
        VARCHAR(255) adresse_livraison NOT NULL
        VARCHAR(100) ville NOT NULL
        VARCHAR(10) code_postal NOT NULL
        VARCHAR(20) gsm NOT NULL

        INT nombre_personnes NOT NULL CHECK(nombre_personnes >= nombre_personne_min_snapshot)
        INT nombre_personne_min_snapshot NOT NULL
        DECIMAL(10,2) prix_menu_unitaire NOT NULL CHECK(prix_menu_unitaire > 0)
        DECIMAL(10,2) montant_reduction DEFAULT 0 CHECK(montant_reduction >= 0)
        BOOLEAN reduction_appliquee DEFAULT FALSE NOT NULL
        DECIMAL(10,2) frais_livraison DEFAULT 0 NOT NULL CHECK(frais_livraison >= 0)
        DECIMAL(10,2) prix_total NOT NULL CHECK(prix_total > 0)

        BOOLEAN hors_bordeaux DEFAULT FALSE NOT NULL
        DECIMAL(6,2) distance_km DEFAULT 0 CHECK(distance_km >= 0)

        ENUM('EN_ATTENTE','ACCEPTE','EN_PREPARATION','EN_LIVRAISON','LIVRE','EN_ATTENTE_RETOUR','TERMINEE','ANNULEE') statut DEFAULT 'EN_ATTENTE' NOT NULL
        BOOLEAN has_avis DEFAULT FALSE NOT NULL
        BOOLEAN materiel_pret DEFAULT FALSE NOT NULL
        DATETIME date_livraison_effective
        DATETIME date_retour_materiel
    }

    %% ==== MATERIEL PRETE =====
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

    %% ==== TRAÇABILITE (statuts, annulation, modifications) =====
    COMMANDE_STATUT {
        INT id_statut PK
        INT id_commande FK NOT NULL
        ENUM('EN_ATTENTE','ACCEPTE','EN_PREPARATION','EN_LIVRAISON','LIVRE','EN_ATTENTE_RETOUR','TERMINEE','ANNULEE') statut NOT NULL
        DATETIME date_changement DEFAULT CURRENT_TIMESTAMP NOT NULL
        INT modifie_par FK NOT NULL
        VARCHAR(255) commentaire
    }
    COMMANDE_ANNULATION {
        INT id_annulation PK
        INT id_commande FK NOT NULL
        INT annule_par FK NOT NULL
        ENUM('GSM','MAIL') mode_contact NOT NULL
        TEXT motif NOT NULL
        DATETIME date_annulation DEFAULT CURRENT_TIMESTAMP NOT NULL
    }
    COMMANDE_MODIFICATION {
        INT id_modif PK
        INT id_commande FK NOT NULL
        INT modifie_par FK NOT NULL
        DATETIME date_modif DEFAULT CURRENT_TIMESTAMP NOT NULL
        JSON champs_modified NOT NULL
    }

    %% ==== AVIS FALLBACK (MySQL, pour panne Mongo) =====
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

    %% ==== RELATIONS (cardinalités) =====
    UTILISATEUR  ||--o{ RESET_TOKEN : "possède"
    THEME        ||--o{ MENU        : "catégorise"
    REGIME       ||--o{ MENU        : "catégorise"
    MENU         ||--o{ IMAGE_MENU  : "galerie"
    MENU         }o--o{ PLAT        : "propose" 
    PLAT         ||--o{ PLAT_ALLERGENE  : "carte"
    ALLERGENE    ||--o{ PLAT_ALLERGENE  : "référence"

    UTILISATEUR  ||--o{ COMMANDE    : "passe"
    MENU         ||--o{ COMMANDE    : "commandé"
    COMMANDE     ||--o{ COMMANDE_MATERIEL : "materiel_prete"
    MATERIEL     ||--o{ COMMANDE_MATERIEL : "prete"
    COMMANDE     ||--o{ COMMANDE_STATUT : "historise"
    UTILISATEUR  ||--o{ COMMANDE_STATUT : "modifie_par"
    COMMANDE     ||--o{ COMMANDE_ANNULATION : "annule"
    UTILISATEUR  ||--o{ COMMANDE_ANNULATION : "annule_par"
    COMMANDE     ||--o{ COMMANDE_MODIFICATION : "modifie"
    UTILISATEUR  ||--o{ COMMANDE_MODIFICATION : "modifie_par"

    %% (Liens conceptuels vers AVIS_FALLBACK)
    UTILISATEUR  ||--o{ AVIS_FALLBACK : "avis (fallback)"
    COMMANDE     ||--o{ AVIS_FALLBACK : "avis (fallback)"
    MENU         ||--o{ AVIS_FALLBACK : "avis (fallback)"
```