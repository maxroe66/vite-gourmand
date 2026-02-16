// ============================================================
// Script de configuration MongoDB pour Azure Cosmos DB (PRODUCTION)
// Version: 3.0 - Ultra minimal pour Cosmos DB
// Date: 23 janvier 2026
// ============================================================

var dbName = "vite_gourmand_prod";

print("=== Initialisation MongoDB Azure Cosmos DB (PRODUCTION) ===");
print("Base de données: " + dbName);

db = db.getSiblingDB(dbName);

// ============================================================
// COLLECTION : avis
// ============================================================

try {
    db.createCollection("avis");
    print("✓ Collection 'avis' créée");
} catch (e) {
    if (e.codeName === "NamespaceExists") {
        print("⚠️  Collection 'avis' existe déjà");
    } else {
        print("❌ Erreur création 'avis': " + e.message);
        throw e;
    }
}

// ============================================================
// COLLECTION : statistiques_commandes
// ============================================================

try {
    db.createCollection("statistiques_commandes");
    print("✓ Collection 'statistiques_commandes' créée");
} catch (e) {
    if (e.codeName === "NamespaceExists") {
        print("⚠️  Collection 'statistiques_commandes' existe déjà");
    } else {
        print("❌ Erreur création 'statistiques_commandes': " + e.message);
        throw e;
    }
}

// ============================================================
// INDEX BASIQUES (ajout progressif)
// ============================================================

print("\n=== Création des index ===");

// Index pour avis
try {
    db.avis.createIndex({ "statut_validation": 1 });
    print("✓ Index avis.statut_validation");
} catch (e) {
    print("⚠️  " + e.message);
}

try {
    db.avis.createIndex({ "id_menu": 1 });
    print("✓ Index avis.id_menu");
} catch (e) {
    print("⚠️  " + e.message);
}

// Index pour statistiques
try {
    db.statistiques_commandes.createIndex({ "commandeId": 1 }, { unique: true });
    print("✓ Index UNIQUE statistiques_commandes.commandeId");
} catch (e) {
    print("⚠️  " + e.message);
}

try {
    db.statistiques_commandes.createIndex({ "menuId": 1 });
    print("✓ Index statistiques_commandes.menuId");
} catch (e) {
    print("⚠️  " + e.message);
}

try {
    db.statistiques_commandes.createIndex({ "dateCommande": 1 });
    print("✓ Index statistiques_commandes.dateCommande");
} catch (e) {
    print("⚠️  " + e.message);
}

try {
    db.statistiques_commandes.createIndex({ "ville": 1 });
    print("✓ Index statistiques_commandes.ville");
} catch (e) {
    print("⚠️  " + e.message);
}

// ============================================================
// VÉRIFICATION
// ============================================================

print("\n=== Vérification ===");
var collections = db.getCollectionNames();
print("Collections: " + collections.join(", "));

// ============================================================
// DONNÉES INITIALES : AVIS (11 validés + 1 en attente)
// Nécessaires pour le carousel d'avis sur la page d'accueil
// ============================================================

print("\n=== Insertion des avis initiaux ===");

// Ne réinsérer que si la collection est vide (éviter les doublons)
var existingAvis = db.avis.countDocuments({});
if (existingAvis === 0) {
    db.avis.insertMany([
        {
            mysql_id: 1,
            note: 5,
            commentaire: "Service impeccable et plats savoureux, une vraie réussite !",
            statut_validation: "VALIDE",
            date_avis: new Date("2024-10-06T10:30:00Z"),
            id_utilisateur: 7,
            id_commande: 5,
            id_menu: 6,
            modere_par: 2,
            date_validation: new Date("2024-10-06T14:00:00Z"),
            mysql_synced: true
        },
        {
            mysql_id: 2,
            note: 5,
            commentaire: "Fraîcheur remarquable, livraison ponctuelle, convives ravis. Merci beaucoup !",
            statut_validation: "VALIDE",
            date_avis: new Date("2024-08-16T09:15:00Z"),
            id_utilisateur: 3,
            id_commande: 6,
            id_menu: 5,
            modere_par: 2,
            date_validation: new Date("2024-08-16T15:30:00Z"),
            mysql_synced: true
        },
        {
            mysql_id: 3,
            note: 4,
            commentaire: "Agneau fondant à souhait, un vrai régal pour tous.",
            statut_validation: "VALIDE",
            date_avis: new Date("2024-09-10T16:20:00Z"),
            id_utilisateur: 6,
            id_commande: 4,
            id_menu: 2,
            modere_par: 2,
            date_validation: new Date("2024-09-11T09:00:00Z"),
            mysql_synced: true
        },
        {
            mysql_id: 4,
            note: 5,
            commentaire: "Noël inoubliable grâce au chapon, toute la famille enchantée.",
            statut_validation: "VALIDE",
            date_avis: new Date("2024-11-22T11:00:00Z"),
            id_utilisateur: 8,
            id_commande: 8,
            id_menu: 1,
            modere_par: 2,
            date_validation: new Date("2024-11-22T15:00:00Z"),
            mysql_synced: true
        },
        {
            mysql_id: 5,
            note: 5,
            commentaire: "Risotto crémeux parfait, mes invités en redemandent encore !",
            statut_validation: "VALIDE",
            date_avis: new Date("2024-07-03T09:30:00Z"),
            id_utilisateur: 9,
            id_commande: 9,
            id_menu: 3,
            modere_par: 2,
            date_validation: new Date("2024-07-03T14:00:00Z"),
            mysql_synced: true
        },
        {
            mysql_id: 6,
            note: 4,
            commentaire: "Magret cuit à la perfection, accompagnements délicieux et généreux.",
            statut_validation: "VALIDE",
            date_avis: new Date("2024-08-27T16:00:00Z"),
            id_utilisateur: 10,
            id_commande: 10,
            id_menu: 4,
            modere_par: 2,
            date_validation: new Date("2024-08-28T09:00:00Z"),
            mysql_synced: true
        },
        {
            mysql_id: 7,
            note: 5,
            commentaire: "Tarte tatin exceptionnelle, un dessert digne d'un grand chef.",
            statut_validation: "VALIDE",
            date_avis: new Date("2024-04-12T10:15:00Z"),
            id_utilisateur: 4,
            id_commande: 11,
            id_menu: 2,
            modere_par: 2,
            date_validation: new Date("2024-04-12T14:30:00Z"),
            mysql_synced: true
        },
        {
            mysql_id: 8,
            note: 4,
            commentaire: "Repas estival léger et raffiné, idéal pour notre garden-party.",
            statut_validation: "VALIDE",
            date_avis: new Date("2024-06-22T14:45:00Z"),
            id_utilisateur: 11,
            id_commande: 12,
            id_menu: 5,
            modere_par: 2,
            date_validation: new Date("2024-06-23T09:00:00Z"),
            mysql_synced: true
        },
        {
            mysql_id: 9,
            note: 5,
            commentaire: "Cuisine vegan créative et gourmande, même les sceptiques approuvent !",
            statut_validation: "VALIDE",
            date_avis: new Date("2024-05-30T17:00:00Z"),
            id_utilisateur: 5,
            id_commande: 13,
            id_menu: 6,
            modere_par: 2,
            date_validation: new Date("2024-05-31T10:00:00Z"),
            mysql_synced: true
        },
        {
            mysql_id: 10,
            note: 5,
            commentaire: "Foie gras sublime et bûche divine, un Noël parfait.",
            statut_validation: "VALIDE",
            date_avis: new Date("2024-12-22T11:30:00Z"),
            id_utilisateur: 12,
            id_commande: 14,
            id_menu: 1,
            modere_par: 2,
            date_validation: new Date("2024-12-22T16:00:00Z"),
            mysql_synced: true
        },
        {
            mysql_id: 11,
            note: 5,
            commentaire: "Présentation soignée, portions généreuses et saveurs authentiques. Bravo !",
            statut_validation: "VALIDE",
            date_avis: new Date("2024-10-14T10:00:00Z"),
            id_utilisateur: 13,
            id_commande: 15,
            id_menu: 3,
            modere_par: 2,
            date_validation: new Date("2024-10-14T15:00:00Z"),
            mysql_synced: true
        },
        {
            mysql_id: 12,
            note: 5,
            commentaire: "Qualité constante et équipe réactive, je recommande sans hésiter.",
            statut_validation: "EN_ATTENTE",
            date_avis: new Date("2025-01-05T11:00:00Z"),
            id_utilisateur: 3,
            id_commande: 6,
            id_menu: 5,
            modere_par: null,
            date_validation: null,
            mysql_synced: true
        }
    ]);
    print("✓ 12 avis insérés (11 validés, 1 en attente)");
} else {
    print("⚠️  " + existingAvis + " avis déjà présents, insertion ignorée");
}

print("\n✅ Configuration terminée!");

