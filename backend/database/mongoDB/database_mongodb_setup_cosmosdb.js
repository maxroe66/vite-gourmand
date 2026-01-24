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
print("\n✅ Configuration terminée!");

