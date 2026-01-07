<?php
// routes.auth.php : routes liées à l'authentification

$router->post('/auth/register', function ($config) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        \App\Core\Response::json([
            'success' => false,
            'message' => 'Données invalides'
        ], 400);
    }

    // Création de la connexion PDO à partir de la config (+ options SSL éventuelles)
    $pdoOptions = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ];

    if (isset($config['db']['options']) && is_array($config['db']['options'])) {
        // Les options de config (ex: SSL) prennent le dessus si même clé
        $pdoOptions = $config['db']['options'] + $pdoOptions;
    }

    $pdo = new \PDO(
        $config['db']['dsn'],
        $config['db']['user'],
        $config['db']['pass'],
        $pdoOptions
    );

    $userService = new \App\Services\UserService($pdo);
    $authService = new \App\Services\AuthService($config);
    $mailerService = new \App\Services\MailerService();
    $logger = \App\Utils\MonologLogger::getLogger();
    $authController = new \App\Controllers\Auth\AuthController($userService, $authService, $mailerService, $logger, $config);

    $response = $authController->register($input);
    \App\Core\Response::json($response, $response['success'] ? 201 : 400);
});

$router->post('/auth/login', function ($config) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        \App\Core\Response::json([
            'success' => false,
            'message' => 'Données invalides'
        ], 400);
    }

    // Fonctionnalité de connexion non encore disponible / non implémentée
    \App\Core\Response::json([
        'success' => false,
        'message' => 'Fonctionnalité de connexion non disponible'
    ], 501); // 501 Not Implemented

    return;
});

$router->post('/auth/logout', function ($config) {
    // Dépendances
    $pdo = new \PDO($config['db']['dsn'], $config['db']['user'], $config['db']['pass']);
    $userService = new \App\Services\UserService($pdo);
    $authService = new \App\Services\AuthService($config);
    $mailerService = new \App\Services\MailerService();
    $logger = \App\Utils\MonologLogger::getLogger();
    $authController = new \App\Controllers\Auth\AuthController($userService, $authService, $mailerService, $logger, $config);

    return $authController->logout();
});

$router->get('/auth/check', function ($config) {
    // 1. Appliquer le middleware d'authentification
    \App\Middlewares\AuthMiddleware::check($config);

    // 2. Exécuter la logique du contrôleur si le middleware passe
    $pdo = new \PDO($config['db']['dsn'], $config['db']['user'], $config['db']['pass']);
    $userService = new \App\Services\UserService($pdo);
    $authService = new \App\Services\AuthService($config);
    $mailerService = new \App\Services\MailerService();
    $logger = \App\Utils\MonologLogger::getLogger();
    $authController = new \App\Controllers\Auth\AuthController($userService, $authService, $mailerService, $logger, $config);

    $response = $authController->checkAuth();
    \App\Core\Response::json($response); // Envoyer la réponse JSON
});

$router->get('/auth/test', function () {
    return ['message' => 'API Auth OK'];
});
