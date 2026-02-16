<?php
declare(strict_types=1);

// 1) Chargement de l'autoloader Composer (DOIT ÊTRE EN PREMIER)
require_once __DIR__ . '/../backend/vendor/autoload.php';

// 2) Chargement des variables d'environnement
// Détection de l'environnement (CLI, serveur web, ou défaut production)
$appEnv = getenv('APP_ENV') ?: ($_SERVER['APP_ENV'] ?? ($_ENV['APP_ENV'] ?? 'production'));

if ($appEnv === 'test' && file_exists(__DIR__ . '/../.env.test')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.test');
    $dotenv->load();
} elseif (file_exists(__DIR__ . '/../.env.azure')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.azure');
    $dotenv->load();
} elseif (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// 3) Configuration des erreurs selon l'environnement
$isDevEnv = in_array(($appEnv ?: 'production'), ['development', 'test'], true);
ini_set('display_errors', $isDevEnv ? '1' : '0');
ini_set('display_startup_errors', $isDevEnv ? '1' : '0');
error_reporting(E_ALL);

// 3.1) Configuration encodage UTF-8
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
if (function_exists('mb_http_output')) {
    mb_http_output('UTF-8');
}
ini_set('default_charset', 'UTF-8');

use App\Core\Response;
use App\Core\Router;
use Psr\Log\LoggerInterface;

// 3.2) Forcer HTTPS et ajouter HSTS en production
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

// Redirection HTTPS uniquement en production (ne pas casser le dev local / CI)
if ($appEnv === 'production' && php_sapi_name() !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
    if (!_is_request_secure()) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }
    // En-tête HSTS pour les requêtes sécurisées
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// 4) Configuration et Conteneur d'injection de dépendances (DI)
$config = require __DIR__ . '/../backend/config/config.php';
// On récupère la fonction de création du conteneur et on l'appelle avec la config.
$createContainer = require __DIR__ . '/../backend/config/container.php';
$container = $createContainer($config);

// 5) Middlewares globaux (CORS + Security Headers)
$corsMiddleware = $container->get(\App\Middlewares\CorsMiddleware::class);
$corsMiddleware->handle();

$securityHeadersMiddleware = $container->get(\App\Middlewares\SecurityHeadersMiddleware::class);
$securityHeadersMiddleware->handle();

// 6) Détermination de la méthode et du chemin de la requête
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// 6.1) Initialiser le cookie CSRF pour les requêtes non mutantes
if (in_array($method, ['GET', 'HEAD'], true)) {
    $csrfService = $container->get(\App\Services\CsrfService::class);
    $csrfService->ensureTokenCookie();
}

// 7) Initialisation du routeur
$router = new Router();

// 8) Chargement des définitions de routes API (préfixe /api)
// Le fichier routes.php centralise le chargement de toutes les routes
$router->addGroup('/api', function ($router) use ($container, $config) {
    require __DIR__ . '/../backend/api/routes.php';
});

// Les routes "pages" qui servent du HTML statique (simpliste)
if ($method === 'GET' && strpos($path, '/api') !== 0) {
    $staticPagePath = null;
    if ($path === '/' || $path === '/home' || $path === '/accueil') {
        $staticPagePath = __DIR__ . '/../frontend/pages/home.html';
    } elseif ($path === '/inscription') {
        $staticPagePath = __DIR__ . '/../frontend/pages/inscription.html';
    } elseif ($path === '/connexion') {
        $staticPagePath = __DIR__ . '/../frontend/pages/connexion.html';
    } elseif ($path === '/reset-password') {
        $staticPagePath = __DIR__ . '/../frontend/pages/motdepasse-oublie.html';
    } elseif ($path === '/contact') {
        $staticPagePath = __DIR__ . '/../frontend/pages/contact.html';
    }

    if ($staticPagePath && file_exists($staticPagePath)) {
        require $staticPagePath;
        exit;
    }
}

// 9) Dispatching de la requête et envoi de la réponse
try {
    $response = $router->dispatch($method, $path, $container);

} catch (\App\Exceptions\AuthException $e) {
    $response = (new Response())->setStatusCode(Response::HTTP_UNAUTHORIZED)
                               ->setJsonContent([
                                   'success' => false,
                                   'message' => $e->getMessage()
                               ]);

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

