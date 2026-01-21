<?php

use App\Controllers\CommandeController;
use App\Validators\CommandeValidator;
use App\Core\Request;
use App\Core\Response;
use App\Middlewares\AuthMiddleware;
use Psr\Container\ContainerInterface;

// Création de commande
$router->post('/commandes', function (ContainerInterface $container, array $params, Request $request) {
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
$router->post('/commandes/calculate-price', function (ContainerInterface $container, array $params, Request $request) {
    $authMiddleware = $container->get(AuthMiddleware::class);
    $authMiddleware->handle($request);

    $controller = $container->get(CommandeController::class);
    return $controller->calculate($request);
});

// Modification partielle de la commande (Client : tant que non accepté)
$router->patch('/commandes/{id}', function (ContainerInterface $container, array $params, Request $request) {
    $authMiddleware = $container->get(AuthMiddleware::class);
    $authMiddleware->handle($request);

    $controller = $container->get(CommandeController::class);
    return $controller->update($request, (int)$params['id']);
});

// Update Status (Employé ou Annulation Client)
$router->put('/commandes/{id}/status', function (ContainerInterface $container, array $params, Request $request) {
    $authMiddleware = $container->get(AuthMiddleware::class);
    $authMiddleware->handle($request);

    $controller = $container->get(CommandeController::class);
    // L'ID est dans $params['id'] (géré par le router regex)
    return $controller->updateStatus($request, (int)$params['id']);
});

// Consultation des commandes de l'utilisateur
$router->get('/my-orders', function (ContainerInterface $container, array $params, Request $request) {
    $authMiddleware = $container->get(AuthMiddleware::class);
    $authMiddleware->handle($request);

    $controller = $container->get(CommandeController::class);
    return $controller->listMyOrders($request);
});

// Consultation d'une commande spécifique
$router->get('/commandes/{id}', function (ContainerInterface $container, array $params, Request $request) {
    $authMiddleware = $container->get(AuthMiddleware::class);
    $authMiddleware->handle($request);

    $controller = $container->get(CommandeController::class);
    return $controller->show($request, (int)$params['id']);
});

// Ajout de matériel prêté (Employé)
$router->post('/commandes/{id}/material', function (ContainerInterface $container, array $params, Request $request) {
    $authMiddleware = $container->get(AuthMiddleware::class);
    $authMiddleware->handle($request);

    $controller = $container->get(CommandeController::class);
    return $controller->loanMaterial($request, (int)$params['id']);
});

// Liste filtrée des commandes (Employé)
$router->get('/commandes', function (ContainerInterface $container, array $params, Request $request) {
    $authMiddleware = $container->get(AuthMiddleware::class);
    $authMiddleware->handle($request);

    $controller = $container->get(CommandeController::class);
    return $controller->index($request);
});

// Stats Commandes (Admin)
$router->get('/menues-commandes-stats', function (ContainerInterface $container, array $params, Request $request) {
    $authMiddleware = $container->get(AuthMiddleware::class);
    $authMiddleware->handle($request);

    // Injection StatsController
    $controller = $container->get(App\Controllers\StatsController::class);
    return $controller->getMenuStats($request);
});
