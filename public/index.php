<?php
// Charge l'autoloader Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Charge la config (qui charge aussi .env)
$config = require __DIR__ . '/../backend/config/config.php';

// Importe les classes nécessaires
use App\Core\Router;

// Instancie le routeur
$router = new Router($config);

// Inclut toutes les routes (auth, menus, etc.)
require __DIR__ . '/../backend/api/routes.php';

// Récupère la méthode et le chemin de la requête
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Enlève le préfixe /api si besoin (ex: /api/auth/test → /auth/test)
$apiPrefix = '/api';
if (str_starts_with($path, $apiPrefix)) {
    $path = substr($path, strlen($apiPrefix));
    if ($path === '') $path = '/';
}

// Debug : afficher le chemin reçu
//var_dump($path); die();
// Lance le dispatch
$router->dispatch($method, $path);