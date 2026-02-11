<?php

use App\Controllers\AvisController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\RoleMiddleware;
use App\Exceptions\AuthException;
use App\Core\Request;
use Psr\Container\ContainerInterface;

// Créer un avis
$router->post('/avis', function (ContainerInterface $container, array $params, Request $request) {
    $middleware = $container->get(AuthMiddleware::class);
    $middleware->handle($request);
    
    $controller = $container->get(AvisController::class);
    return $controller->create($request);
})->middleware(CsrfMiddleware::class);

// Lister les avis (Admin dashboard ou public)
$router->get('/avis', function (ContainerInterface $container, array $params, Request $request) {
    // Auth facultative : si un token est présent on enrichit la requête, sinon on sert la version publique.
    $middleware = $container->get(AuthMiddleware::class);
    try {
        $middleware->handle($request);
    } catch (AuthException $e) {
        if ($e->getCode() !== AuthException::TOKEN_MISSING) {
            throw $e;
        }
        // Pas de token : on continue en mode public (avis validés uniquement)
    }

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
    $controller = $container->get(AvisController::class);
    return $controller->validate($request, $params);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['ADMINISTRATEUR']);

// Supprimer/Refuser un avis
$router->delete('/avis/{id}', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(AvisController::class);
    return $controller->delete($request, $params);
})
->middleware(CsrfMiddleware::class)
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['ADMINISTRATEUR']);
