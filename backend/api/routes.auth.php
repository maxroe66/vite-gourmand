<?php
// routes.auth.php : routes liées à l'authentification
$router->post('/auth/register', function() {
	$input = json_decode(file_get_contents('php://input'), true);
	if (!$input) {
		\App\Core\Response::json([
			'success' => false,
			'message' => 'Données invalides'
		], 400);
	}

	$userService = new \App\Services\UserService();
	$authService = new \App\Services\AuthService();
	$mailerService = new \App\Services\MailerService();
	$logger = \App\Utils\MonologLogger::getLogger();
	$authController = new \App\Controllers\Auth\AuthController($userService, $authService, $mailerService, $logger);

	$response = $authController->register($input);
	\App\Core\Response::json($response, $response['success'] ? 201 : 400);
});

$router->get('/auth/test', function() {
	return ['message' => 'API Auth OK'];
});

