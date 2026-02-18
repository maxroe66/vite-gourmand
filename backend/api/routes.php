<?php

require __DIR__ . '/routes.auth.php';
require __DIR__ . '/routes.menus.php';
require __DIR__ . '/routes.commandes.php';
require __DIR__ . '/routes.avis.php';
require __DIR__ . '/routes.admin.php';
require __DIR__ . '/routes.materiel.php'; // Routes matériel
require __DIR__ . '/routes.horaires.php'; // Routes horaires
require __DIR__ . '/routes.contact.php'; // Routes contact (formulaire public)
require __DIR__ . '/routes.diagnostic.php'; // Route de diagnostic MongoDB

// Routes de test : accessibles uniquement en environnement test/development
$appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
if (in_array($appEnv, ['test', 'development'], true)) {
    require __DIR__ . '/routes.test.php';
}

use App\Controllers\UploadController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\RoleMiddleware;
use Psr\Container\ContainerInterface;
use App\Core\Request;

// Route upload (Protégée : EMPLOYE, ADMINISTRATEUR)
$router->post('/upload', function (ContainerInterface $container, array $params, Request $request) {
    // On utilise le conteneur pour résoudre les dépendances (StorageService)
    $controller = $container->get(UploadController::class);
    return $controller->uploadImage($request);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// routes.php : point d'entrée pour toutes les routes de l'API
