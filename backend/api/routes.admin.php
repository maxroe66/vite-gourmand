<?php

use App\Controllers\AdminController;
use App\Core\Request;
use Psr\Container\ContainerInterface;

// Création compte employé
$router->post('/admin/employees', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(AdminController::class);
    return $controller->createEmployee($request);
});

// Désactiver un utilisateur
$router->patch('/admin/users/{id}/disable', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(AdminController::class);
    return $controller->disableUser($params, $request);
});
