// ============================================================
// Script de configuration MongoDB pour Azure Cosmos DB (PRODUCTION)
// Version: 1.0
// Date: 4 janvier 2026
// MongoDB API: 4.0
// ============================================================
// ⚠️ ATTENTION: Ce script initialise UNIQUEMENT la structure.
// Aucune donnée de test n'est insérée (production uniquement).
// ============================================================

// Connexion à la base de données de production
var dbName = "vite_gourmand_prod";

print("=== Initialisation MongoDB Azure (PRODUCTION) ===");
print("Base de données: " + dbName);

db = db.getSiblingDB(dbName);

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

print("\n=== RÉSUMÉ DE LA BASE DE DONNÉES (PRODUCTION) ===\n");

print("Collections créées :");
print("  - avis : " + db.avis.countDocuments({}) + " documents (vide initialement)");
print("  - statistiques_commandes : " + db.statistiques_commandes.countDocuments({}) + " documents (vide initialement)");

print("\nVues créées :");
print("  - avis_page_accueil");
print("  - statistiques_menus_globales");

print("\nIndex créés :");
print("  - avis : " + db.avis.getIndexes().length + " index");
print("  - statistiques_commandes : " + db.statistiques_commandes.getIndexes().length + " index");

print("\n⚠️  ATTENTION: Collections vides (pas de données de test)");
print("Les données seront créées par les utilisateurs réels en production.");

print("\n=== CONFIGURATION AZURE TERMINÉE ===");
print("Base de données 'vite_gourmand_prod' prête pour la production !");
