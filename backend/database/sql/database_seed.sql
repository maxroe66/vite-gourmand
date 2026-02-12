-- ============================================================
-- Données de PRODUCTION pour "Vite & Gourmand"
-- Ce fichier contient UNIQUEMENT :
--   • Le compte administrateur (José)
--   • Les données de référence (thèmes, régimes, allergènes,
--     plats, menus, horaires, matériel)
--
-- ⚠️  Ne contient AUCUNE donnée de test (commandes, avis,
--     contacts, utilisateurs fictifs).
--
-- Mot de passe initial admin : Jose@VG-Prod2025
-- → À CHANGER impérativement après le premier déploiement.
-- ============================================================

-- Désactivation temporaire des vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- ADMINISTRATEUR
-- ============================================================
-- Mot de passe initial : Jose@VG-Prod2025  (à changer après 1er login)

INSERT IGNORE INTO UTILISATEUR (nom, prenom, gsm, email, adresse_postale, ville, code_postal, mot_de_passe, role, actif, date_creation) VALUES
('Admin', 'José', '0556123456', 'jose@vite-gourmand.fr', '12 rue des Halles', 'Bordeaux', '33000', '$argon2id$v=19$m=65536,t=4,p=1$YlJqazVJbTM1ck9LZ2Jyeg$sPHfSqHCJWUGf/01X4XCgjAUjyNnXQWv1BoPZcbUbI0', 'ADMINISTRATEUR', TRUE, NOW());

-- ============================================================
-- THÈMES & RÉGIMES
-- ============================================================

INSERT IGNORE INTO THEME (libelle) VALUES
('Noël'),
('Pâques'),
('Classique'),
('Événement'),
('Estival');

INSERT IGNORE INTO REGIME (libelle) VALUES
('Classique'),
('Végétarien'),
('Vegan'),
('Sans gluten');

-- ============================================================
-- ALLERGÈNES (14 allergènes réglementaires)
-- ============================================================

INSERT IGNORE INTO ALLERGENE (libelle) VALUES
('Gluten'),
('Crustacés'),
('Œufs'),
('Poissons'),
('Arachides'),
('Soja'),
('Lait'),
('Fruits à coque'),
('Céleri'),
('Moutarde'),
('Sésame'),
('Sulfites'),
('Lupin'),
('Mollusques');

-- ============================================================
-- PLATS
-- ============================================================

-- ENTRÉES
INSERT IGNORE INTO PLAT (libelle, type, description) VALUES
('Foie gras maison sur toast', 'ENTREE', 'Foie gras de canard mi-cuit, pain d''épices et confiture de figues'),
('Velouté de châtaignes', 'ENTREE', 'Crème de châtaignes, éclats de marrons et crème fraîche'),
('Salade de chèvre chaud', 'ENTREE', 'Mesclun, fromage de chèvre rôti, noix et miel'),
('Assiette de saumon fumé', 'ENTREE', 'Saumon fumé maison, blinis et crème citronnée'),
('Tartare de légumes', 'ENTREE', 'Légumes crus marinés, avocat et vinaigrette balsamique');

-- PLATS
INSERT IGNORE INTO PLAT (libelle, type, description) VALUES
('Chapon farci aux marrons', 'PLAT', 'Chapon rôti, farce aux marrons et champignons, jus corsé'),
('Gigot d''agneau aux herbes', 'PLAT', 'Gigot d''agneau rôti sept heures, gratin dauphinois'),
('Pavé de saumon grillé', 'PLAT', 'Saumon grillé, purée de patates douces et légumes de saison'),
('Lasagnes végétariennes', 'PLAT', 'Lasagnes aux légumes du soleil, sauce béchamel'),
('Risotto aux champignons', 'PLAT', 'Risotto crémeux, cèpes et parmesan'),
('Magret de canard au miel', 'PLAT', 'Magret de canard rôti, sauce au miel et thym');

-- DESSERTS
INSERT IGNORE INTO PLAT (libelle, type, description) VALUES
('Bûche de Noël chocolat', 'DESSERT', 'Biscuit roulé au chocolat, crème au beurre pralinée'),
('Tarte tatin aux pommes', 'DESSERT', 'Pommes caramélisées sur pâte feuilletée, crème fraîche'),
('Mousse au chocolat maison', 'DESSERT', 'Mousse au chocolat noir 70%, éclats de nougatine'),
('Tiramisu traditionnel', 'DESSERT', 'Biscuits imbibés de café, crème mascarpone'),
('Salade de fruits frais', 'DESSERT', 'Fruits de saison, coulis de fruits rouges'),
('Fondant au chocolat', 'DESSERT', 'Coulant au chocolat, glace vanille');

-- ============================================================
-- ASSOCIATIONS PLATS - ALLERGÈNES
-- ============================================================

INSERT IGNORE INTO PLAT_ALLERGENE (id_plat, id_allergene) VALUES
-- Foie gras sur toast
(1, 1), (1, 7),
-- Velouté de châtaignes
(2, 7), (2, 9),
-- Salade chèvre chaud
(3, 7), (3, 8),
-- Saumon fumé
(4, 4), (4, 1), (4, 3), (4, 7),
-- Chapon farci
(6, 1), (6, 3), (6, 7), (6, 9),
-- Gigot d'agneau
(7, 7), (7, 9),
-- Pavé de saumon
(8, 4), (8, 7),
-- Lasagnes végétariennes
(9, 1), (9, 3), (9, 7),
-- Risotto champignons
(10, 7), (10, 9),
-- Magret de canard
(11, 9),
-- Bûche chocolat
(12, 1), (12, 3), (12, 7), (12, 6),
-- Tarte tatin
(13, 1), (13, 7), (13, 3),
-- Mousse chocolat
(14, 3), (14, 7),
-- Tiramisu
(15, 1), (15, 3), (15, 7),
-- Fondant chocolat
(17, 1), (17, 3), (17, 7);

-- ============================================================
-- MENUS
-- ============================================================

INSERT IGNORE INTO MENU (id_menu, titre, description, nombre_personne_min, prix, stock_disponible, conditions, id_theme, id_regime, actif, date_publication) VALUES
(1, 'Menu de Noël Traditionnel', 'Un repas festif pour célébrer Noël en famille avec des mets traditionnels et raffinés. Parfait pour vos réunions familiales.', 6, 150.00, 10, 'Commande à passer au minimum 7 jours avant la prestation. Matériel de service disponible en prêt.', 1, 1, TRUE, '2024-11-01 10:00:00'),
(2, 'Menu de Pâques Gourmand', 'Célébrez Pâques avec ce menu printanier à base d''agneau et de légumes de saison.', 4, 120.00, 15, 'Commande à passer au minimum 5 jours avant la prestation.', 2, 1, TRUE, '2024-03-01 10:00:00'),
(3, 'Menu Végétarien Raffiné', 'Un menu 100% végétarien qui ravira vos convives soucieux de l''environnement.', 4, 95.00, 20, 'Commande à passer au minimum 3 jours avant la prestation.', 3, 2, TRUE, '2024-01-20 10:00:00'),
(4, 'Menu Classique 4 Saisons', 'Un menu équilibré et savoureux pour toutes les occasions, avec des produits frais et locaux.', 4, 110.00, 12, 'Commande à passer au minimum 48h avant la prestation.', 3, 1, TRUE, '2024-02-15 10:00:00'),
(5, 'Menu Estival Léger', 'Menu frais et léger parfait pour les beaux jours et les événements en extérieur.', 6, 130.00, 8, 'Disponible uniquement de mai à septembre. Commande 5 jours avant.', 5, 1, TRUE, '2024-05-01 10:00:00'),
(6, 'Menu Vegan Créatif', 'Menu 100% végétal, sans produits d''origine animale, créatif et gourmand.', 4, 105.00, 10, 'Commande à passer au minimum 4 jours avant la prestation.', 3, 3, TRUE, '2024-03-10 10:00:00');

-- ============================================================
-- ASSOCIATIONS MENUS - PLATS
-- ============================================================

-- Menu de Noël Traditionnel
INSERT IGNORE INTO PROPOSE (id_menu, id_plat, position) VALUES
(1, 1, 1),  -- Foie gras
(1, 6, 2),  -- Chapon farci
(1, 12, 3); -- Bûche chocolat

-- Menu de Pâques Gourmand
INSERT IGNORE INTO PROPOSE (id_menu, id_plat, position) VALUES
(2, 4, 1),  -- Saumon fumé
(2, 7, 2),  -- Gigot d'agneau
(2, 13, 3); -- Tarte tatin

-- Menu Végétarien Raffiné
INSERT IGNORE INTO PROPOSE (id_menu, id_plat, position) VALUES
(3, 3, 1),  -- Salade chèvre chaud
(3, 10, 2), -- Risotto champignons
(3, 16, 3); -- Salade de fruits

-- Menu Classique 4 Saisons
INSERT IGNORE INTO PROPOSE (id_menu, id_plat, position) VALUES
(4, 2, 1),  -- Velouté châtaignes
(4, 11, 2), -- Magret de canard
(4, 15, 3); -- Tiramisu

-- Menu Estival Léger
INSERT IGNORE INTO PROPOSE (id_menu, id_plat, position) VALUES
(5, 4, 1),  -- Saumon fumé
(5, 8, 2),  -- Pavé de saumon
(5, 16, 3); -- Salade de fruits

-- Menu Vegan Créatif
INSERT IGNORE INTO PROPOSE (id_menu, id_plat, position) VALUES
(6, 5, 1),  -- Tartare de légumes
(6, 9, 2),  -- Lasagnes végétariennes
(6, 16, 3); -- Salade de fruits

-- ============================================================
-- HORAIRES
-- ============================================================

INSERT IGNORE INTO HORAIRE (id_horaire, jour, heure_ouverture, heure_fermeture, ferme) VALUES
(1, 'LUNDI', '09:00:00', '18:00:00', FALSE),
(2, 'MARDI', '09:00:00', '18:00:00', FALSE),
(3, 'MERCREDI', '09:00:00', '18:00:00', FALSE),
(4, 'JEUDI', '09:00:00', '18:00:00', FALSE),
(5, 'VENDREDI', '09:00:00', '18:00:00', FALSE),
(6, 'SAMEDI', '10:00:00', '16:00:00', FALSE),
(7, 'DIMANCHE', NULL, NULL, TRUE);

-- ============================================================
-- MATÉRIEL
-- ============================================================

INSERT IGNORE INTO MATERIEL (id_materiel, libelle, description, valeur_unitaire, stock_disponible) VALUES
(1, 'Assiettes en porcelaine (lot de 12)', 'Service complet d''assiettes blanches en porcelaine', 180.00, 10),
(2, 'Couverts en inox (lot de 12)', 'Service complet de couverts (couteau, fourchette, cuillère)', 150.00, 10),
(3, 'Verres à vin (lot de 12)', 'Verres à vin rouge et blanc', 120.00, 8),
(4, 'Nappe blanche 6 personnes', 'Nappe en lin blanc, dimension 150x200cm', 45.00, 15),
(5, 'Chafing dish (réchaud)', 'Réchaud professionnel pour maintenir les plats au chaud', 250.00, 5),
(6, 'Plats de service (set de 3)', 'Grands plats ovales en inox pour le service', 90.00, 8);

-- ============================================================
-- ASSOCIATIONS MENUS - MATÉRIEL (Configuration par défaut)
-- ============================================================

-- Menu de Noël (Id 1) : Assiettes, Couverts, Verres
INSERT IGNORE INTO MENU_MATERIEL (id_menu, id_materiel, quantite_par_personne) VALUES
(1, 1, 1), -- Assiettes
(1, 2, 1), -- Couverts
(1, 3, 1); -- Verres à vin

-- Menu de Pâques (Id 2) : Assiettes, Couverts
INSERT IGNORE INTO MENU_MATERIEL (id_menu, id_materiel, quantite_par_personne) VALUES
(2, 1, 1), -- Assiettes
(2, 2, 1); -- Couverts

-- Menu Estival (Id 5) : Verres à vin
INSERT IGNORE INTO MENU_MATERIEL (id_menu, id_materiel, quantite_par_personne) VALUES
(5, 3, 1); -- Verres

-- Réactivation des vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'SEED DE PRODUCTION APPLIQUÉ AVEC SUCCÈS' AS Status;
