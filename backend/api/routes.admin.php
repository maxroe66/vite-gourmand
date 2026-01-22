<?php

use App\Controllers\AdminController;
use App\Core\Request;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\RoleMiddleware;
use Psr\Container\ContainerInterface;

// Création compte employé
$router->post('/admin/employees', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(AdminController::class);
    return $controller->createEmployee($request);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['ADMINISTRATEUR']);

// Lister les employés
$router->get('/admin/employees', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(AdminController::class);
    return $controller->getEmployees($request);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['ADMINISTRATEUR']);

// Désactiver un utilisateur
$router->patch('/admin/users/{id}/disable', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(AdminController::class);
    return $controller->disableUser($params, $request);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['ADMINISTRATEUR']);
