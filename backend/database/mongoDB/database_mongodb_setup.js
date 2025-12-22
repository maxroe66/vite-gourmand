// ============================================================
// Script de configuration MongoDB pour "Vite & Gourmand"
// Version: 1.0
// Date: 11 décembre 2025
// MongoDB: 4.4+
// ============================================================

// Connexion à la base de données
// Utilise la variable d'environnement MONGO_INITDB_DATABASE ou "vite_et_gourmand" par défaut
var dbName = typeof process !== 'undefined' && process.env.MONGO_INITDB_DATABASE ? process.env.MONGO_INITDB_DATABASE : "vite_et_gourmand";
print("=== Environment check ===");
print("process.env.MONGO_INITDB_DATABASE: " + (typeof process !== 'undefined' ? process.env.MONGO_INITDB_DATABASE : 'undefined'));
print("Selected dbName: " + dbName);
db = db.getSiblingDB(dbName);

print("=== Base de données utilisée: " + dbName + " ===");

print("=== Initialisation de la base de données MongoDB ===");

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
                    bsonType: ["int", "double"],
                    description: "ID de l'utilisateur (référence MySQL) - obligatoire"
                },
                id_commande: {
                    bsonType: ["int", "double"],
                    description: "ID de la commande (référence MySQL) - obligatoire"
                },
                id_menu: {
                    bsonType: ["int", "double"],
                    description: "ID du menu (référence MySQL) - obligatoire"
                },
                modere_par: {
                    bsonType: ["int", "double", "null"],
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
                    bsonType: ["int", "double", "null"],
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
        commentaire: "Prestation exceptionnelle ! Les plats étaient délicieux et la présentation soignée. Je recommande vivement pour vos événements.",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-10-06T10:30:00Z"),
        id_utilisateur: NumberInt(7),
        id_commande: NumberInt(5),
        id_menu: NumberInt(6),
        modere_par: NumberInt(2),
        date_validation: new Date("2024-10-06T14:00:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(1)
    },
    {
        note: NumberInt(5),
        commentaire: "Menu estival parfait pour notre réception. Produits frais, saveurs au rendez-vous. La livraison à Arcachon s'est très bien passée.",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-08-16T09:15:00Z"),
        id_utilisateur: NumberInt(3),
        id_commande: NumberInt(6),
        id_menu: NumberInt(5),
        modere_par: NumberInt(2),
        date_validation: new Date("2024-08-16T15:30:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(2)
    },
    {
        note: NumberInt(4),
        commentaire: "Très bon rapport qualité-prix. Quelques petits détails à améliorer sur la présentation mais les saveurs étaient au top !",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-09-10T16:20:00Z"),
        id_utilisateur: NumberInt(6),
        id_commande: NumberInt(4),
        id_menu: NumberInt(2),
        modere_par: NumberInt(2),
        date_validation: new Date("2024-09-11T09:00:00Z"),
        mysql_synced: true,
        mysql_id: NumberInt(3)
    },
    {
        note: NumberInt(5),
        commentaire: "Commande pour Noël en cours mais contact très professionnel et réactif. Hâte de goûter !",
        statut_validation: "EN_ATTENTE",
        date_avis: new Date("2024-12-06T11:00:00Z"),
        id_utilisateur: NumberInt(3),
        id_commande: NumberInt(1),
        id_menu: NumberInt(1),
        modere_par: null,
        date_validation: null,
        mysql_synced: true,
        mysql_id: NumberInt(4)
    },
    {
        note: NumberInt(5),
        commentaire: "Équipe au top ! Le menu de Noël était succulent. Les convives ont adoré le chapon farci aux marrons.",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-12-02T20:30:00Z"),
        id_utilisateur: NumberInt(4),
        id_commande: NumberInt(7),
        id_menu: NumberInt(1),
        modere_par: NumberInt(2),
        date_validation: new Date("2024-12-03T09:00:00Z"),
        mysql_synced: false,
        mysql_id: null
    },
    {
        note: NumberInt(4),
        commentaire: "Menu végétarien très créatif et savoureux. Seul bémol : le délai de livraison un peu long.",
        statut_validation: "VALIDE",
        date_avis: new Date("2024-11-20T14:15:00Z"),
        id_utilisateur: NumberInt(5),
        id_commande: NumberInt(3),
        id_menu: NumberInt(3),
        modere_par: NumberInt(2),
        date_validation: new Date("2024-11-21T10:00:00Z"),
        mysql_synced: false,
        mysql_id: null
    },
    {
        note: NumberInt(2),
        commentaire: "Déçu par la qualité des produits. Le poisson n'était pas assez frais à mon goût.",
        statut_validation: "REFUSE",
        date_avis: new Date("2024-10-25T18:45:00Z"),
        id_utilisateur: NumberInt(4),
        id_commande: NumberInt(2),
        id_menu: NumberInt(4),
        modere_par: NumberInt(2),
        date_validation: new Date("2024-10-26T09:30:00Z"),
        mysql_synced: false,
        mysql_id: null
    }
]);

print("✓ 7 avis insérés (4 validés, 1 en attente, 2 refusés)");

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
print("  - avis : " + db.avis.countDocuments() + " documents");
print("  - statistiques_commandes : " + db.statistiques_commandes.countDocuments() + " documents");

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
print("Base de données 'vite_et_gourmand' prête à l'emploi !");
