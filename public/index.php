<?php

use App\Utils\MonologLogger;
use App\Core\Router;

// 1. Autoload et config
require_once __DIR__ . '/../backend/vendor/autoload.php';
$config = require __DIR__ . '/../backend/config/config.php';

// 2. Headers globaux (CORS + JSON)
header('Access-Control-Allow-Origin: *'); // À adapter selon l'URL du front
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// 3. Gestion des requêtes OPTIONS (préflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 4. Récupère la méthode et le chemin de la requête
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

// 5. Petit endpoint de healthcheck (utile sur Azure pour vérifier que ça tourne)
if ($method === 'GET' && ($path === '/' || $path === '/health')) {
    http_response_code(200);
    echo json_encode([
        'status' => 'ok',
        'message' => 'API is running',
        'env' => getenv('APP_ENV') ?: 'unknown',
    ]);
    exit;
}

// 6. Instancie le routeur
$router = new Router($config);

// 7. Inclut toutes les routes (auth, menus, etc.)
require __DIR__ . '/../backend/api/routes.php';

// 8. Enlève le préfixe /api si besoin (ex: /api/auth/test → /auth/test)
$apiPrefix = '/api';
if (str_starts_with($path, $apiPrefix)) {
    $path = substr($path, strlen($apiPrefix));
    if ($path === '') {
        $path = '/';
    }
}

// 9. Gestion d'erreur globale + logs
try {
    MonologLogger::getLogger()->info("Requête reçue : $method $path");
    $router->dispatch($method, $path);
} catch (Throwable $e) {
    MonologLogger::getLogger()->error("Erreur serveur : " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        'details' => $e->getMessage(),
    ]);
}
