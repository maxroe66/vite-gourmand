<?php

use App\Controllers\MaterielController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\RoleMiddleware;
use App\Core\Request;
use Psr\Container\ContainerInterface;

// --- Routes Matériel (Protégées : EMPLOYE, ADMINISTRATEUR) ---

// Liste du matériel
$router->get('/materiels', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MaterielController::class)->index($request);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);
