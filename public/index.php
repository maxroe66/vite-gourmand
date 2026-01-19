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

// 3.1) Forcer HTTPS et ajouter HSTS
function _is_request_secure(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_ARR_SSL'])) { // Azure App Service
        return true;
    }
    return false;
}

// Redirect to HTTPS if request is not secure
// Only enforce in production to avoid breaking local dev / CI tests
$appEnv = getenv('APP_ENV') ?: ($_SERVER['APP_ENV'] ?? ($_ENV['APP_ENV'] ?? 'production'));
if ($appEnv === 'production' && php_sapi_name() !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
    if (!_is_request_secure()) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }
    // Add HSTS header for secure requests
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// 4) Configuration et Conteneur d'injection de dépendances (DI)
$config = require __DIR__ . '/../backend/config/config.php';
// On récupère la fonction de création du conteneur et on l'appelle avec la config.
$createContainer = require __DIR__ . '/../backend/config/container.php';
$container = $createContainer($config);

// 5) Middleware CORS global
// On instancie et exécute le middleware CORS avant toute autre chose.
$corsMiddleware = $container->get(\App\Middlewares\CorsMiddleware::class);
$corsMiddleware->handle();

// 6) Détermination de la méthode et du chemin de la requête
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// 7) Initialisation du routeur
$router = new Router();

// 8) Chargement des définitions de routes
// Les routes API sont préfixées par /api
$router->addGroup('/api', function ($router) use ($container, $config) {
    require __DIR__ . '/../backend/api/routes.php';
    require __DIR__ . '/../backend/api/routes.auth.php';
    require __DIR__ . '/../backend/api/routes.menus.php';
    require __DIR__ . '/../backend/api/routes.commandes.php';
    require __DIR__ . '/../backend/api/routes.avis.php';

    // Charger les routes de test uniquement si on est en environnement de test ou développement
    if (in_array(($config['env'] ?? 'production'), ['test', 'development'])) {
        require __DIR__ . '/../backend/api/routes.test.php';
    }
});

// Les routes "pages" qui servent du HTML statique (simpliste)
if ($method === 'GET' && strpos($path, '/api') !== 0) {
    $staticPagePath = null;
    if ($path === '/' || $path === '/home' || $path === '/accueil') {
        $staticPagePath = __DIR__ . '/../frontend/frontend/pages/home.html';
    } elseif ($path === '/inscription') {
        $staticPagePath = __DIR__ . '/../frontend/frontend/pages/inscription.html';
    } elseif ($path === '/connexion') {
        $staticPagePath = __DIR__ . '/../frontend/frontend/pages/connexion.html';
    } elseif ($path === '/reset-password') {
        $staticPagePath = __DIR__ . '/../frontend/frontend/pages/motdepasse-oublie.html';
    }

    
    if ($staticPagePath && file_exists($staticPagePath)) {
        require $staticPagePath;
        exit;
    }
}

// 9) Dispatching de la requête et envoi de la réponse
try {
    // Le routeur trouve la bonne route, exécute le handler et retourne un objet Response
    // DEBUG: log temporaire pour vérifier la valeur de LOG_FILE sur Azure
    error_log('LOG_FILE=' . getenv('LOG_FILE'));
    $response = $router->dispatch($method, $path, $container);

} catch (\Exception $e) {
    // Gestion centralisée des erreurs non capturées
    $logger = $container->get(LoggerInterface::class);
    $logger->error("Erreur non capturée: " . $e->getMessage(), [
        'exception' => $e,
        'trace' => $e->getTraceAsString()
    ]);

    // On crée une réponse d'erreur générique
    $response = (new Response())->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                               ->setJsonContent([
                                   'success' => false,
                                   'message' => 'Une erreur interne est survenue.'
                               ]);
}

// 10) Envoi final de la réponse au client
$response->send();

