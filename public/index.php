<?php
declare(strict_types=1);

// Chargement de l'autoloader Composer
require_once __DIR__ . '/../backend/vendor/autoload.php';

// Chargement automatique du .env (local/dev)
if (file_exists(__DIR__ . '/../.env.azure')) {
    // Utilise le .env.azure si présent
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.azure');
    $dotenv->load();
} elseif (file_exists(__DIR__ . '/../.env')) {
    // Sinon fallback sur .env classique
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Core\Response;
use App\Core\Router;
use App\Utils\MonologLogger;

// 1) Autoload + config
require_once __DIR__ . '/../backend/vendor/autoload.php';
$config = require __DIR__ . '/../backend/config/config.php';

// 2) Headers globaux (CORS)
header('Access-Control-Allow-Origin: *'); // À adapter selon l'URL du front
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
// Note: Content-Type sera défini selon le contexte (JSON pour API, HTML pour pages)

// 3) OPTIONS (préflight CORS)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 4) Méthode + chemin
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// 5) Fichiers statiques (CSS, JS, images, fonts, HTML composants) - laisser passer sans traitement
$staticExtensions = ['.css', '.js', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot', '.html'];
foreach ($staticExtensions as $ext) {
    if (str_ends_with($path, $ext)) {
        // Laisser Apache servir le fichier directement
        return false;
    }
}

// 6) Routes frontend (pages HTML principales uniquement)
if ($method === 'GET' && !str_starts_with($path, '/api') && !str_contains($path, '/components/')) {
    // Route d'accueil
    if ($path === '/' || $path === '/home' || $path === '/accueil') {
        require __DIR__ . '/../frontend/frontend/pages/home.html';
        exit;
    }
    
    // Route inscription
    if ($path === '/inscription') {
        require __DIR__ . '/../frontend/frontend/pages/inscription.html';
        exit;
    }
    
    // Route connexion
    if ($path === '/connexion') {
        require __DIR__ . '/../frontend/frontend/pages/connexion.html';
        exit;
    }
}

// 7) Enlève le préfixe /api si besoin (ex: /api/auth/test → /auth/test)
$apiPrefix = '/api';
if (strncmp($path, $apiPrefix, strlen($apiPrefix)) === 0) {
    $path = substr($path, strlen($apiPrefix));
    if ($path === '') {
        $path = '/';
    }
}

// 8) Healthcheck API (utile sur Azure)
if ($method === 'GET' && ($path === '/' || $path === '/health')) {
    http_response_code(200);
    echo json_encode([
        'status' => 'ok',
        'message' => 'API is running',
        'env' => getenv('APP_ENV') ?: 'unknown',
    ]);
    exit;
}


// 9) Routeur + routes
$router = new Router($config);
require __DIR__ . '/../backend/api/routes.php';

// 10) Exécution + logs + fallback JSON
try {
    MonologLogger::getLogger()->info("Requête reçue : {$method} {$path}");
    
    // Content-Type JSON pour l'API
    header('Content-Type: application/json; charset=utf-8');

    $result = $router->dispatch($method, $path);

    // Si ton Router "return" quelque chose (ex: route /auth/test), on renvoie du JSON
    if ($result !== null) {
        if (is_array($result) || is_object($result)) {
            Response::json($result, 200);
        } else {
            echo (string)$result;
        }
    }
} catch (Throwable $e) {
    MonologLogger::getLogger()->error('Erreur serveur : ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        'details' => $e->getMessage(),
    ]);
}
