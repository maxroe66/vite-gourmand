<?php

// routes.auth.php : routes liées à l'authentification

use App\Controllers\Auth\AuthController;
use App\Core\Request;
use App\Core\Response;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\RateLimitMiddleware;
use App\Services\CsrfService;
use Psr\Container\ContainerInterface;

$router->post('/auth/register', function (ContainerInterface $container, array $params, Request $request) {
    $authController = $container->get(AuthController::class);
    // Le contrôleur retourne directement un objet Response
    return $authController->register($request);
})
->middleware(RateLimitMiddleware::class, ['maxRequests' => 5, 'windowSeconds' => 3600, 'prefix' => 'register'])
->middleware(CsrfMiddleware::class);

$router->post('/auth/login', function (ContainerInterface $container, array $params, Request $request) {
    $authController = $container->get(AuthController::class);
    // Le contrôleur retourne directement un objet Response
    return $authController->login($request);
})
->middleware(RateLimitMiddleware::class, ['maxRequests' => 5, 'windowSeconds' => 900, 'prefix' => 'login'])
->middleware(CsrfMiddleware::class);

$router->post('/auth/logout', function (ContainerInterface $container, array $params, Request $request) {
    $authController = $container->get(AuthController::class);
    // Le contrôleur retourne directement un objet Response
    return $authController->logout();
})->middleware(CsrfMiddleware::class);

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
})
->middleware(RateLimitMiddleware::class, ['maxRequests' => 3, 'windowSeconds' => 900, 'prefix' => 'forgot-pwd'])
->middleware(CsrfMiddleware::class);

$router->post('/auth/reset-password', function (ContainerInterface $container, array $params, Request $request) {
    $authController = $container->get(AuthController::class);
    return $authController->resetPassword($request);
})
->middleware(RateLimitMiddleware::class, ['maxRequests' => 5, 'windowSeconds' => 900, 'prefix' => 'reset-pwd'])
->middleware(CsrfMiddleware::class);

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

$router->get('/csrf', function (ContainerInterface $container) {
    $csrfService = $container->get(CsrfService::class);
    $token = $csrfService->ensureTokenCookie();
    return (new Response())->setJsonContent([
        'success' => true,
        'csrfToken' => $token
    ]);
});
