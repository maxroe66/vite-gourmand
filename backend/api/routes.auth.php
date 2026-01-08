<?php

// routes.auth.php : routes liées à l'authentification

use App\Controllers\Auth\AuthController;
use App\Core\Request;
use App\Core\Response;
use App\Middlewares\AuthMiddleware;
use Psr\Container\ContainerInterface;

$router->post('/auth/register', function (ContainerInterface $container) {
    $authController = $container->get(AuthController::class);
    $response = $authController->register();
    Response::json($response, $response['success'] ? 201 : 400);
});

$router->post('/auth/login', function (ContainerInterface $container) {
    $authController = $container->get(AuthController::class);
    $response = $authController->login();
    Response::json($response, $response['success'] ? 200 : 401);
});

$router->post('/auth/logout', function () {
    // La déconnexion ne nécessite aucune dépendance, juste la manipulation du cookie.

    // 1. Invalider le cookie en le supprimant
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

    setcookie('authToken', '', [
        'expires' => time() - 3600, // Expiré dans le passé
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // 2. Répondre avec succès
    Response::json([
        'success' => true,
        'message' => 'Déconnexion réussie.'
    ]);
});

$router->get('/auth/check', function (ContainerInterface $container, array $params, Request $request) {
    // Le middleware a déjà été exécuté et a enrichi l'objet $request.
    // On passe cet objet au contrôleur.
    $authController = $container->get(AuthController::class);
    $response = $authController->checkAuth($request);
    Response::json($response);
})->middleware(AuthMiddleware::class); // On attache le middleware à la route.

$router->get('/auth/test', function () {
    Response::json(['message' => 'API Auth OK']);
});
