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

    $userRepository = new \App\Repositories\UserRepository($pdo);
    $userService = new \App\Services\UserService($userRepository);
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
    \App\Core\Response::json([
        'success' => true,
        'message' => 'Déconnexion réussie.'
    ]);
});

$router->get('/auth/check', function ($config) {
    // 1. Appliquer le middleware d'authentification
    \App\Middlewares\AuthMiddleware::check($config);

    // 2. Exécuter la logique du contrôleur si le middleware passe
    $pdo = new \PDO($config['db']['dsn'], $config['db']['user'], $config['db']['pass']);
    $userRepository = new \App\Repositories\UserRepository($pdo);
    $userService = new \App\Services\UserService($userRepository);
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
