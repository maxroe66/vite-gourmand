<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

/**
 * config.php (unique) :
 * - En DEV/PROD : charge <racine>/.env
 * - En TEST     : charge <racine>/.env.test (si APP_ENV=test ou ENV=test)
 *
 * IMPORTANT:
 * - Les variables injectées par l'OS/CI/script (getenv) doivent être prioritaires.
 * - Dotenv ne doit pas écraser les variables déjà définies.
 */

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    throw new RuntimeException('Project root not found (expected ../../ from backend/config)');
}

/**
 * Helper: lire env en priorité via getenv() (CI / export),
 * sinon via $_ENV (valeurs Dotenv).
 */
$env = static function (string $key, ?string $default = null): ?string {
    $v = getenv($key);
    if ($v !== false) {
        return $v;
    }
    return $_ENV[$key] ?? $default;
};

/**
 * Détecter l'environnement le plus tôt possible.
 * On regarde d'abord getenv() (CI), puis $_ENV (si déjà chargé).
 */
$detectedEnv = $env('APP_ENV') ?? $env('ENV');

/**
 * Choix du fichier dotenv :
 * - si test et .env.test existe -> .env.test
 * - sinon si .env existe -> .env
 */
$dotenvFile = null;
if ($detectedEnv === 'test' && file_exists($root . '/.env.test')) {
    $dotenvFile = '.env.test';
} elseif (file_exists($root . '/.env')) {
    $dotenvFile = '.env';
}

/**
 * Charger Dotenv seulement si on a trouvé un fichier.
 * Dotenv (createImmutable) n'écrase pas les variables déjà présentes dans l'environnement.
 */
if ($dotenvFile !== null) {
    Dotenv::createImmutable($root, $dotenvFile)->safeLoad();
}

/**
 * ENV / DEBUG
 * - Standard: APP_ENV, APP_DEBUG
 * - Compat: ENV, DEBUG
 */
$appEnv = $env('APP_ENV') ?? $env('ENV') ?? 'development';
$appDebugRaw = $env('APP_DEBUG') ?? $env('DEBUG') ?? 'false';

/**
 * MySQL
 * - Standard: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
 * - Compat: DB_PASS
 */
$dbHost = $env('DB_HOST', '127.0.0.1');
$dbPort = $env('DB_PORT', '3306');
$dbName = $env('DB_NAME', 'vite_gourmand');
$dbUser = $env('DB_USER', '');
$dbPass = $env('DB_PASSWORD') ?? $env('DB_PASS') ?? '';

/**
 * SSL MySQL (optionnel)
 * - DB_SSL=1/true/yes/on => active TLS
 * - DB_SSL_CA => chemin du CA (par défaut celui ajouté dans Dockerfile.azure)
 */
$dbSslRaw = $env('DB_SSL', '0') ?? '0';
$dbSslEnabled = in_array(strtolower((string)$dbSslRaw), ['1', 'true', 'yes', 'on'], true);
$dbSslCa = $env('DB_SSL_CA', '/etc/ssl/azure/DigiCertGlobalRootCA.crt.pem') ?: '/etc/ssl/azure/DigiCertGlobalRootCA.crt.pem';

// Options PDO de base pour garantir UTF-8MB4
$dbPdoOptions = [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

// Ajouter les options SSL si activées (Azure)
if ($dbSslEnabled) {
    $dbPdoOptions[\PDO::MYSQL_ATTR_SSL_CA] = $dbSslCa;
    // Pragmatique pour éviter les erreurs SSL sur Azure si la vérification stricte pose problème.
    // Le TLS est bien utilisé, mais on assouplit la vérification du certificat serveur.
    $dbPdoOptions[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

/**
 * MongoDB
 * - Standard: MONGO_DB + (MONGO_URI ou MONGO_HOST/MONGO_PORT)
 * - Détection automatique de Cosmos DB (port 10255)
 */
$mongoDb = $env('MONGO_DB', 'vite_gourmand');

$mongoUri = $env('MONGO_URI', '');
if ($mongoUri === '') {
    $mongoHost = $env('MONGO_HOST', '127.0.0.1');
    $mongoPort = $env('MONGO_PORT', '27017');
    $mongoUser = $env('MONGO_USERNAME') ?? $env('MONGO_USER');
    $mongoPass = $env('MONGO_PASSWORD') ?? $env('MONGO_PASS');

    if ($mongoUser && $mongoPass) {
        // Détection Azure Cosmos DB (port 10255 spécifique)
        $isCosmosDb = ($mongoPort === '10255');
        
        if ($isCosmosDb) {
            // Cosmos DB nécessite ssl=true et retrywrites=false
            $mongoUri = "mongodb://{$mongoUser}:{$mongoPass}@{$mongoHost}:{$mongoPort}/{$mongoDb}?ssl=true&retrywrites=false";
        } else {
            // MongoDB standard
            $mongoUri = "mongodb://{$mongoUser}:{$mongoPass}@{$mongoHost}:{$mongoPort}/{$mongoDb}?authSource=admin";
        }
    } else {
        $mongoUri = "mongodb://{$mongoHost}:{$mongoPort}/{$mongoDb}";
    }
}


/**
 * Mail Provider (Mailtrap en dev, SendGrid en prod)
 */
$mailProvider = 'mailtrap';
$mailApiKey = $env('MAILTRAP_API_KEY') ?? '';
$mailHost = $env('MAIL_HOST', '');
$mailUser = $env('MAIL_USERNAME') ?? $env('MAIL_USER') ?? '';
$mailPass = $env('MAIL_PASSWORD') ?? $env('MAIL_PASS') ?? '';
$mailFrom = $env('MAIL_FROM_ADDRESS') ?? $env('MAIL_FROM') ?? '';

if ($appEnv === 'production') {
    $mailProvider = 'sendgrid';
    $mailApiKey = $env('SENDGRID_API_KEY') ?? '';
    // Optionnel : override host/user/pass si besoin pour SendGrid
    $mailHost = 'smtp.sendgrid.net';
    $mailUser = 'apikey';
    $mailPass = $mailApiKey;
    $mailFrom = $env('MAIL_FROM_ADDRESS') ?? 'no-reply@vite-et-gourmand.me';
}

/**
 * JWT
 * - JWT_SECRET: secret for signing tokens
 */
$jwtSecret = $env('JWT_SECRET');
if (empty($jwtSecret) || $jwtSecret === '<placeholder>') {
    if ($appEnv === 'production') {
        throw new \RuntimeException('FATAL: JWT_SECRET is not defined in the environment.');
    }
    // For non-production environments, use a default insecure key.
    $jwtSecret = 'default-dev-secret-do-not-use-in-prod';
}

/**
 * CORS
 * - CORS_ALLOWED_ORIGINS : liste d'origines séparées par des virgules (prioritaire)
 * - FRONTEND_ORIGIN      : URL du frontend (fallback, origine unique)
 * En production, le wildcard '*' est interdit car l'app utilise les cookies (credentials).
 */
$frontendOrigin = $env('FRONTEND_ORIGIN', 'http://localhost:8000');
$corsOriginsRaw = $env('CORS_ALLOWED_ORIGINS', '');

$corsAllowedOrigins = !empty($corsOriginsRaw)
    ? array_map('trim', explode(',', $corsOriginsRaw))
    : [$frontendOrigin];

// Sécurité production : interdire wildcard et origines HTTP non sécurisées
if ($appEnv === 'production') {
    $corsAllowedOrigins = array_filter($corsAllowedOrigins, static function (string $o): bool {
        return $o !== '*' && str_starts_with($o, 'https://');
    });
    if (empty($corsAllowedOrigins)) {
        throw new \RuntimeException(
            'CORS: aucune origine HTTPS valide configurée pour la production. '
            . 'Définissez CORS_ALLOWED_ORIGINS ou FRONTEND_ORIGIN avec une URL https://.'
        );
    }
}

/**
 * Google Maps API Key
 */
$googleMapsApiKey = $env('GOOGLE_MAPS_API_KEY', '');

return [
    'db' => [
        'dsn' => "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        'user' => $dbUser,
        'pass' => $dbPass,
        'options' => $dbPdoOptions, // vide en CI/DEV, rempli en Azure si DB_SSL=true
    ],
    'cors' => [
        'allowed_origins' => array_values($corsAllowedOrigins),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-CSRF-Token'],
        'allow_credentials' => true,
        'max_age' => 86400, // 24h — cache preflight côté navigateur
    ],
    'mongo' => [
        'uri'      => $mongoUri,
        'database' => $mongoDb,
    ],
    'jwt' => [
        'secret' => $jwtSecret,
        'algo'   => 'HS256',
        'expire' => 3600,
    ],
    'csrf' => [
        'cookie_name' => 'csrfToken',
        'header_name' => 'X-CSRF-Token',
        'token_bytes' => 32,
        'ttl' => 7200,
    ],
    'csp' => [
        'default_src' => ["'self'"],
        'script_src'  => ["'self'", 'https://cdn.jsdelivr.net'],
        'style_src'   => ["'self'", "'unsafe-inline'", 'https://cdnjs.cloudflare.com'],
        'img_src'     => ["'self'", 'data:'],
        'font_src'    => ["'self'", 'https://cdnjs.cloudflare.com'],
        'connect_src' => ["'self'"],
        'frame_src'   => ["'none'"],
        'object_src'  => ["'none'"],
        'base_uri'    => ["'self'"],
        'form_action' => ["'self'"],
    ],
    'mail' => [
        'provider' => $mailProvider,
        'api_key'  => $mailApiKey,
        'host'     => $mailHost,
        'user'     => $mailUser,
        'pass'     => $mailPass,
        'from'     => $mailFrom,
    ],
    // URL frontend utilisée par les emails (fallback sur FRONTEND_ORIGIN)
    'app_url' => $env('APP_URL', $frontendOrigin),
    'env' => $appEnv,
    'debug' => (string)$appDebugRaw === 'true',
    
    // Services Tiers
    'google_maps' => [
        'api_key' => $googleMapsApiKey,
    ],
];
