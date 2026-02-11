<?php

use App\Controllers\CommandeController;
use App\Validators\CommandeValidator;
use App\Core\Request;
use App\Core\Response;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\RateLimitMiddleware;
use App\Middlewares\RoleMiddleware;
use Psr\Container\ContainerInterface;

// Création de commande
$router->post('/commandes', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(CommandeController::class);
    return $controller->create($request);
})
->middleware(RateLimitMiddleware::class, ['maxRequests' => 10, 'windowSeconds' => 60, 'prefix' => 'commande-create'])
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class);

// Calcul de prix (Simulation avant commande)
$router->post('/commandes/calculate-price', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(CommandeController::class);
    return $controller->calculate($request);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class);

// Modification partielle de la commande (Client : tant que non accepté)
$router->patch('/commandes/{id}', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(CommandeController::class);
    return $controller->update($request, (int)$params['id']);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class);

// Update Status (Employé ou Annulation Client)
$router->put('/commandes/{id}/status', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(CommandeController::class);
    return $controller->updateStatus($request, (int)$params['id']);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Consultation des commandes de l'utilisateur
$router->get('/my-orders', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(CommandeController::class);
    return $controller->listMyOrders($request);
})->middleware(AuthMiddleware::class);

// Consultation d'une commande spécifique
$router->get('/commandes/{id}', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(CommandeController::class);
    return $controller->show($request, (int)$params['id']);
})->middleware(AuthMiddleware::class);

// Ajout de matériel prêté (Employé)
$router->post('/commandes/{id}/material', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(CommandeController::class);
    return $controller->loanMaterial($request, (int)$params['id']);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Retour du matériel prêté (Employé/Admin)
$router->post('/commandes/{id}/return-material', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(CommandeController::class);
    return $controller->returnMaterial($request, (int)$params['id']);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Liste filtrée des commandes (Employé)
$router->get('/commandes', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(CommandeController::class);
    return $controller->index($request);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Stats Commandes (Admin)
$router->get('/menues-commandes-stats', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(App\Controllers\StatsController::class);
    return $controller->getMenuStats($request);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['ADMINISTRATEUR']);
