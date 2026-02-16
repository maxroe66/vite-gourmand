// ============================================================
// Script de configuration MongoDB pour "Vite & Gourmand"
// Version: 1.0
// Date: 11 décembre 2025
// MongoDB: 4.4+
// ============================================================

// Connexion à la base de données
// Priorité : variable JS DB_NAME (injectée via --eval) > MONGO_INITDB_DATABASE > DB test par défaut
var dbName =
    (typeof DB_NAME !== 'undefined' && DB_NAME)
        ? DB_NAME
        : (typeof process !== 'undefined' && process.env && process.env.MONGO_INITDB_DATABASE)
            ? process.env.MONGO_INITDB_DATABASE
            : "vite_gourmand_test";

print("=== Environment check ===");
print("process.env.MONGO_INITDB_DATABASE: " + (typeof process !== 'undefined' && process.env ? process.env.MONGO_INITDB_DATABASE : 'undefined'));
print("Injected DB_NAME: " + (typeof DB_NAME !== 'undefined' ? DB_NAME : 'undefined'));
print("Selected dbName: " + dbName);

db = db.getSiblingDB(dbName);

print("=== Base de données utilisée: " + dbName + " ===");

// ============================================================
// COLLECTION : avis
// Stockage des avis clients avec modération
// ============================================================

db.createCollection("avis", {
    validator: {
        $jsonSchema: {
            bsonType: "object",
            required: ["note", "commentaire", "statut_validation", "date_avis", "id_utilisateur", "id_commande", "id_menu"],
            properties: {
                note: {
                    bsonType: ["int", "double"],
                    minimum: 1,
                    maximum: 5,
                    description: "Note de 1 à 5 étoiles - obligatoire"
                },
                commentaire: {
                    bsonType: "string",
                    minLength: 10,
                    maxLength: 1000,
                    description: "Commentaire du client (10-1000 caractères) - obligatoire"
                },
                statut_validation: {
                    bsonType: "string",
                    enum: ["EN_ATTENTE", "VALIDE", "REFUSE"],
                    description: "Statut de modération - obligatoire"
                },
                date_avis: {
                    bsonType: "date",
                    description: "Date de création de l'avis - obligatoire"
                },
                id_utilisateur: {
                    bsonType: "int",
                    description: "ID de l'utilisateur (référence MySQL) - obligatoire"
                },
                id_commande: {
                    bsonType: "int",
                    description: "ID de la commande (référence MySQL) - obligatoire"
                },
                id_menu: {
                    bsonType: "int",
                    description: "ID du menu (référence MySQL) - obligatoire"
                },
                modere_par: {
                    bsonType: ["int", "null"],
                    description: "ID de l'employé modérateur (référence MySQL)"
                },
                date_validation: {
                    bsonType: ["date", "null"],
                    description: "Date de validation/refus de l'avis"
                },
                mysql_synced: {
                    bsonType: "bool",
                    description: "Indique si l'avis est synchronisé avec MySQL (fallback)"
                },
                mysql_id: {
                    bsonType: ["int", "null"],
                    description: "ID dans la table AVIS_FALLBACK de MySQL"
                }
            }
        }
    },
    validationLevel: "strict",
    validationAction: "error"
});

print("✓ Collection 'avis' créée avec validation de schéma");

// Index pour performances
db.avis.createIndex({ "statut_validation": 1 });
db.avis.createIndex({ "id_menu": 1 });
db.avis.createIndex({ "date_avis": -1 });
db.avis.createIndex({ "note": 1 });
db.avis.createIndex({ "id_utilisateur": 1 });
db.avis.createIndex({ "id_commande": 1 });
// Index composé pour requêtes fréquentes (avis validés par menu)
db.avis.createIndex({ "statut_validation": 1, "id_menu": 1, "date_avis": -1 });

print("✓ Index créés sur la collection 'avis'");

// ============================================================
// COLLECTION : statistiques_commandes
// Stockage des statistiques pour l'espace administrateur
// ============================================================

db.createCollection("statistiques_commandes", {
    validator: {
        $jsonSchema: {
            bsonType: "object",
            required: ["id_menu", "titre_menu", "periode", "nb_commandes", "chiffre_affaires", "date_maj"],
            properties: {
                id_menu: {
                    bsonType: "int",
                    description: "ID du menu (référence MySQL) - obligatoire"
                },
                titre_menu: {
                    bsonType: "string",
                    description: "Titre du menu - obligatoire"
                },
                periode: {
                    bsonType: "object",
                    required: ["annee", "mois"],
                    properties: {
                        annee: {
                            bsonType: "int",
                            minimum: 2024,
                            description: "Année de la période"
                        },
                        mois: {
                            bsonType: "int",
                            minimum: 1,
                            maximum: 12,
                            description: "Mois de la période (1-12)"
                        }
                    },
                    description: "Période de référence - obligatoire"
                },
                nb_commandes: {
                    bsonType: "int",
                    minimum: 0,
                    description: "Nombre de commandes pour cette période - obligatoire"
                },
                chiffre_affaires: {
                    bsonType: "double",
                    minimum: 0,
                    description: "CA total pour cette période - obligatoire"
                },
                nb_personnes_total: {
                    bsonType: "int",
                    minimum: 0,
                    description: "Nombre total de personnes servies"
                },
                panier_moyen: {
                    bsonType: "double",
                    minimum: 0,
                    description: "Panier moyen (CA / nb_commandes)"
                },
                date_maj: {
                    bsonType: "date",
                    description: "Date de dernière mise à jour - obligatoire"
                },
                details_statuts: {
                    bsonType: "object",
                    description: "Répartition par statut de commande",
                    properties: {
                        EN_ATTENTE: { bsonType: "int", minimum: 0 },
                        ACCEPTE: { bsonType: "int", minimum: 0 },
                        EN_PREPARATION: { bsonType: "int", minimum: 0 },
                        EN_LIVRAISON: { bsonType: "int", minimum: 0 },
                        LIVRE: { bsonType: "int", minimum: 0 },
                        EN_ATTENTE_RETOUR: { bsonType: "int", minimum: 0 },
                        TERMINEE: { bsonType: "int", minimum: 0 },
                        ANNULEE: { bsonType: "int", minimum: 0 }
                    }
                }
            }
        }
    },
    validationLevel: "strict",
    validationAction: "error"
});

print("✓ Collection 'statistiques_commandes' créée avec validation de schéma");

// Index pour performances
db.statistiques_commandes.createIndex({ "id_menu": 1 });
db.statistiques_commandes.createIndex({ "periode.annee": 1, "periode.mois": 1 });
// Index composé pour requêtes par menu et période
db.statistiques_commandes.createIndex({ "id_menu": 1, "periode.annee": -1, "periode.mois": -1 });
// Index unique pour éviter les doublons (un menu par période)
db.statistiques_commandes.createIndex(
    { "id_menu": 1, "periode.annee": 1, "periode.mois": 1 },
    { unique: true }
);

print("✓ Index créés sur la collection 'statistiques_commandes'");

// ============================================================
// INSERTION DE DONNÉES DE TEST
// ============================================================

print("\n=== Insertion des données de test ===\n");

// --- AVIS CLIENTS ---

print("Insertion des avis clients...");

db.avis.insertMany([
    {
        note: NumberInt(5),
        commentaire: "Service impeccable et plats savoureux, une vraie réussite !",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-10-06T10:30:00Z"),
        id_utilisateur: NumberInt(7),  // Claire Moreau
        id_commande: NumberInt(5),     // Commande Menu Vegan TERMINEE
        id_menu: NumberInt(6),          // Menu Vegan Créatif
        modere_par: NumberInt(2),       // Julie (Employée)
        date_validation: new Date("2024-10-06T14:00:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(1)
    },
    {
        note: NumberInt(5),
        commentaire: "Fraîcheur remarquable, livraison ponctuelle, convives ravis. Merci beaucoup !",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-08-16T09:15:00Z"),
        id_utilisateur: NumberInt(3),  // Marie Dupont
        id_commande: NumberInt(6),     // Commande Menu Estival TERMINEE (Arcachon)
        id_menu: NumberInt(5),          // Menu Estival Léger
        modere_par: NumberInt(2),       // Julie (Employée)
        date_validation: new Date("2024-08-16T15:30:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(2)
    },
    {
        note: NumberInt(4),
        commentaire: "Agneau fondant à souhait, un vrai régal pour tous.",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-09-10T16:20:00Z"),
        id_utilisateur: NumberInt(6),  // Thomas Lefebvre
        id_commande: NumberInt(4),     // Commande Menu Pâques LIVRE
        id_menu: NumberInt(2),          // Menu de Pâques Gourmand
        modere_par: NumberInt(2),       // Julie (Employée)
        date_validation: new Date("2024-09-11T09:00:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(3)
    },
    {
        note: NumberInt(5),
        commentaire: "Noël inoubliable grâce au chapon, toute la famille enchantée.",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-11-22T11:00:00Z"),
        id_utilisateur: NumberInt(8),  // Lucas Dubois
        id_commande: NumberInt(8),     // Commande Menu Noël TERMINEE
        id_menu: NumberInt(1),          // Menu de Noël Traditionnel
        modere_par: NumberInt(2),       // Julie (Employée)
        date_validation: new Date("2024-11-22T15:00:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(4)
    },
    {
        note: NumberInt(5),
        commentaire: "Risotto crémeux parfait, mes invités en redemandent encore !",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-07-03T09:30:00Z"),
        id_utilisateur: NumberInt(9),  // Emma Petit
        id_commande: NumberInt(9),     // Commande Menu Végétarien TERMINEE
        id_menu: NumberInt(3),          // Menu Végétarien Raffiné
        modere_par: NumberInt(2),       // Julie (Employée)
        date_validation: new Date("2024-07-03T14:00:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(5)
    },
    {
        note: NumberInt(4),
        commentaire: "Magret cuit à la perfection, accompagnements délicieux et généreux.",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-08-27T16:00:00Z"),
        id_utilisateur: NumberInt(10), // Antoine Garcia
        id_commande: NumberInt(10),    // Commande Menu Classique TERMINEE
        id_menu: NumberInt(4),          // Menu Classique 4 Saisons
        modere_par: NumberInt(2),       // Julie (Employée)
        date_validation: new Date("2024-08-28T09:00:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(6)
    },
    {
        note: NumberInt(5),
        commentaire: "Tarte tatin exceptionnelle, un dessert digne d'un grand chef.",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-04-12T10:15:00Z"),
        id_utilisateur: NumberInt(4),  // Pierre Martin
        id_commande: NumberInt(11),    // Commande Menu Pâques TERMINEE
        id_menu: NumberInt(2),          // Menu de Pâques Gourmand
        modere_par: NumberInt(2),       // Julie (Employée)
        date_validation: new Date("2024-04-12T14:30:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(7)
    },
    {
        note: NumberInt(4),
        commentaire: "Repas estival léger et raffiné, idéal pour notre garden-party.",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-06-22T14:45:00Z"),
        id_utilisateur: NumberInt(11), // Camille Roux
        id_commande: NumberInt(12),    // Commande Menu Estival TERMINEE
        id_menu: NumberInt(5),          // Menu Estival Léger
        modere_par: NumberInt(2),       // Julie (Employée)
        date_validation: new Date("2024-06-23T09:00:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(8)
    },
    {
        note: NumberInt(5),
        commentaire: "Cuisine vegan créative et gourmande, même les sceptiques approuvent !",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-05-30T17:00:00Z"),
        id_utilisateur: NumberInt(5),  // Sophie Bernard
        id_commande: NumberInt(13),    // Commande Menu Vegan TERMINEE
        id_menu: NumberInt(6),          // Menu Vegan Créatif
        modere_par: NumberInt(2),       // Julie (Employée)
        date_validation: new Date("2024-05-31T10:00:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(9)
    },
    {
        note: NumberInt(5),
        commentaire: "Foie gras sublime et bûche divine, un Noël parfait.",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-12-22T11:30:00Z"),
        id_utilisateur: NumberInt(12), // Nicolas Laurent
        id_commande: NumberInt(14),    // Commande Menu Noël TERMINEE
        id_menu: NumberInt(1),          // Menu de Noël Traditionnel
        modere_par: NumberInt(2),       // Julie (Employée)
        date_validation: new Date("2024-12-22T16:00:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(10)
    },
    {
        note: NumberInt(5),
        commentaire: "Présentation soignée, portions généreuses et saveurs authentiques. Bravo !",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-10-14T10:00:00Z"),
        id_utilisateur: NumberInt(13), // Isabelle Girard
        id_commande: NumberInt(15),    // Commande Menu Végétarien TERMINEE
        id_menu: NumberInt(3),          // Menu Végétarien Raffiné
        modere_par: NumberInt(2),       // Julie (Employée)
        date_validation: new Date("2024-10-14T15:00:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(11)
    },
    {
        note: NumberInt(5),
        commentaire: "Qualité constante et équipe réactive, je recommande sans hésiter.",
        statut_validation: "EN_ATTENTE",
        date_avis: new Date("2025-01-05T11:00:00Z"),
        id_utilisateur: NumberInt(3),  // Marie Dupont
        id_commande: NumberInt(6),     // Commande Menu Estival TERMINEE
        id_menu: NumberInt(5),          // Menu Estival Léger
        modere_par: null,
        date_validation: null,
        mysql_synced: true,
        mysql_id: NumberInt(12)
    }
]);

print("✓ 12 avis insérés (11 validés, 1 en attente)");

// --- STATISTIQUES COMMANDES ---

print("Insertion des statistiques de commandes...");

db.statistiques_commandes.insertMany([
    // Menu de Noël - Novembre 2024
    {
        id_menu: NumberInt(1),
        titre_menu: "Menu de Noël Traditionnel",
        periode: { annee: NumberInt(2024), mois: NumberInt(11) },
        nb_commandes: NumberInt(3),
        chiffre_affaires: 460.00,
        nb_personnes_total: NumberInt(24),
        panier_moyen: 153.33,
        date_maj: new Date("2024-11-30T23:59:00Z"),
        details_statuts: {
            EN_ATTENTE: NumberInt(1),
            ACCEPTE: NumberInt(0),
            EN_PREPARATION: NumberInt(0),
            EN_LIVRAISON: NumberInt(0),
            LIVRE: NumberInt(1),
            EN_ATTENTE_RETOUR: NumberInt(1),
            TERMINEE: NumberInt(0),
            ANNULEE: NumberInt(0)
        }
    },
    // Menu de Noël - Décembre 2024
    {
        id_menu: NumberInt(1),
        titre_menu: "Menu de Noël Traditionnel",
        periode: { annee: NumberInt(2024), mois: NumberInt(12) },
        nb_commandes: NumberInt(1),
        chiffre_affaires: 155.00,
        nb_personnes_total: NumberInt(6),
        panier_moyen: 155.00,
        date_maj: new Date("2024-12-10T23:59:00Z"),
        details_statuts: {
            EN_ATTENTE: NumberInt(1),
            ACCEPTE: NumberInt(0),
            EN_PREPARATION: NumberInt(0),
            EN_LIVRAISON: NumberInt(0),
            LIVRE: NumberInt(0),
            EN_ATTENTE_RETOUR: NumberInt(0),
            TERMINEE: NumberInt(0),
            ANNULEE: NumberInt(0)
        }
    },
    // Menu de Pâques - Octobre 2024
    {
        id_menu: NumberInt(2),
        titre_menu: "Menu de Pâques Gourmand",
        periode: { annee: NumberInt(2024), mois: NumberInt(10) },
        nb_commandes: NumberInt(1),
        chiffre_affaires: 245.00,
        nb_personnes_total: NumberInt(8),
        panier_moyen: 245.00,
        date_maj: new Date("2024-10-31T23:59:00Z"),
        details_statuts: {
            EN_ATTENTE: NumberInt(0),
            ACCEPTE: NumberInt(0),
            EN_PREPARATION: NumberInt(0),
            EN_LIVRAISON: NumberInt(0),
            LIVRE: NumberInt(1),
            EN_ATTENTE_RETOUR: NumberInt(0),
            TERMINEE: NumberInt(0),
            ANNULEE: NumberInt(0)
        }
    },
    // Menu Végétarien - Novembre 2024
    {
        id_menu: NumberInt(3),
        titre_menu: "Menu Végétarien Raffiné",
        periode: { annee: NumberInt(2024), mois: NumberInt(11) },
        nb_commandes: NumberInt(1),
        chiffre_affaires: 151.75,
        nb_personnes_total: NumberInt(6),
        panier_moyen: 151.75,
        date_maj: new Date("2024-11-30T23:59:00Z"),
        details_statuts: {
            EN_ATTENTE: NumberInt(0),
            ACCEPTE: NumberInt(0),
            EN_PREPARATION: NumberInt(1),
            EN_LIVRAISON: NumberInt(0),
            LIVRE: NumberInt(0),
            EN_ATTENTE_RETOUR: NumberInt(0),
            TERMINEE: NumberInt(0),
            ANNULEE: NumberInt(0)
        }
    },
    // Menu Classique - Novembre 2024
    {
        id_menu: NumberInt(4),
        titre_menu: "Menu Classique 4 Saisons",
        periode: { annee: NumberInt(2024), mois: NumberInt(11) },
        nb_commandes: NumberInt(1),
        chiffre_affaires: 225.00,
        nb_personnes_total: NumberInt(8),
        panier_moyen: 225.00,
        date_maj: new Date("2024-11-30T23:59:00Z"),
        details_statuts: {
            EN_ATTENTE: NumberInt(0),
            ACCEPTE: NumberInt(1),
            EN_PREPARATION: NumberInt(0),
            EN_LIVRAISON: NumberInt(0),
            LIVRE: NumberInt(0),
            EN_ATTENTE_RETOUR: NumberInt(0),
            TERMINEE: NumberInt(0),
            ANNULEE: NumberInt(0)
        }
    },
    // Menu Estival - Août 2024
    {
        id_menu: NumberInt(5),
        titre_menu: "Menu Estival Léger",
        periode: { annee: NumberInt(2024), mois: NumberInt(8) },
        nb_commandes: NumberInt(1),
        chiffre_affaires: 251.70,
        nb_personnes_total: NumberInt(10),
        panier_moyen: 251.70,
        date_maj: new Date("2024-08-31T23:59:00Z"),
        details_statuts: {
            EN_ATTENTE: NumberInt(0),
            ACCEPTE: NumberInt(0),
            EN_PREPARATION: NumberInt(0),
            EN_LIVRAISON: NumberInt(0),
            LIVRE: NumberInt(0),
            EN_ATTENTE_RETOUR: NumberInt(0),
            TERMINEE: NumberInt(1),
            ANNULEE: NumberInt(0)
        }
    },
    // Menu Vegan - Septembre 2024
    {
        id_menu: NumberInt(6),
        titre_menu: "Menu Vegan Créatif",
        periode: { annee: NumberInt(2024), mois: NumberInt(9) },
        nb_commandes: NumberInt(1),
        chiffre_affaires: 110.00,
        nb_personnes_total: NumberInt(4),
        panier_moyen: 110.00,
        date_maj: new Date("2024-09-30T23:59:00Z"),
        details_statuts: {
            EN_ATTENTE: NumberInt(0),
            ACCEPTE: NumberInt(0),
            EN_PREPARATION: NumberInt(0),
            EN_LIVRAISON: NumberInt(0),
            LIVRE: NumberInt(0),
            EN_ATTENTE_RETOUR: NumberInt(0),
            TERMINEE: NumberInt(1),
            ANNULEE: NumberInt(0)
        }
    }
]);

print("✓ 7 enregistrements de statistiques insérés");

// ============================================================
// VUES AGRÉGÉES (PIPELINES)
// ============================================================

print("\n=== Création des vues agrégées ===\n");

// Vue : Avis validés pour la page d'accueil
db.createView(
    "avis_page_accueil",
    "avis",
    [
        {
            $match: {
                statut_validation: "VALIDE"
            }
        },
        {
            $sort: {
                date_avis: -1
            }
        },
        {
            $limit: 10
        },
        {
            $project: {
                _id: 1,
                note: 1,
                commentaire: 1,
                date_avis: 1,
                id_utilisateur: 1,
                id_menu: 1
            }
        }
    ]
);

print("✓ Vue 'avis_page_accueil' créée");

// Vue : Statistiques globales par menu
db.createView(
    "statistiques_menus_globales",
    "statistiques_commandes",
    [
        {
            $group: {
                _id: "$id_menu",
                titre_menu: { $first: "$titre_menu" },
                nb_commandes_total: { $sum: "$nb_commandes" },
                ca_total: { $sum: "$chiffre_affaires" },
                nb_personnes_total: { $sum: "$nb_personnes_total" },
                panier_moyen_global: { $avg: "$panier_moyen" }
            }
        },
        {
            $sort: {
                ca_total: -1
            }
        }
    ]
);

print("✓ Vue 'statistiques_menus_globales' créée");

// ============================================================
// RÉSUMÉ
// ============================================================

print("\n=== RÉSUMÉ DE LA BASE DE DONNÉES ===\n");

print("Collections créées :");
print("  - avis : " + db.avis.countDocuments({}) + " documents");
print("  - statistiques_commandes : " + db.statistiques_commandes.countDocuments({}) + " documents");

print("\nVues créées :");
print("  - avis_page_accueil");
print("  - statistiques_menus_globales");

print("\nIndex créés :");
print("  - avis : " + db.avis.getIndexes().length + " index");
print("  - statistiques_commandes : " + db.statistiques_commandes.getIndexes().length + " index");

// ============================================================
// EXEMPLES DE REQUÊTES UTILES
// ============================================================

print("\n=== EXEMPLES DE REQUÊTES ===\n");

print("// 1. Récupérer les avis validés pour un menu");
print('db.avis.find({ statut_validation: "VALIDE", id_menu: 1 }).sort({ date_avis: -1 })\n');

print("// 2. Calculer la note moyenne d'un menu");
print('db.avis.aggregate([');
print('  { $match: { statut_validation: "VALIDE", id_menu: 1 } },');
print('  { $group: { _id: "$id_menu", note_moyenne: { $avg: "$note" } } }');
print('])\n');

print("// 3. Récupérer les statistiques d'un menu sur une période");
print('db.statistiques_commandes.find({');
print('  id_menu: 1,');
print('  "periode.annee": 2024,');
print('  "periode.mois": { $gte: 11, $lte: 12 }');
print('})\n');

print("// 4. Comparer le CA de tous les menus");
print('db.statistiques_menus_globales.find().sort({ ca_total: -1 })\n');

print("// 5. Avis en attente de modération");
print('db.avis.find({ statut_validation: "EN_ATTENTE" }).sort({ date_avis: -1 })\n');

print("\n=== CONFIGURATION TERMINÉE ===");
print("Base de données 'vite_gourmand_test' prête à l'emploi !");
