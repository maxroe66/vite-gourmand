<?php

declare(strict_types=1);

use App\Core\Response;
use App\Core\Router;
use App\Utils\MonologLogger;

// 1) Autoload + config
require_once __DIR__ . '/../backend/vendor/autoload.php';
$config = require __DIR__ . '/../backend/config/config.php';

// 2) Headers globaux (CORS + JSON)
header('Access-Control-Allow-Origin: *'); // À adapter selon l'URL du front
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// 3) OPTIONS (préflight CORS)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 4) Méthode + chemin
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// 5) Enlève le préfixe /api si besoin (ex: /api/auth/test → /auth/test)
$apiPrefix = '/api';
if (strncmp($path, $apiPrefix, strlen($apiPrefix)) === 0) {
    $path = substr($path, strlen($apiPrefix));
    if ($path === '') {
        $path = '/';
    }
}

// 6) Healthcheck (utile sur Azure)
if ($method === 'GET' && ($path === '/' || $path === '/health')) {
    http_response_code(200);
    echo json_encode([
        'status' => 'ok',
        'message' => 'API is running',
        'env' => getenv('APP_ENV') ?: 'unknown',
    ]);
    exit;
}

/**
 * 6bis) Debug logs (temporaire)
 * Permet de lire le fichier de logs défini par LOG_FILE (par défaut /tmp/app.log).
 * À supprimer avant une vraie mise en prod.
 */
if ($method === 'GET' && $path === '/_logs') {
    $file = getenv('LOG_FILE') ?: '/tmp/app.log';

    if (!is_readable($file)) {
        http_response_code(404);
        echo json_encode([
            'error' => 'Log file not readable',
            'file' => $file,
        ]);
        exit;
    }

    $content = file_get_contents($file);
    http_response_code(200);
    echo json_encode([
        'file' => $file,
        'content' => $content,
    ]);
    exit;
}

// 7) Routeur + routes
$router = new Router($config);
require __DIR__ . '/../backend/api/routes.php';

// 8) Exécution + logs + fallback JSON
try {
    MonologLogger::getLogger()->info("Requête reçue : {$method} {$path}");

    $result = $router->dispatch($method, $path);

    // Si ton Router "return" quelque chose (ex: route /auth/test), on renvoie du JSON
    if ($result !== null) {
        if (is_array($result) || is_object($result)) {
            Response::json($result, 200);
        } else {
            echo (string) $result;
        }
    }
} catch (Throwable $e) {
    MonologLogger::getLogger()->error('Erreur serveur : ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        // En prod, évite d'exposer les détails ; si tu veux garder, décommente :
        // 'details' => $e->getMessage(),
    ]);
}
