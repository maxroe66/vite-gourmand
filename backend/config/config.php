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

$dbPdoOptions = [];
if ($dbSslEnabled) {
    $dbPdoOptions = [
        \PDO::MYSQL_ATTR_SSL_CA => $dbSslCa,
        \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
    ];
}

/**
 * MongoDB
 * - Standard: MONGO_DB + (MONGO_URI ou MONGO_HOST/MONGO_PORT)
 */
$mongoDb = $env('MONGO_DB', 'vite_gourmand');

$mongoUri = $env('MONGO_URI', '');
if ($mongoUri === '') {
    $mongoHost = $env('MONGO_HOST', '127.0.0.1');
    $mongoPort = $env('MONGO_PORT', '27017');
    $mongoUri = "mongodb://{$mongoHost}:{$mongoPort}";
}

/**
 * Mail
 * - Standard: MAIL_USERNAME / MAIL_PASSWORD / MAIL_FROM_ADDRESS
 * - Compat: MAIL_USER / MAIL_PASS / MAIL_FROM
 */
$mailHost = $env('MAIL_HOST', '');
$mailUser = $env('MAIL_USERNAME') ?? $env('MAIL_USER') ?? '';
$mailPass = $env('MAIL_PASSWORD') ?? $env('MAIL_PASS') ?? '';
$mailFrom = $env('MAIL_FROM_ADDRESS') ?? $env('MAIL_FROM') ?? '';

return [
    'db' => [
        'dsn' => "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        'user' => $dbUser,
        'pass' => $dbPass,
        'options' => $dbPdoOptions, // vide en CI/DEV, rempli en Azure si DB_SSL=true
    ],
    'mongo' => [
        'uri'      => $mongoUri,
        'database' => $mongoDb,
    ],
    'jwt' => [
        'secret' => $env('JWT_SECRET', '<placeholder>') ?? '<placeholder>',
        'algo'   => 'HS256',
        'expire' => 3600,
    ],
    'mail' => [
        'host' => $mailHost,
        'user' => $mailUser,
        'pass' => $mailPass,
        'from' => $mailFrom,
    ],
    'env' => $appEnv,
    'debug' => (string)$appDebugRaw === 'true',
];
