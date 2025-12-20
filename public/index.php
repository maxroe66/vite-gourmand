<?php
use App\Middlewares\AuthMiddleware;
// 1. Autoload et config
require_once __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../backend/config/config.php';

// 2. Headers globaux (CORS + JSON)
header('Access-Control-Allow-Origin: *'); // À adapter selon l'URL de ton front
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// 3. Gestion des requêtes OPTIONS (préflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 4. Importe les classes nécessaires
use App\Core\Router;

// 5. Instancie le routeur
$router = new Router($config);

// 6. Inclut toutes les routes (auth, menus, etc.)
require __DIR__ . '/../backend/api/routes.php';

// 7. Récupère la méthode et le chemin de la requête
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 8. Enlève le préfixe /api si besoin (ex: /api/auth/test → /auth/test)
$apiPrefix = '/api';
if (str_starts_with($path, $apiPrefix)) {
    $path = substr($path, strlen($apiPrefix));
    if ($path === '') $path = '/';
}

// 9. Gestion d'erreur globale
try {
    // Debug : afficher le chemin reçu
    // var_dump($path); die();
    // Lance le dispatch
    $router->dispatch($method, $path);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        'details' => $e->getMessage()
    ]);
}