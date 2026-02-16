#!/usr/bin/env php
<?php

/**
 * Script de configuration du mot de passe administrateur initial.
 *
 * Ce script lit la variable d'environnement ADMIN_INITIAL_PASSWORD et met à jour
 * le hash du compte administrateur en base de données (Argon2ID).
 *
 * Usage :
 *   - Localhost : définir ADMIN_INITIAL_PASSWORD dans .env puis exécuter :
 *       php scripts/setup/setup-admin-password.php
 *
 *   - Azure CI/CD : ajouté automatiquement après le seed dans le workflow
 *       de déploiement (via GitHub Secrets ADMIN_INITIAL_PASSWORD)
 *
 * Sécurité :
 *   - Le mot de passe n'apparaît jamais dans le code versionné
 *   - Le hash est généré en Argon2ID (recommandé OWASP)
 *   - Le script vérifie la robustesse minimale du mot de passe
 */

declare(strict_types=1);

// --- Configuration : lecture des variables d'environnement ---

// Charger .env si disponible (localhost)
$root = realpath(__DIR__ . '/..');
if ($root && file_exists($root . '/.env')) {
    $lines = file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (getenv($key) === false) {
                putenv("$key=$value");
            }
        }
    }
}

// --- Récupération du mot de passe ---
$password = getenv('ADMIN_INITIAL_PASSWORD');

if (empty($password)) {
    echo "❌ Erreur : la variable d'environnement ADMIN_INITIAL_PASSWORD n'est pas définie.\n";
    echo "\n";
    echo "Utilisation :\n";
    echo "  Localhost : ajouter ADMIN_INITIAL_PASSWORD=VotreMotDePasse dans .env\n";
    echo "  CI/CD    : définir le secret GitHub ADMIN_INITIAL_PASSWORD\n";
    exit(1);
}

// --- Validation minimale du mot de passe ---
if (strlen($password) < 12) {
    echo "❌ Erreur : le mot de passe doit contenir au moins 12 caractères.\n";
    exit(1);
}

if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
    echo "❌ Erreur : le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.\n";
    exit(1);
}

// --- Connexion à la base de données ---
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: '';
$dbUser = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: '';
$dbPass = getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: '';

if (empty($dbName) || empty($dbUser)) {
    echo "❌ Erreur : les variables de connexion à la base de données ne sont pas définies.\n";
    echo "   Variables attendues : DB_HOST, DB_NAME, DB_USER, DB_PASS\n";
    echo "   (ou MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD)\n";
    exit(1);
}

$adminEmail = getenv('ADMIN_EMAIL') ?: 'jose@vite-gourmand.fr';

try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Support SSL pour Azure
    $sslCert = getenv('MYSQL_SSL_CA');
    if ($sslCert && file_exists($sslCert)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCert;
    }

    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    // --- Hash du mot de passe en Argon2ID ---
    $hash = password_hash($password, PASSWORD_ARGON2ID);

    // --- Mise à jour du compte admin ---
    $stmt = $pdo->prepare('UPDATE UTILISATEUR SET mot_de_passe = :hash WHERE email = :email AND role = :role');
    $stmt->execute([
        ':hash' => $hash,
        ':email' => $adminEmail,
        ':role' => 'ADMINISTRATEUR',
    ]);

    $rowCount = $stmt->rowCount();

    if ($rowCount > 0) {
        echo "✅ Mot de passe administrateur mis à jour avec succès (Argon2ID).\n";
        echo "   Email : $adminEmail\n";
        echo "   ⚠️  Conservez ce mot de passe en lieu sûr, il n'est stocké nulle part dans le code.\n";
    } else {
        echo "⚠️  Aucun compte administrateur trouvé avec l'email : $adminEmail\n";
        echo "   Vérifiez que le seed SQL a été exécuté avant ce script.\n";
        exit(1);
    }

} catch (PDOException $e) {
    echo "❌ Erreur de connexion à la base de données : " . $e->getMessage() . "\n";
    exit(1);
}
