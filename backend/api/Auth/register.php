<?php
use Backend\Controllers\Auth\AuthController;
use Backend\Services\UserService;
use Backend\Services\AuthService;
use Backend\Services\MailerService;
use Backend\Utils\MonologLogger;

require_once __DIR__ . '/../../../controllers/Auth/AuthController.php';
require_once __DIR__ . '/../../../services/UserService.php';
require_once __DIR__ . '/../../../services/AuthService.php';
require_once __DIR__ . '/../../../services/MailerService.php';
require_once __DIR__ . '/../../../Utils/MonologLogger.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données POST
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Instanciation des services
$userService = new UserService();
$authService = new AuthService();
$mailerService = new MailerService();
$logger = MonologLogger::getLogger();

// Instanciation du contrôleur
$authController = new AuthController($userService, $authService, $mailerService, $logger);

// Appel à la méthode register
$response = $authController->register($input);

// Réponse JSON
if ($response['success']) {
    http_response_code(201);
} else {
    http_response_code(400);
}
echo json_encode($response);