-- ============================================================
-- Script de création de la base de données "Vite & Gourmand"
-- Version: 1.0
-- Date: 11 décembre 2025
-- SGBD: MySQL 8.0+
-- ============================================================

-- Configuration de l'encodage UTF-8
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- S'assurer que la base utilise UTF-8 (si elle existe déjà)
ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Création de la base de données
-- DROP DATABASE IF EXISTS vite_et_gourmand;
-- CREATE DATABASE vite_et_gourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE vite_et_gourmand;
-- a decommenter en prod?
-- USE vite_et_gourmand_test;
-- ============================================================
-- UTILISATEURS & AUTHENTIFICATION
-- ============================================================

CREATE TABLE IF NOT EXISTS UTILISATEUR (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    gsm VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    adresse_postale VARCHAR(255) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    code_postal VARCHAR(20) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('UTILISATEUR', 'EMPLOYE', 'ADMINISTRATEUR') NOT NULL DEFAULT 'UTILISATEUR',
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_actif (actif)
) ENGINE=InnoDB COMMENT='Table des utilisateurs (clients, employés, administrateurs)';

CREATE TABLE IF NOT EXISTS RESET_TOKEN (
    id_token INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(255) NOT NULL UNIQUE,
    id_utilisateur INT NOT NULL,
    expiration DATETIME NOT NULL,
    utilise BOOLEAN NOT NULL DEFAULT FALSE,
    
    CONSTRAINT fk_reset_token_utilisateur 
        FOREIGN KEY (id_utilisateur) 
        REFERENCES UTILISATEUR(id_utilisateur) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    INDEX idx_token (token),
    INDEX idx_expiration (expiration),
    INDEX idx_utilise (utilise)
) ENGINE=InnoDB COMMENT='Tokens pour réinitialisation de mot de passe';

-- ============================================================
-- RÉFÉRENTIELS & MENU
-- ============================================================

CREATE TABLE IF NOT EXISTS THEME (
    id_theme INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE,
    
    INDEX idx_libelle (libelle)
) ENGINE=InnoDB COMMENT='Thèmes des menus (Noël, Pâques, classique, événement)';

CREATE TABLE IF NOT EXISTS REGIME (
    id_regime INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE,
    
    INDEX idx_libelle (libelle)
) ENGINE=InnoDB COMMENT='Régimes alimentaires (végétarien, vegan, classique)';

CREATE TABLE IF NOT EXISTS MENU (
    id_menu INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    nombre_personne_min INT NOT NULL CHECK(nombre_personne_min > 0),
    prix DECIMAL(10,2) NOT NULL CHECK(prix > 0),
    stock_disponible INT NOT NULL DEFAULT 0 CHECK(stock_disponible >= 0),
    conditions TEXT,
    id_theme INT NOT NULL,
    id_regime INT NOT NULL,
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    date_publication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_menu_theme 
        FOREIGN KEY (id_theme) 
        REFERENCES THEME(id_theme) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_menu_regime 
        FOREIGN KEY (id_regime) 
        REFERENCES REGIME(id_regime) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    
    INDEX idx_actif (actif),
    INDEX idx_prix (prix),
    INDEX idx_theme (id_theme),
    INDEX idx_regime (id_regime),
    INDEX idx_stock (stock_disponible),
    INDEX idx_date_publication (date_publication)
) ENGINE=InnoDB COMMENT='Menus proposés par l\'entreprise';

CREATE TABLE IF NOT EXISTS IMAGE_MENU (
    id_image INT AUTO_INCREMENT PRIMARY KEY,
    id_menu INT NOT NULL,
    url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    position INT NOT NULL,
    
    CONSTRAINT fk_image_menu 
        FOREIGN KEY (id_menu) 
        REFERENCES MENU(id_menu) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    INDEX idx_menu (id_menu),
    INDEX idx_position (position)
) ENGINE=InnoDB COMMENT='Galerie d\'images pour chaque menu';

CREATE TABLE IF NOT EXISTS MENU_MATERIEL (
    id_menu INT NOT NULL,
    id_materiel INT NOT NULL,
    quantite_par_personne INT NOT NULL DEFAULT 1 CHECK(quantite_par_personne > 0),
    PRIMARY KEY (id_menu, id_materiel),
    
    CONSTRAINT fk_menu_materiel_menu
        FOREIGN KEY (id_menu) 
        REFERENCES MENU(id_menu) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    -- Attention: La table MATERIEL doit être créée avant cette contrainte.
    -- Si ce script est exécuté séquentiellement, assurez-vous que MATERIEL est défini plus bas ou déplacez cette table.
    -- Pour éviter les erreurs de dépendance circulaire lors de l'import, on peut définir la contrainte à la fin.
   INDEX idx_materiel_def (id_materiel)
) ENGINE=InnoDB COMMENT='Matériel inclus par défaut dans un menu';

-- ============================================================
-- PLATS & ALLERGÈNES
-- ============================================================

CREATE TABLE IF NOT EXISTS PLAT (
    id_plat INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(150) NOT NULL UNIQUE,
    type ENUM('ENTREE', 'PLAT', 'DESSERT') NOT NULL,
    description TEXT,
    
    INDEX idx_type (type),
    INDEX idx_libelle (libelle)
) ENGINE=InnoDB COMMENT='Plats disponibles (entrée, plat, dessert)';

CREATE TABLE IF NOT EXISTS PROPOSE (
    id_menu INT NOT NULL,
    id_plat INT NOT NULL,
    position INT NOT NULL,
    
    PRIMARY KEY (id_menu, id_plat),
    
    CONSTRAINT fk_propose_menu 
        FOREIGN KEY (id_menu) 
        REFERENCES MENU(id_menu) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_propose_plat 
        FOREIGN KEY (id_plat) 
        REFERENCES PLAT(id_plat) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    INDEX idx_position (position)
) ENGINE=InnoDB COMMENT='Association many-to-many entre menus et plats';

CREATE TABLE IF NOT EXISTS ALLERGENE (
    id_allergene INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE,
    
    INDEX idx_libelle (libelle)
) ENGINE=InnoDB COMMENT='Liste des allergènes possibles';

CREATE TABLE IF NOT EXISTS PLAT_ALLERGENE (
    id_plat INT NOT NULL,
    id_allergene INT NOT NULL,
    
    PRIMARY KEY (id_plat, id_allergene),
    
    CONSTRAINT fk_plat_allergene_plat 
        FOREIGN KEY (id_plat) 
        REFERENCES PLAT(id_plat) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_plat_allergene_allergene 
        FOREIGN KEY (id_allergene) 
        REFERENCES ALLERGENE(id_allergene) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Association many-to-many entre plats et allergènes';

-- ============================================================
-- HORAIRES & CONTACT
-- ============================================================

CREATE TABLE IF NOT EXISTS HORAIRE (
    id_horaire INT AUTO_INCREMENT PRIMARY KEY,
    jour ENUM('LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE') NOT NULL UNIQUE,
    heure_ouverture TIME,
    heure_fermeture TIME,
    ferme BOOLEAN NOT NULL DEFAULT FALSE,
    
    INDEX idx_jour (jour)
) ENGINE=InnoDB COMMENT='Horaires d\'ouverture de l\'entreprise';

CREATE TABLE IF NOT EXISTS CONTACT (
    id_contact INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    email VARCHAR(255) NOT NULL,
    date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    traite BOOLEAN NOT NULL DEFAULT FALSE,
    
    INDEX idx_traite (traite),
    INDEX idx_date_envoi (date_envoi)
) ENGINE=InnoDB COMMENT='Messages de contact des visiteurs';

-- ============================================================
-- MATÉRIEL PRÊTÉ
-- ============================================================

CREATE TABLE IF NOT EXISTS MATERIEL (
    id_materiel INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL,
    description TEXT,
    valeur_unitaire DECIMAL(10,2) NOT NULL CHECK(valeur_unitaire > 0),
    stock_disponible INT NOT NULL DEFAULT 0 CHECK(stock_disponible >= 0),
    
    INDEX idx_libelle (libelle),
    INDEX idx_stock (stock_disponible)
) ENGINE=InnoDB COMMENT='Matériel pouvant être prêté aux clients';

-- Ajout de la contrainte FK pour MENU_MATERIEL maintenant que MATERIEL existe
ALTER TABLE MENU_MATERIEL
ADD CONSTRAINT fk_menu_materiel_materiel
FOREIGN KEY (id_materiel) REFERENCES MATERIEL(id_materiel)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- ============================================================
-- COMMANDES
-- ============================================================

CREATE TABLE IF NOT EXISTS COMMANDE (
    id_commande INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_menu INT NOT NULL,
    date_commande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Informations de livraison
    date_prestation DATE NOT NULL,
    heure_livraison TIME NOT NULL,
    adresse_livraison VARCHAR(255) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    code_postal VARCHAR(10) NOT NULL,
    gsm VARCHAR(20) NOT NULL,
    
    -- Tarification (snapshots au moment de la commande)
    nombre_personnes INT NOT NULL,
    nombre_personne_min_snapshot INT NOT NULL,
    prix_menu_unitaire DECIMAL(10,2) NOT NULL CHECK(prix_menu_unitaire > 0),
    montant_reduction DECIMAL(10,2) DEFAULT 0 CHECK(montant_reduction >= 0),
    reduction_appliquee BOOLEAN NOT NULL DEFAULT FALSE,
    frais_livraison DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK(frais_livraison >= 0),
    prix_total DECIMAL(10,2) NOT NULL CHECK(prix_total > 0),
    
    -- Livraison hors Bordeaux
    hors_bordeaux BOOLEAN NOT NULL DEFAULT FALSE,
    distance_km DECIMAL(6,2) DEFAULT 0 CHECK(distance_km >= 0),
    
    -- Statut et suivi
    statut ENUM('EN_ATTENTE', 'ACCEPTE', 'EN_PREPARATION', 'EN_LIVRAISON', 'LIVRE', 'EN_ATTENTE_RETOUR', 'TERMINEE', 'ANNULEE') NOT NULL DEFAULT 'EN_ATTENTE',
    has_avis BOOLEAN NOT NULL DEFAULT FALSE,
    materiel_pret BOOLEAN NOT NULL DEFAULT FALSE,
    date_livraison_effective DATETIME,
    date_retour_materiel DATETIME,
    
    CONSTRAINT fk_commande_utilisateur 
        FOREIGN KEY (id_utilisateur) 
        REFERENCES UTILISATEUR(id_utilisateur) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_commande_menu 
        FOREIGN KEY (id_menu) 
        REFERENCES MENU(id_menu) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    
    CONSTRAINT chk_nombre_personnes 
        CHECK(nombre_personnes >= nombre_personne_min_snapshot),
    
    INDEX idx_utilisateur (id_utilisateur),
    INDEX idx_menu (id_menu),
    INDEX idx_statut (statut),
    INDEX idx_date_commande (date_commande),
    INDEX idx_date_prestation (date_prestation),
    INDEX idx_ville (ville)
) ENGINE=InnoDB COMMENT='Commandes passées par les clients';

CREATE TABLE IF NOT EXISTS COMMANDE_MATERIEL (
    id_commande_materiel INT AUTO_INCREMENT PRIMARY KEY,
    id_commande INT NOT NULL,
    id_materiel INT NOT NULL,
    quantite INT NOT NULL CHECK(quantite > 0),
    date_pret DATETIME NOT NULL,
    date_retour_prevu DATETIME NOT NULL,
    date_retour_effectif DATETIME,
    retourne BOOLEAN NOT NULL DEFAULT FALSE,
    
    CONSTRAINT fk_commande_materiel_commande 
        FOREIGN KEY (id_commande) 
        REFERENCES COMMANDE(id_commande) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_commande_materiel_materiel 
        FOREIGN KEY (id_materiel) 
        REFERENCES MATERIEL(id_materiel) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    
    INDEX idx_commande (id_commande),
    INDEX idx_materiel (id_materiel),
    INDEX idx_retourne (retourne),
    INDEX idx_date_retour_prevu (date_retour_prevu)
) ENGINE=InnoDB COMMENT='Matériel prêté pour chaque commande';

-- ============================================================
-- TRAÇABILITÉ (HISTORIQUE & MODIFICATIONS)
-- ============================================================

CREATE TABLE IF NOT EXISTS COMMANDE_STATUT (
    id_statut INT AUTO_INCREMENT PRIMARY KEY,
    id_commande INT NOT NULL,
    statut ENUM('EN_ATTENTE', 'ACCEPTE', 'EN_PREPARATION', 'EN_LIVRAISON', 'LIVRE', 'EN_ATTENTE_RETOUR', 'TERMINEE', 'ANNULEE') NOT NULL,
    date_changement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_par INT NOT NULL,
    commentaire VARCHAR(255),
    
    CONSTRAINT fk_commande_statut_commande 
        FOREIGN KEY (id_commande) 
        REFERENCES COMMANDE(id_commande) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_commande_statut_utilisateur 
        FOREIGN KEY (modifie_par) 
        REFERENCES UTILISATEUR(id_utilisateur) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    
    INDEX idx_commande (id_commande),
    INDEX idx_statut (statut),
    INDEX idx_date_changement (date_changement)
) ENGINE=InnoDB COMMENT='Historique des changements de statut des commandes';

CREATE TABLE IF NOT EXISTS COMMANDE_ANNULATION (
    id_annulation INT AUTO_INCREMENT PRIMARY KEY,
    id_commande INT NOT NULL,
    annule_par INT NOT NULL,
    mode_contact ENUM('GSM', 'MAIL') NOT NULL,
    motif TEXT NOT NULL,
    date_annulation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_commande_annulation_commande 
        FOREIGN KEY (id_commande) 
        REFERENCES COMMANDE(id_commande) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_commande_annulation_utilisateur 
        FOREIGN KEY (annule_par) 
        REFERENCES UTILISATEUR(id_utilisateur) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    
    INDEX idx_commande (id_commande),
    INDEX idx_date_annulation (date_annulation)
) ENGINE=InnoDB COMMENT='Historique des annulations de commandes avec motif';

CREATE TABLE IF NOT EXISTS COMMANDE_MODIFICATION (
    id_modif INT AUTO_INCREMENT PRIMARY KEY,
    id_commande INT NOT NULL,
    modifie_par INT NOT NULL,
    date_modif DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    champs_modified JSON NOT NULL,
    
    CONSTRAINT fk_commande_modification_commande 
        FOREIGN KEY (id_commande) 
        REFERENCES COMMANDE(id_commande) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_commande_modification_utilisateur 
        FOREIGN KEY (modifie_par) 
        REFERENCES UTILISATEUR(id_utilisateur) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    
    INDEX idx_commande (id_commande),
    INDEX idx_date_modif (date_modif)
) ENGINE=InnoDB COMMENT='Historique des modifications de commandes';

-- ============================================================
-- AVIS (FALLBACK MySQL pour panne MongoDB)
-- ============================================================

CREATE TABLE IF NOT EXISTS AVIS_FALLBACK (
    id_avis_fallback INT AUTO_INCREMENT PRIMARY KEY,
    note TINYINT NOT NULL CHECK(note BETWEEN 1 AND 5),
    commentaire TEXT NOT NULL,
    statut_validation ENUM('VALIDE', 'REFUSE', 'EN_ATTENTE') NOT NULL DEFAULT 'EN_ATTENTE',
    date_avis DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur INT NOT NULL,
    id_commande INT NOT NULL,
    id_menu INT NOT NULL,
    modere_par INT,
    date_validation DATETIME,
    mongo_id VARCHAR(24),
    
    CONSTRAINT fk_avis_utilisateur 
        FOREIGN KEY (id_utilisateur) 
        REFERENCES UTILISATEUR(id_utilisateur) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_avis_commande 
        FOREIGN KEY (id_commande) 
        REFERENCES COMMANDE(id_commande) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_avis_menu 
        FOREIGN KEY (id_menu) 
        REFERENCES MENU(id_menu) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_avis_moderateur 
        FOREIGN KEY (modere_par) 
        REFERENCES UTILISATEUR(id_utilisateur) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE,
    
    INDEX idx_statut_validation (statut_validation),
    INDEX idx_note (note),
    INDEX idx_date_avis (date_avis),
    INDEX idx_menu (id_menu),
    INDEX idx_mongo_id (mongo_id)
) ENGINE=InnoDB COMMENT='Avis clients (fallback MySQL en cas de panne MongoDB)';

-- ============================================================
-- TRIGGERS & ÉVÉNEMENTS
-- ============================================================

-- Suppression du trigger s'il existe déjà
DROP TRIGGER IF EXISTS after_commande_insert;
DELIMITER //
CREATE TRIGGER after_commande_insert
AFTER INSERT ON COMMANDE
FOR EACH ROW
BEGIN
    INSERT INTO COMMANDE_STATUT (id_commande, statut, modifie_par, commentaire)
    VALUES (NEW.id_commande, NEW.statut, NEW.id_utilisateur, 'Commande créée');
END//
DELIMITER ;

-- Suppression du trigger s'il existe déjà
DROP TRIGGER IF EXISTS after_commande_update_statut;
DELIMITER //
CREATE TRIGGER after_commande_update_statut
AFTER UPDATE ON COMMANDE
FOR EACH ROW
BEGIN
    IF NEW.statut != OLD.statut THEN
        INSERT INTO COMMANDE_STATUT (id_commande, statut, modifie_par, commentaire)
        VALUES (NEW.id_commande, NEW.statut, NEW.id_utilisateur, 'Statut modifié');
    END IF;
END//
DELIMITER ;

-- ============================================================
-- VUES UTILES
-- ============================================================

CREATE OR REPLACE VIEW v_menus_actifs AS
SELECT 
    m.id_menu,
    m.titre,
    m.description,
    m.nombre_personne_min,
    m.prix,
    m.stock_disponible,
    m.conditions,
    t.libelle AS theme,
    r.libelle AS regime,
    m.date_publication,
    COUNT(DISTINCT p.id_plat) AS nombre_plats,
    GROUP_CONCAT(DISTINCT im.url ORDER BY im.position) AS images
FROM MENU m
LEFT JOIN THEME t ON m.id_theme = t.id_theme
LEFT JOIN REGIME r ON m.id_regime = r.id_regime
LEFT JOIN PROPOSE p ON m.id_menu = p.id_menu
LEFT JOIN IMAGE_MENU im ON m.id_menu = im.id_menu
WHERE m.actif = TRUE
GROUP BY m.id_menu;

CREATE OR REPLACE VIEW v_commandes_en_cours AS
SELECT 
    c.id_commande,
    c.date_commande,
    c.date_prestation,
    c.statut,
    u.nom,
    u.prenom,
    u.email,
    u.gsm,
    m.titre AS menu,
    c.nombre_personnes,
    c.prix_total,
    c.ville,
    c.materiel_pret
FROM COMMANDE c
JOIN UTILISATEUR u ON c.id_utilisateur = u.id_utilisateur
JOIN MENU m ON c.id_menu = m.id_menu
WHERE c.statut NOT IN ('TERMINEE', 'ANNULEE')
ORDER BY c.date_prestation ASC;

CREATE OR REPLACE VIEW v_avis_valides AS
SELECT 
    a.id_avis_fallback,
    a.note,
    a.commentaire,
    a.date_avis,
    u.prenom AS client_prenom,
    SUBSTRING(u.nom, 1, 1) AS client_initiale,
    m.titre AS menu
FROM AVIS_FALLBACK a
JOIN UTILISATEUR u ON a.id_utilisateur = u.id_utilisateur
JOIN MENU m ON a.id_menu = m.id_menu
WHERE a.statut_validation = 'VALIDE'
ORDER BY a.date_avis DESC;

-- ============================================================
-- FIN DU SCRIPT
-- ============================================================
