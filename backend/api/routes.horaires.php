<?php

// routes.horaires.php : routes pour la gestion des horaires

use App\Controllers\HoraireController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\RoleMiddleware;
use App\Core\Request;
use Psr\Container\ContainerInterface;

// --- Routes Horaires ---

// Liste des horaires (public — pour affichage sur le site)
$router->get('/horaires', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(HoraireController::class)->index($request);
});

// Modifier un horaire (protégé : EMPLOYE, ADMINISTRATEUR)
$router->put('/horaires/{id}', function (ContainerInterface $container, array $params, Request $request) {
    return $container->get(HoraireController::class)->update($request, $params);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['EMPLOYE', 'ADMINISTRATEUR']);
