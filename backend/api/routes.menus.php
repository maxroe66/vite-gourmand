<?php

use App\Controllers\MenuController;
use App\Controllers\PlatController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\RoleMiddleware;
use App\Core\Request;
use Psr\Container\ContainerInterface;

// --- Routes Menus (Publiques) ---

// Récupération des thèmes (défini avant /{id} pour éviter les conflits)
$router->get('/menus/themes', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MenuController::class)->getThemes($request);
});

// Récupération des régimes
$router->get('/menus/regimes', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MenuController::class)->getRegimes($request);
});

// Détail d'un menu
$router->get('/menus/{id}', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MenuController::class)->show($request, (int)$params['id']);
});

// Liste des menus
$router->get('/menus', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MenuController::class)->index($request);
});

// --- Routes Menus (Protégées : EMPLOYE, ADMINISTRATEUR) ---

// Création menu
$router->post('/menus', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MenuController::class)->store($request);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Modification menu
$router->put('/menus/{id}', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MenuController::class)->update($request, (int)$params['id']);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Suppression menu
$router->delete('/menus/{id}', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(MenuController::class)->destroy($request, (int)$params['id']);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);


// --- Routes Plats (Protégées : EMPLOYE, ADMINISTRATEUR) ---

// Liste des plats par type (via query param ?type=...)
$router->get('/plats/by-type', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(PlatController::class)->getByType($request);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Détail d'un plat
$router->get('/plats/{id}', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(PlatController::class)->show($request, (int)$params['id']);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Liste de tous les plats
$router->get('/plats', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(PlatController::class)->index($request);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Création plat
$router->post('/plats', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(PlatController::class)->store($request);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Modification plat
$router->put('/plats/{id}', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(PlatController::class)->update($request, (int)$params['id']);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);

// Suppression plat
$router->delete('/plats/{id}', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(PlatController::class)->destroy($request, (int)$params['id']);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);
