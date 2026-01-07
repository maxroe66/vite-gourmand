<?php
// routes.auth.php : routes liées à l'authentification

use App\Controllers\Auth\AuthController;
use App\Core\Response;
use App\Middlewares\AuthMiddleware;
use Psr\Container\ContainerInterface;

$router->post('/auth/register', function (ContainerInterface $container) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        Response::json(['success' => false, 'message' => 'Données invalides'], 400);
        return;
    }

    // On récupère le contrôleur directement depuis le conteneur
    $authController = $container->get(AuthController::class);

    $response = $authController->register($input);
    Response::json($response, $response['success'] ? 201 : 400);
});

$router->post('/auth/login', function (ContainerInterface $container) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        Response::json(['success' => false, 'message' => 'Données invalides'], 400);
        return;
    }

    // Fonctionnalité de connexion non encore disponible / non implémentée
    Response::json([
        'success' => false,
        'message' => 'Fonctionnalité de connexion non disponible'
    ], 501); // 501 Not Implemented
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

$router->get('/auth/check', function (ContainerInterface $container) {
    // 1. Appliquer le middleware d'authentification
    // Le middleware a besoin de la config, on la récupère depuis le conteneur
    AuthMiddleware::check($container->get('config'));

    // 2. Exécuter la logique du contrôleur si le middleware passe
    $authController = $container->get(AuthController::class);

    $response = $authController->checkAuth();
    Response::json($response); // Envoyer la réponse JSON
});

$router->get('/auth/test', function () {
    return ['message' => 'API Auth OK'];
});

