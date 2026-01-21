<?php

use App\Controllers\CommandeController;
use App\Validators\CommandeValidator;
use App\Core\Request;
use App\Core\Response;
use App\Middlewares\AuthMiddleware;
use Psr\Container\ContainerInterface;

// Création de commande
$router->post('/api/commandes', function (ContainerInterface $container, array $params, Request $request) {
    // Middleware Auth
    $authMiddleware = $container->get(AuthMiddleware::class);
    $authMiddleware->handle($request);

    // Injection automatique via PHP-DI ou résolution manuelle si config simple
    // Ici nous utilisons $container->get() qui va résoudre les dépendances si configuré.
    // CommandeValidator sera injecté car ajouté dans le constructeur de CommandeController.
    $controller = $container->get(CommandeController::class);
    return $controller->create($request);
});

// Calcul de prix (Simulation avant commande)
$router->post('/api/commandes/calculate-price', function (ContainerInterface $container, array $params, Request $request) {
    $authMiddleware = $container->get(AuthMiddleware::class);
    $authMiddleware->handle($request);

    $controller = $container->get(CommandeController::class);
    return $controller->calculate($request);
});

// Update Status (Employé)
$router->post('/api/commandes/{id}/status', function (ContainerInterface $container, array $params, Request $request) {
    $authMiddleware = $container->get(AuthMiddleware::class);
    $authMiddleware->handle($request);

    $controller = $container->get(CommandeController::class);
    // L'ID est dans $params['id'] (géré par le router regex)
    return $controller->updateStatus($request, (int)$params['id']);
});
