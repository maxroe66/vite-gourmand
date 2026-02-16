<?php

// routes.contact.php : routes liées au formulaire de contact

use App\Controllers\ContactController;
use App\Core\Request;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\RateLimitMiddleware;
use Psr\Container\ContainerInterface;

$router->post('/contact', function (ContainerInterface $container, array $params, Request $request) {
    $controller = $container->get(ContactController::class);
    return $controller->submit($request);
})
->middleware(RateLimitMiddleware::class, ['maxRequests' => 5, 'windowSeconds' => 3600, 'prefix' => 'contact'])
->middleware(CsrfMiddleware::class);
