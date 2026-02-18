<?php

use App\Controllers\MaterielController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
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

// Détail d'un matériel
$router->get('/materiels/{id}', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MaterielController::class)->show($request, $params);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Créer un matériel
$router->post('/materiels', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MaterielController::class)->store($request);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Modifier un matériel
$router->put('/materiels/{id}', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MaterielController::class)->update($request, $params);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Supprimer un matériel
$router->delete('/materiels/{id}', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MaterielController::class)->destroy($request, $params);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);
