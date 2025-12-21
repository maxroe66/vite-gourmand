<?php
// routes.auth.php : routes liées à l'authentification
$router->post('/auth/register', function() {
	$input = json_decode(file_get_contents('php://input'), true);
	if (!$input) {
		http_response_code(400);
		return [
			'success' => false,
			'message' => 'Données invalides'
		];
	}

	$userService = new \App\Services\UserService();
	$authService = new \App\Services\AuthService();
	$mailerService = new \App\Services\MailerService();
	$logger = \App\Utils\MonologLogger::getLogger();
	$authController = new \App\Controllers\Auth\AuthController($userService, $authService, $mailerService, $logger);

	$response = $authController->register($input);
	if ($response['success']) {
		http_response_code(201);
	} else {
		http_response_code(400);
	}
	return $response;
});

$router->get('/auth/test', function() {
	return ['message' => 'API Auth OK'];
});

