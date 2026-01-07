<?php
declare(strict_types=1);

// 1) Chargement de l'autoloader Composer (DOIT ÊTRE EN PREMIER)
require_once __DIR__ . '/../backend/vendor/autoload.php';

// 2) Chargement des variables d'environnement
if (file_exists(__DIR__ . '/../.env.azure')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.azure');
    $dotenv->load();
} elseif (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// 3) Configuration des erreurs pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Core\Response;
use App\Core\Router;
use Psr\Log\LoggerInterface;

// 4) Configuration et Conteneur d'injection de dépendances (DI)
$config = require __DIR__ . '/../backend/config/config.php';
// On récupère la fonction de création du conteneur et on l'appelle avec la config.
$createContainer = require __DIR__ . '/../backend/config/container.php';
$container = $createContainer($config);

// 5) Headers globaux (CORS)
$allowedOrigin = $_ENV['FRONTEND_ORIGIN'] ?? getenv('FRONTEND_ORIGIN') ?? 'http://localhost:8000';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 6) Réponse pour les requêtes OPTIONS (pré-vérification CORS)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 7) Détermination de la méthode et du chemin de la requête
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// 8) Initialisation du routeur
$router = new Router();

// 9) Chargement des définitions de routes
// Les routes API sont préfixées par /api
$router->addGroup('/api', function ($router) use ($container) {
    require __DIR__ . '/../backend/api/routes.php';
});

// Les routes "pages" qui servent du HTML statique
if ($method === 'GET') {
    // Page d'accueil
    if ($path === '/' || $path === '/home' || $path === '/accueil') {
        require __DIR__ . '/../frontend/frontend/pages/home.html';
        exit;
    }
    
    // Page d'inscription
    if ($path === '/inscription') {
        require __DIR__ . '/../frontend/frontend/pages/inscription.html';
        exit;
    }

    // Autres pages...
}


// 10) Dispatching de la requête
try {
    // Le routeur trouve la bonne route et exécute le handler associé
    $router->dispatch($method, $path, $container);

} catch (\Exception $e) {
    // Gestion centralisée des erreurs non capturées
    // On récupère le logger depuis le conteneur pour logger l'erreur.
    $logger = $container->get(LoggerInterface::class);
    $logger->error("Erreur non capturée: " . $e->getMessage(), [
        'exception' => $e,
        'trace' => $e->getTraceAsString()
    ]);

    Response::json([
        'success' => false,
        'message' => 'Une erreur interne est survenue.'
    ], 500);
}

