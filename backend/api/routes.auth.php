<?php

// routes.auth.php : routes liées à l'authentification

use App\Controllers\Auth\AuthController;
use App\Core\Request;
use App\Core\Response;
use App\Middlewares\AuthMiddleware;
use Psr\Container\ContainerInterface;

$router->post('/auth/register', function (ContainerInterface $container, array $params, Request $request) {
    $authController = $container->get(AuthController::class);
    // Le contrôleur retourne directement un objet Response
    return $authController->register($request);
});

$router->post('/auth/login', function (ContainerInterface $container, array $params, Request $request) {
    $authController = $container->get(AuthController::class);
    // Le contrôleur retourne directement un objet Response
    return $authController->login($request);
});

$router->post('/auth/logout', function (ContainerInterface $container, array $params, Request $request) {
    $authController = $container->get(AuthController::class);
    // Le contrôleur retourne directement un objet Response
    return $authController->logout();
});

// Route GET explicite pour renvoyer 404 (sécurité/idempotence)
$router->get('/auth/logout', function () {
    return (new Response())
        ->setStatusCode(Response::HTTP_NOT_FOUND)
        ->setJsonContent(['error' => 'Not found']);
});

// Routes mot de passe oublié
$router->post('/auth/forgot-password', function (ContainerInterface $container, array $params, Request $request) {
    $authController = $container->get(AuthController::class);
    return $authController->forgotPassword($request);
});

$router->post('/auth/reset-password', function (ContainerInterface $container, array $params, Request $request) {
    $authController = $container->get(AuthController::class);
    return $authController->resetPassword($request);
});

$router->get('/auth/check', function (ContainerInterface $container, array $params, Request $request) {
    // Le middleware a déjà été exécuté et a enrichi l'objet $request.
    $authController = $container->get(AuthController::class);
    // Le contrôleur retourne directement un objet Response
    return $authController->checkAuth($request);
})->middleware(AuthMiddleware::class); // On attache le middleware à la route.

$router->get('/auth/test', function () {
    // On retourne un nouvel objet Response pour le test
    return (new Response())->setJsonContent(['message' => 'API Auth OK']);
});
