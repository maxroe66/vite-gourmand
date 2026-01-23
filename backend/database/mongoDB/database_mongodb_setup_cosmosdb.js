// ============================================================
// Script de configuration MongoDB pour Azure Cosmos DB (PRODUCTION)
// Version: 2.0 - Compatible Cosmos DB (sans validateurs complexes)
// Date: 23 janvier 2026
// ============================================================
// ⚠️ ATTENTION: Ce script initialise UNIQUEMENT la structure.
// Cosmos DB ne supporte pas tous les validateurs MongoDB.
// ============================================================

// Connexion à la base de données de production
var dbName = "vite_gourmand_prod";

print("=== Initialisation MongoDB Azure Cosmos DB (PRODUCTION) ===");
print("Base de données: " + dbName);

db = db.getSiblingDB(dbName);

// ============================================================
// COLLECTION : avis
// Stockage des avis clients avec modération
// ============================================================

// Cosmos DB ne supporte pas bien les validateurs complexes avec bsonType arrays
// On crée la collection sans validateur
db.createCollection("avis");

print("✓ Collection 'avis' créée");

// Index pour performances
try {
    db.avis.createIndex({ "statut_validation": 1 });
    print("✓ Index sur 'statut_validation' créé");
} catch (e) {
    print("⚠️  Index 'statut_validation': " + e.message);
}

try {
    db.avis.createIndex({ "id_menu": 1 });
    print("✓ Index sur 'id_menu' créé");
} catch (e) {
    print("⚠️  Index 'id_menu': " + e.message);
}

try {
    db.avis.createIndex({ "date_avis": -1 });
    print("✓ Index sur 'date_avis' créé");
} catch (e) {
    print("⚠️  Index 'date_avis': " + e.message);
}

try {
    db.avis.createIndex({ "note": 1 });
    print("✓ Index sur 'note' créé");
} catch (e) {
    print("⚠️  Index 'note': " + e.message);
}

try {
    db.avis.createIndex({ "id_utilisateur": 1 });
    print("✓ Index sur 'id_utilisateur' créé");
} catch (e) {
    print("⚠️  Index 'id_utilisateur': " + e.message);
}

try {
    db.avis.createIndex({ "id_commande": 1 });
    print("✓ Index sur 'id_commande' créé");
} catch (e) {
    print("⚠️  Index 'id_commande': " + e.message);
}

// Index composé pour requêtes fréquentes (avis validés par menu)
try {
    db.avis.createIndex({ "statut_validation": 1, "id_menu": 1, "date_avis": -1 });
    print("✓ Index composé créé");
} catch (e) {
    print("⚠️  Index composé: " + e.message);
}

// ============================================================
// COLLECTION : statistiques_commandes
// Données analytiques sur les commandes (dénormalisées)
// ============================================================

db.createCollection("statistiques_commandes");

print("✓ Collection 'statistiques_commandes' créée");

// Index pour analyses
try {
    db.statistiques_commandes.createIndex({ "commandeId": 1 }, { unique: true });
    print("✓ Index unique sur 'commandeId' créé");
} catch (e) {
    print("⚠️  Index 'commandeId': " + e.message);
}

try {
    db.statistiques_commandes.createIndex({ "menuId": 1 });
    print("✓ Index sur 'menuId' créé");
} catch (e) {
    print("⚠️  Index 'menuId': " + e.message);
}

try {
    db.statistiques_commandes.createIndex({ "status": 1 });
    print("✓ Index sur 'status' créé");
} catch (e) {
    print("⚠️  Index 'status': " + e.message);
}

try {
    db.statistiques_commandes.createIndex({ "dateCommande": -1 });
    print("✓ Index sur 'dateCommande' créé");
} catch (e) {
    print("⚠️  Index 'dateCommande': " + e.message);
}

try {
    db.statistiques_commandes.createIndex({ "ville": 1 });
    print("✓ Index sur 'ville' créé");
} catch (e) {
    print("⚠️  Index 'ville': " + e.message);
}

// Index composé pour statistiques par menu et période
try {
    db.statistiques_commandes.createIndex({ "menuId": 1, "dateCommande": -1 });
    print("✓ Index composé 'menuId + dateCommande' créé");
} catch (e) {
    print("⚠️  Index composé: " + e.message);
}

// ============================================================
// VÉRIFICATION
// ============================================================

print("\n=== Vérification des collections ===");
var collections = db.getCollectionNames();
print("Collections créées: " + collections.join(", "));

print("\n=== Configuration terminée ===");
print("✅ Base de données MongoDB Azure Cosmos DB initialisée avec succès!");
print("📊 Collections: " + collections.length);
print("⚠️  Note: Les validateurs de schéma ne sont pas appliqués (limitation Cosmos DB)");
print("⚠️  La validation doit être faite côté application PHP");
