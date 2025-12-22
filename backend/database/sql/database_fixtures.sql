-- ============================================================
-- Données de test (Fixtures) pour "Vite & Gourmand"
-- Version: 1.0
-- Date: 11 décembre 2025
-- ============================================================

USE vite_et_gourmand;

-- Désactivation temporaire des vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- UTILISATEURS
-- ============================================================
-- Mot de passe pour tous les comptes de test : "Password123!"
-- Hash bcrypt : $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO UTILISATEUR (id_utilisateur, nom, prenom, gsm, email, adresse_postale, ville, code_postal, mot_de_passe, role, actif, date_creation) VALUES
(1, 'Admin', 'José', '0556123456', 'jose@vite-gourmand.fr', '12 rue des Halles', 'Bordeaux', '33000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMINISTRATEUR', TRUE, '2024-01-15 10:00:00'),
(2, 'Employe', 'Julie', '0556789012', 'julie@vite-gourmand.fr', '12 rue des Halles', 'Bordeaux', '33000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'EMPLOYE', TRUE, '2024-01-15 10:30:00'),
(3, 'Dupont', 'Marie', '0601020304', 'marie.dupont@email.fr', '25 cours de l''Intendance', 'Bordeaux', '33000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'UTILISATEUR', TRUE, '2024-02-10 14:30:00'),
(4, 'Martin', 'Pierre', '0612345678', 'pierre.martin@email.fr', '48 rue Sainte-Catherine', 'Bordeaux', '33000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'UTILISATEUR', TRUE, '2024-03-05 16:20:00'),
(5, 'Bernard', 'Sophie', '0623456789', 'sophie.bernard@email.fr', '15 place de la Victoire', 'Bordeaux', '33000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'UTILISATEUR', TRUE, '2024-04-12 11:15:00'),
(6, 'Lefebvre', 'Thomas', '0634567890', 'thomas.lefebvre@email.fr', '8 avenue Victor Hugo', 'Bordeaux', '33200', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'UTILISATEUR', TRUE, '2024-05-20 09:45:00'),
(7, 'Moreau', 'Claire', '0645678901', 'claire.moreau@email.fr', '22 rue de la Devise', 'Bordeaux', '33000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'UTILISATEUR', TRUE, '2024-06-08 15:30:00');

-- ============================================================
-- THÈMES & RÉGIMES
-- ============================================================

INSERT INTO THEME (id_theme, libelle) VALUES
(1, 'Noël'),
(2, 'Pâques'),
(3, 'Classique'),
(4, 'Événement'),
(5, 'Estival');

INSERT INTO REGIME (id_regime, libelle) VALUES
(1, 'Classique'),
(2, 'Végétarien'),
(3, 'Vegan'),
(4, 'Sans gluten');

-- ============================================================
-- ALLERGÈNES
-- ============================================================

INSERT INTO ALLERGENE (id_allergene, libelle) VALUES
(1, 'Gluten'),
(2, 'Crustacés'),
(3, 'Œufs'),
(4, 'Poissons'),
(5, 'Arachides'),
(6, 'Soja'),
(7, 'Lait'),
(8, 'Fruits à coque'),
(9, 'Céleri'),
(10, 'Moutarde'),
(11, 'Sésame'),
(12, 'Sulfites'),
(13, 'Lupin'),
(14, 'Mollusques');

-- ============================================================
-- PLATS
-- ============================================================

-- ENTRÉES
INSERT INTO PLAT (id_plat, libelle, type, description) VALUES
(1, 'Foie gras maison sur toast', 'ENTREE', 'Foie gras de canard mi-cuit, pain d\'épices et confiture de figues'),
(2, 'Velouté de châtaignes', 'ENTREE', 'Crème de châtaignes, éclats de marrons et crème fraîche'),
(3, 'Salade de chèvre chaud', 'ENTREE', 'Mesclun, fromage de chèvre rôti, noix et miel'),
(4, 'Assiette de saumon fumé', 'ENTREE', 'Saumon fumé maison, blinis et crème citronnée'),
(5, 'Tartare de légumes', 'ENTREE', 'Légumes crus marinés, avocat et vinaigrette balsamique');

-- PLATS
INSERT INTO PLAT (id_plat, libelle, type, description) VALUES
(6, 'Chapon farci aux marrons', 'PLAT', 'Chapon rôti, farce aux marrons et champignons, jus corsé'),
(7, 'Gigot d\'agneau aux herbes', 'PLAT', 'Gigot d\'agneau rôti sept heures, gratin dauphinois'),
(8, 'Pavé de saumon grillé', 'PLAT', 'Saumon grillé, purée de patates douces et légumes de saison'),
(9, 'Lasagnes végétariennes', 'PLAT', 'Lasagnes aux légumes du soleil, sauce béchamel'),
(10, 'Risotto aux champignons', 'PLAT', 'Risotto crémeux, cèpes et parmesan'),
(11, 'Magret de canard au miel', 'PLAT', 'Magret de canard rôti, sauce au miel et thym');

-- DESSERTS
INSERT INTO PLAT (id_plat, libelle, type, description) VALUES
(12, 'Bûche de Noël chocolat', 'DESSERT', 'Biscuit roulé au chocolat, crème au beurre pralinée'),
(13, 'Tarte tatin aux pommes', 'DESSERT', 'Pommes caramélisées sur pâte feuilletée, crème fraîche'),
(14, 'Mousse au chocolat maison', 'DESSERT', 'Mousse au chocolat noir 70%, éclats de nougatine'),
(15, 'Tiramisu traditionnel', 'DESSERT', 'Biscuits imbibés de café, crème mascarpone'),
(16, 'Salade de fruits frais', 'DESSERT', 'Fruits de saison, coulis de fruits rouges'),
(17, 'Fondant au chocolat', 'DESSERT', 'Coulant au chocolat, glace vanille');

-- ============================================================
-- ASSOCIATIONS PLATS - ALLERGÈNES
-- ============================================================

INSERT INTO PLAT_ALLERGENE (id_plat, id_allergene) VALUES
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

INSERT INTO MENU (id_menu, titre, description, nombre_personne_min, prix, stock_disponible, conditions, id_theme, id_regime, actif, date_publication) VALUES
(1, 'Menu de Noël Traditionnel', 'Un repas festif pour célébrer Noël en famille avec des mets traditionnels et raffinés. Parfait pour vos réunions familiales.', 6, 150.00, 10, 'Commande à passer au minimum 7 jours avant la prestation. Matériel de service disponible en prêt.', 1, 1, TRUE, '2024-11-01 10:00:00'),
(2, 'Menu de Pâques Gourmand', 'Célébrez Pâques avec ce menu printanier à base d\'agneau et de légumes de saison.', 4, 120.00, 15, 'Commande à passer au minimum 5 jours avant la prestation.', 2, 1, TRUE, '2024-03-01 10:00:00'),
(3, 'Menu Végétarien Raffiné', 'Un menu 100% végétarien qui ravira vos convives soucieux de l\'environnement.', 4, 95.00, 20, 'Commande à passer au minimum 3 jours avant la prestation.', 3, 2, TRUE, '2024-01-20 10:00:00'),
(4, 'Menu Classique 4 Saisons', 'Un menu équilibré et savoureux pour toutes les occasions, avec des produits frais et locaux.', 4, 110.00, 12, 'Commande à passer au minimum 48h avant la prestation.', 3, 1, TRUE, '2024-02-15 10:00:00'),
(5, 'Menu Estival Léger', 'Menu frais et léger parfait pour les beaux jours et les événements en extérieur.', 6, 130.00, 8, 'Disponible uniquement de mai à septembre. Commande 5 jours avant.', 5, 1, TRUE, '2024-05-01 10:00:00'),
(6, 'Menu Vegan Créatif', 'Menu 100% végétal, sans produits d\'origine animale, créatif et gourmand.', 4, 105.00, 10, 'Commande à passer au minimum 4 jours avant la prestation.', 3, 3, TRUE, '2024-03-10 10:00:00');

-- ============================================================
-- IMAGES MENUS
-- ============================================================

INSERT INTO IMAGE_MENU (id_image, id_menu, url, alt_text, position) VALUES
-- Menu de Noël
(1, 1, '/images/menus/noel-1.jpg', 'Foie gras et champagne', 1),
(2, 1, '/images/menus/noel-2.jpg', 'Chapon farci aux marrons', 2),
(3, 1, '/images/menus/noel-3.jpg', 'Bûche de Noël chocolat', 3),
-- Menu de Pâques
(4, 2, '/images/menus/paques-1.jpg', 'Gigot d\'agneau', 1),
(5, 2, '/images/menus/paques-2.jpg', 'Tarte tatin maison', 2),
-- Menu Végétarien
(6, 3, '/images/menus/vegetarien-1.jpg', 'Assortiment de légumes', 1),
(7, 3, '/images/menus/vegetarien-2.jpg', 'Risotto aux champignons', 2),
-- Menu Classique
(8, 4, '/images/menus/classique-1.jpg', 'Présentation du menu', 1),
-- Menu Estival
(9, 5, '/images/menus/estival-1.jpg', 'Saumon grillé', 1),
(10, 5, '/images/menus/estival-2.jpg', 'Salade de fruits frais', 2),
-- Menu Vegan
(11, 6, '/images/menus/vegan-1.jpg', 'Tartare de légumes', 1),
(12, 6, '/images/menus/vegan-2.jpg', 'Lasagnes végétariennes', 2);

-- ============================================================
-- ASSOCIATIONS MENUS - PLATS
-- ============================================================

-- Menu de Noël Traditionnel
INSERT INTO PROPOSE (id_menu, id_plat, position) VALUES
(1, 1, 1),  -- Foie gras
(1, 6, 2),  -- Chapon farci
(1, 12, 3); -- Bûche chocolat

-- Menu de Pâques Gourmand
INSERT INTO PROPOSE (id_menu, id_plat, position) VALUES
(2, 4, 1),  -- Saumon fumé
(2, 7, 2),  -- Gigot d'agneau
(2, 13, 3); -- Tarte tatin

-- Menu Végétarien Raffiné
INSERT INTO PROPOSE (id_menu, id_plat, position) VALUES
(3, 3, 1),  -- Salade chèvre chaud
(3, 10, 2), -- Risotto champignons
(3, 16, 3); -- Salade de fruits

-- Menu Classique 4 Saisons
INSERT INTO PROPOSE (id_menu, id_plat, position) VALUES
(4, 2, 1),  -- Velouté châtaignes
(4, 11, 2), -- Magret de canard
(4, 15, 3); -- Tiramisu

-- Menu Estival Léger
INSERT INTO PROPOSE (id_menu, id_plat, position) VALUES
(5, 4, 1),  -- Saumon fumé
(5, 8, 2),  -- Pavé de saumon
(5, 16, 3); -- Salade de fruits

-- Menu Vegan Créatif
INSERT INTO PROPOSE (id_menu, id_plat, position) VALUES
(6, 5, 1),  -- Tartare de légumes
(6, 9, 2),  -- Lasagnes végétariennes
(6, 16, 3); -- Salade de fruits

-- ============================================================
-- HORAIRES
-- ============================================================

INSERT INTO HORAIRE (id_horaire, jour, heure_ouverture, heure_fermeture, ferme) VALUES
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

INSERT INTO MATERIEL (id_materiel, libelle, description, valeur_unitaire, stock_disponible) VALUES
(1, 'Assiettes en porcelaine (lot de 12)', 'Service complet d\'assiettes blanches en porcelaine', 180.00, 10),
(2, 'Couverts en inox (lot de 12)', 'Service complet de couverts (couteau, fourchette, cuillère)', 150.00, 10),
(3, 'Verres à vin (lot de 12)', 'Verres à vin rouge et blanc', 120.00, 8),
(4, 'Nappe blanche 6 personnes', 'Nappe en lin blanc, dimension 150x200cm', 45.00, 15),
(5, 'Chafing dish (réchaud)', 'Réchaud professionnel pour maintenir les plats au chaud', 250.00, 5),
(6, 'Plats de service (set de 3)', 'Grands plats ovales en inox pour le service', 90.00, 8);

-- ============================================================
-- COMMANDES
-- ============================================================

-- Commande 1 : Marie Dupont - Menu de Noël - EN_ATTENTE
INSERT INTO COMMANDE (id_commande, id_utilisateur, id_menu, date_commande, date_prestation, heure_livraison, adresse_livraison, ville, code_postal, gsm, nombre_personnes, nombre_personne_min_snapshot, prix_menu_unitaire, montant_reduction, reduction_appliquee, frais_livraison, prix_total, hors_bordeaux, distance_km, statut, has_avis, materiel_pret) VALUES
(1, 3, 1, '2024-12-05 14:30:00', '2024-12-24', '18:00:00', '25 cours de l\'Intendance', 'Bordeaux', '33000', '0601020304', 6, 6, 25.00, 0.00, FALSE, 5.00, 155.00, FALSE, 0.00, 'EN_ATTENTE', FALSE, TRUE);

-- Commande 2 : Pierre Martin - Menu Classique - ACCEPTE
INSERT INTO COMMANDE (id_commande, id_utilisateur, id_menu, date_commande, date_prestation, heure_livraison, adresse_livraison, ville, code_postal, gsm, nombre_personnes, nombre_personne_min_snapshot, prix_menu_unitaire, montant_reduction, reduction_appliquee, frais_livraison, prix_total, hors_bordeaux, distance_km, statut, has_avis, materiel_pret) VALUES
(2, 4, 4, '2024-11-20 10:15:00', '2024-12-15', '19:00:00', '48 rue Sainte-Catherine', 'Bordeaux', '33000', '0612345678', 8, 4, 27.50, 22.00, TRUE, 5.00, 225.00, FALSE, 0.00, 'ACCEPTE', FALSE, FALSE);

-- Commande 3 : Sophie Bernard - Menu Végétarien - EN_PREPARATION
INSERT INTO COMMANDE (id_commande, id_utilisateur, id_menu, date_commande, date_prestation, heure_livraison, adresse_livraison, ville, code_postal, gsm, nombre_personnes, nombre_personne_min_snapshot, prix_menu_unitaire, montant_reduction, reduction_appliquee, frais_livraison, prix_total, hors_bordeaux, distance_km, statut, has_avis, materiel_pret) VALUES
(3, 5, 3, '2024-11-25 16:45:00', '2024-12-10', '12:00:00', '15 place de la Victoire', 'Bordeaux', '33000', '0623456789', 6, 4, 23.75, 14.25, TRUE, 5.00, 151.75, FALSE, 0.00, 'EN_PREPARATION', FALSE, TRUE);

-- Commande 4 : Thomas Lefebvre - Menu de Pâques - LIVRE (avec avis à donner)
INSERT INTO COMMANDE (id_commande, id_utilisateur, id_menu, date_commande, date_prestation, heure_livraison, adresse_livraison, ville, code_postal, gsm, nombre_personnes, nombre_personne_min_snapshot, prix_menu_unitaire, montant_reduction, reduction_appliquee, frais_livraison, prix_total, hors_bordeaux, distance_km, statut, has_avis, materiel_pret, date_livraison_effective) VALUES
(4, 6, 2, '2024-10-15 09:30:00', '2024-11-10', '13:00:00', '8 avenue Victor Hugo', 'Bordeaux', '33200', '0634567890', 8, 4, 30.00, 24.00, TRUE, 5.00, 245.00, FALSE, 0.00, 'LIVRE', FALSE, FALSE, '2024-11-10 12:45:00');

-- Commande 5 : Claire Moreau - Menu Vegan - TERMINEE (avec avis donné)
INSERT INTO COMMANDE (id_commande, id_utilisateur, id_menu, date_commande, date_prestation, heure_livraison, adresse_livraison, ville, code_postal, gsm, nombre_personnes, nombre_personne_min_snapshot, prix_menu_unitaire, montant_reduction, reduction_appliquee, frais_livraison, prix_total, hors_bordeaux, distance_km, statut, has_avis, materiel_pret, date_livraison_effective) VALUES
(5, 7, 6, '2024-09-20 11:20:00', '2024-10-05', '19:30:00', '22 rue de la Devise', 'Bordeaux', '33000', '0645678901', 4, 4, 26.25, 0.00, FALSE, 5.00, 110.00, FALSE, 0.00, 'TERMINEE', TRUE, FALSE, '2024-10-05 19:15:00');

-- Commande 6 : Marie Dupont - Menu Estival - TERMINEE (hors Bordeaux avec avis)
INSERT INTO COMMANDE (id_commande, id_utilisateur, id_menu, date_commande, date_prestation, heure_livraison, adresse_livraison, ville, code_postal, gsm, nombre_personnes, nombre_personne_min_snapshot, prix_menu_unitaire, montant_reduction, reduction_appliquee, frais_livraison, prix_total, hors_bordeaux, distance_km, statut, has_avis, materiel_pret, date_livraison_effective) VALUES
(6, 3, 5, '2024-07-10 14:00:00', '2024-08-15', '18:00:00', '45 avenue de la Plage', 'Arcachon', '33120', '0601020304', 10, 6, 21.67, 21.70, TRUE, 35.00, 251.70, TRUE, 50.85, 'TERMINEE', TRUE, FALSE, '2024-08-15 17:50:00');

-- Commande 7 : Pierre Martin - Menu de Noël - EN_ATTENTE_RETOUR (matériel prêté)
INSERT INTO COMMANDE (id_commande, id_utilisateur, id_menu, date_commande, date_prestation, heure_livraison, adresse_livraison, ville, code_postal, gsm, nombre_personnes, nombre_personne_min_snapshot, prix_menu_unitaire, montant_reduction, reduction_appliquee, frais_livraison, prix_total, hors_bordeaux, distance_km, statut, has_avis, materiel_pret, date_livraison_effective) VALUES
(7, 4, 1, '2024-11-15 15:30:00', '2024-12-01', '17:00:00', '48 rue Sainte-Catherine', 'Bordeaux', '33000', '0612345678', 10, 6, 25.00, 25.00, TRUE, 5.00, 255.00, FALSE, 0.00, 'EN_ATTENTE_RETOUR', FALSE, TRUE, '2024-12-01 16:50:00');

-- ============================================================
-- MATÉRIEL PRÊTÉ
-- ============================================================

-- Commande 1 : Marie (Menu Noël) - matériel prêté non retourné
INSERT INTO COMMANDE_MATERIEL (id_commande_materiel, id_commande, id_materiel, quantite, date_pret, date_retour_prevu, date_retour_effectif, retourne) VALUES
(1, 1, 1, 1, '2024-12-24 18:00:00', '2024-12-26 14:00:00', NULL, FALSE),
(2, 1, 2, 1, '2024-12-24 18:00:00', '2024-12-26 14:00:00', NULL, FALSE);

-- Commande 3 : Sophie (Menu Végétarien) - matériel prêté non retourné
INSERT INTO COMMANDE_MATERIEL (id_commande_materiel, id_commande, id_materiel, quantite, date_pret, date_retour_prevu, date_retour_effectif, retourne) VALUES
(3, 3, 4, 1, '2024-12-10 12:00:00', '2024-12-12 14:00:00', NULL, FALSE),
(4, 3, 5, 1, '2024-12-10 12:00:00', '2024-12-12 14:00:00', NULL, FALSE);

-- Commande 7 : Pierre (Menu Noël) - matériel prêté en attente de retour
INSERT INTO COMMANDE_MATERIEL (id_commande_materiel, id_commande, id_materiel, quantite, date_pret, date_retour_prevu, date_retour_effectif, retourne) VALUES
(5, 7, 1, 1, '2024-12-01 17:00:00', '2024-12-03 14:00:00', NULL, FALSE),
(6, 7, 2, 1, '2024-12-01 17:00:00', '2024-12-03 14:00:00', NULL, FALSE),
(7, 7, 3, 1, '2024-12-01 17:00:00', '2024-12-03 14:00:00', NULL, FALSE);

-- ============================================================
-- HISTORIQUE STATUTS COMMANDES
-- ============================================================

-- Historique Commande 1 (EN_ATTENTE)

INSERT INTO COMMANDE_STATUT (id_commande, statut, date_changement, modifie_par, commentaire) VALUES
(1, 'EN_ATTENTE', '2024-12-05 14:30:00', 3, 'Commande créée');

-- Historique Commande 2 (ACCEPTE)
INSERT INTO COMMANDE_STATUT (id_commande, statut, date_changement, modifie_par, commentaire) VALUES
(2, 'EN_ATTENTE', '2024-11-20 10:15:00', 4, 'Commande créée'),
(2, 'ACCEPTE', '2024-11-21 09:00:00', 2, 'Commande validée par Julie');

-- Historique Commande 3 (EN_PREPARATION)
INSERT INTO COMMANDE_STATUT (id_commande, statut, date_changement, modifie_par, commentaire) VALUES
(3, 'EN_ATTENTE', '2024-11-25 16:45:00', 5, 'Commande créée'),
(3, 'ACCEPTE', '2024-11-26 10:30:00', 2, 'Commande validée'),
(3, 'EN_PREPARATION', '2024-12-08 08:00:00', 2, 'Préparation en cuisine');

-- Historique Commande 4 (LIVRE)
INSERT INTO COMMANDE_STATUT (id_commande, statut, date_changement, modifie_par, commentaire) VALUES
(4, 'EN_ATTENTE', '2024-10-15 09:30:00', 6, 'Commande créée'),
(4, 'ACCEPTE', '2024-10-16 11:00:00', 2, 'Commande validée'),
(4, 'EN_PREPARATION', '2024-11-08 07:00:00', 2, 'Préparation démarrée'),
(4, 'EN_LIVRAISON', '2024-11-10 11:00:00', 2, 'Livraison en cours'),
(4, 'LIVRE', '2024-11-10 12:45:00', 2, 'Livraison effectuée');

-- Historique Commande 5 (TERMINEE)
INSERT INTO COMMANDE_STATUT (id_commande, statut, date_changement, modifie_par, commentaire) VALUES
(5, 'EN_ATTENTE', '2024-09-20 11:20:00', 7, 'Commande créée'),
(5, 'ACCEPTE', '2024-09-21 09:00:00', 2, 'Commande validée'),
(5, 'EN_PREPARATION', '2024-10-03 08:00:00', 2, 'Préparation cuisine'),
(5, 'EN_LIVRAISON', '2024-10-05 17:00:00', 2, 'Départ livraison'),
(5, 'LIVRE', '2024-10-05 19:15:00', 2, 'Livraison effectuée'),
(5, 'TERMINEE', '2024-10-05 19:30:00', 2, 'Prestation terminée');

-- Historique Commande 6 (TERMINEE - hors Bordeaux)
INSERT INTO COMMANDE_STATUT (id_commande, statut, date_changement, modifie_par, commentaire) VALUES
(6, 'EN_ATTENTE', '2024-07-10 14:00:00', 3, 'Commande créée'),
(6, 'ACCEPTE', '2024-07-11 10:00:00', 2, 'Commande validée'),
(6, 'EN_PREPARATION', '2024-08-13 09:00:00', 2, 'Préparation démarrée'),
(6, 'EN_LIVRAISON', '2024-08-15 15:00:00', 2, 'En route vers Arcachon'),
(6, 'LIVRE', '2024-08-15 17:50:00', 2, 'Livraison effectuée'),
(6, 'TERMINEE', '2024-08-15 18:00:00', 2, 'Prestation terminée');

-- Historique Commande 7 (EN_ATTENTE_RETOUR)
INSERT INTO COMMANDE_STATUT (id_commande, statut, date_changement, modifie_par, commentaire) VALUES
(7, 'EN_ATTENTE', '2024-11-15 15:30:00', 4, 'Commande créée'),
(7, 'ACCEPTE', '2024-11-16 09:30:00', 2, 'Commande validée'),
(7, 'EN_PREPARATION', '2024-11-29 08:00:00', 2, 'Préparation cuisine'),
(7, 'EN_LIVRAISON', '2024-12-01 15:00:00', 2, 'Livraison en cours'),
(7, 'LIVRE', '2024-12-01 16:50:00', 2, 'Livraison effectuée avec matériel'),
(7, 'EN_ATTENTE_RETOUR', '2024-12-01 17:00:00', 2, 'En attente retour matériel');

-- ============================================================
-- AVIS CLIENTS
-- ============================================================

-- Avis validés (page d'accueil)
INSERT INTO AVIS_FALLBACK (id_avis_fallback, note, commentaire, statut_validation, date_avis, id_utilisateur, id_commande, id_menu, modere_par, date_validation) VALUES
(1, 5, 'Prestation exceptionnelle ! Les plats étaient délicieux et la présentation soignée. Je recommande vivement pour vos événements.', 'VALIDE', '2024-10-06 10:30:00', 7, 5, 6, 2, '2024-10-06 14:00:00'),
(2, 5, 'Menu estival parfait pour notre réception. Produits frais, saveurs au rendez-vous. La livraison à Arcachon s\'est très bien passée.', 'VALIDE', '2024-08-16 09:15:00', 3, 6, 5, 2, '2024-08-16 15:30:00'),
(3, 4, 'Très bon rapport qualité-prix. Quelques petits détails à améliorer sur la présentation mais les saveurs étaient au top !', 'VALIDE', '2024-09-10 16:20:00', 6, 4, 2, 2, '2024-09-11 09:00:00');

-- Avis en attente de modération
INSERT INTO AVIS_FALLBACK (id_avis_fallback, note, commentaire, statut_validation, date_avis, id_utilisateur, id_commande, id_menu, modere_par, date_validation) VALUES
(4, 5, 'Commande pour Noël en cours mais contact très professionnel et réactif.', 'EN_ATTENTE', '2024-12-06 11:00:00', 3, 1, 1, NULL, NULL);

-- ============================================================
-- CONTACTS
-- ============================================================

INSERT INTO CONTACT (id_contact, titre, description, email, date_envoi, traite) VALUES
(1, 'Question sur les allergènes', 'Bonjour, je voudrais savoir si vous pouvez adapter le menu de Noël pour une personne allergique aux fruits à coque ? Merci', 'marc.durand@email.fr', '2024-11-28 14:30:00', TRUE),
(2, 'Demande de devis pour mariage', 'Bonjour, nous organisons notre mariage le 15 juin 2025 pour 80 personnes. Pouvez-vous nous proposer un devis ?', 'julie.robert@email.fr', '2024-12-01 10:15:00', FALSE),
(3, 'Disponibilité Menu Pâques', 'Le menu de Pâques sera-t-il disponible pour avril 2025 ? Combien de personnes maximum ?', 'pierre.blanc@email.fr', '2024-12-03 16:45:00', FALSE);

-- Réactivation des vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- RÉSUMÉ DES DONNÉES INSÉRÉES
-- ============================================================

SELECT 'BASE DE DONNÉES INITIALISÉE AVEC SUCCÈS' AS Status;

SELECT 
    'UTILISATEURS' AS Table_Name,
    COUNT(*) AS Nombre_Lignes,
    'Admin: jose@vite-gourmand.fr / Employé: julie@vite-gourmand.fr / Clients: marie.dupont@email.fr, etc.' AS Notes
FROM UTILISATEUR
UNION ALL
SELECT 'MENUS', COUNT(*), '6 menus (Noël, Pâques, Végétarien, Classique, Estival, Vegan)' FROM MENU
UNION ALL
SELECT 'PLATS', COUNT(*), '17 plats (entrées, plats, desserts)' FROM PLAT
UNION ALL
SELECT 'COMMANDES', COUNT(*), '7 commandes (différents statuts pour tester tous les parcours)' FROM COMMANDE
UNION ALL
SELECT 'AVIS', COUNT(*), '4 avis (3 validés pour page accueil, 1 en attente)' FROM AVIS_FALLBACK
UNION ALL
SELECT 'CONTACTS', COUNT(*), '3 messages de contact (1 traité, 2 en attente)' FROM CONTACT;

-- ============================================================
-- IDENTIFIANTS DE TEST
-- ============================================================
/*
COMPTES DE TEST (Mot de passe pour tous : "Password123!")

ADMINISTRATEUR (José):
  Email: jose@vite-gourmand.fr
  Rôle: Peut tout faire (création employés, graphiques, gestion complète)

EMPLOYÉ (Julie):
  Email: julie@vite-gourmand.fr
  Rôle: Gestion menus, commandes, validation avis

CLIENTS:
  - marie.dupont@email.fr (Commande Noël en attente + Commande Estival terminée)
  - pierre.martin@email.fr (Commande acceptée + Commande en attente retour)
  - sophie.bernard@email.fr (Commande en préparation)
  - thomas.lefebvre@email.fr (Commande livrée, peut donner avis)
  - claire.moreau@email.fr (Commande terminée avec avis donné)
*/
