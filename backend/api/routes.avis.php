<?php

use App\Controllers\AvisController;
use App\Middlewares\AuthMiddleware;
use App\Core\Request;
use Psr\Container\ContainerInterface;

// Créer un avis
$router->post('/avis', function (ContainerInterface $container, array $params, Request $request) {
    $middleware = $container->get(AuthMiddleware::class);
    $middleware->handle($request);
    
    $controller = $container->get(AvisController::class);
    return $controller->create($request);
});

// Lister les avis (Admin dashboard ou public)
$router->get('/avis', function (ContainerInterface $container, array $params, Request $request) {
    // On protège a minima pour récupérer l'user / role si nécessaire.
    $middleware = $container->get(AuthMiddleware::class);
    $middleware->handle($request);

    $controller = $container->get(AvisController::class);
    return $controller->list($request);
});

// Avis public (pour la page d'accueil)
$router->get('/avis/public', function (ContainerInterface $container, array $params, Request $request) {
    // Pas d'authentification requise
    $controller = $container->get(AvisController::class);
    return $controller->listPublic($request);
});

// Valider un avis
$router->put('/avis/{id}/validate', function (ContainerInterface $container, array $params, Request $request) {
    $middleware = $container->get(AuthMiddleware::class);
    $middleware->handle($request);

    $controller = $container->get(AvisController::class);
    return $controller->validate($request, $params);
});

// Supprimer/Refuser un avis
$router->delete('/avis/{id}', function (ContainerInterface $container, array $params, Request $request) {
    $middleware = $container->get(AuthMiddleware::class);
    $middleware->handle($request);

    $controller = $container->get(AvisController::class);
    return $controller->delete($request, $params);
});
