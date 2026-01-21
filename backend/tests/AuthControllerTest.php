<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\Auth\AuthController;
use App\Services\UserService;
use App\Services\AuthService;
use App\Services\MailerService;
use App\Validators\UserValidator;
use App\Validators\LoginValidator;
use App\Exceptions\UserServiceException;
use App\Exceptions\InvalidCredentialsException;
use Psr\Log\LoggerInterface;
use App\Core\Request;
use App\Core\Response;

class AuthControllerTest extends TestCase
{
    private AuthController $authController;
    private UserService $userServiceMock;
    private AuthService $authServiceMock;
    private MailerService $mailerServiceMock;
    private UserValidator $userValidatorMock;
    private LoginValidator $loginValidatorMock;
    private LoggerInterface $loggerMock;
    private array $config;

    protected function setUp(): void
    {
        // Configuration de test
        $this->config = [
            'jwt' => [
                'secret' => 'test-secret-key-minimum-32-characters-long',
                'expire' => 3600
            ],
            'app' => [
                'dummy_hash' => '$2y$10$abcdefghijklmnopqrstuv' // 22 chars for salt
            ]
        ];

        // Créer les mocks
        $this->userServiceMock = $this->createMock(UserService::class);
        $this->authServiceMock = $this->createMock(AuthService::class);
        $this->mailerServiceMock = $this->createMock(MailerService::class);
        $this->userValidatorMock = $this->createMock(UserValidator::class);
        $this->loginValidatorMock = $this->createMock(LoginValidator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        // Instancier le contrôleur avec les mocks
        $this->authController = new AuthController(
            $this->userServiceMock,
            $this->authServiceMock,
            $this->mailerServiceMock,
            $this->loggerMock,
            $this->config,
            $this->userValidatorMock,
            $this->loginValidatorMock
        );
    }

    // ==========================================
    // TESTS POUR register()
    // ==========================================

    public function test_register_success_with_valid_data(): void
    {
        // Arrange
        $inputData = [
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'email' => 'jean.dupont@test.com',
            'password' => 'Password123',
            'phone' => '0123456789',
            'address' => '123 Rue Test',
            'city' => 'Paris',
            'postalCode' => '75001'
        ];

        $request = Request::createFromJson($inputData);

        // Mock: la validation réussit (pas d'erreurs)
        $this->userValidatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($inputData)
            ->willReturn(['isValid' => true, 'errors' => []]);

        // Mock: hashPassword retourne un hash
        $this->authServiceMock
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->equalTo('Password123'))
            ->willReturn('$2y$10$hashedpassword');

        // Mock: createUser retourne un ID utilisateur
        $this->userServiceMock
            ->expects($this->once())
            ->method('createUser')
            ->willReturn(42);

        // Mock: generateToken retourne un JWT
        $this->authServiceMock
            ->expects($this->once())
            ->method('generateToken')
            ->with($this->equalTo(42), $this->equalTo('UTILISATEUR'))
            ->willReturn('fake-jwt-token');

        // Mock: sendWelcomeEmail réussit
        $this->mailerServiceMock
            ->expects($this->once())
            ->method('sendWelcomeEmail')
            ->with($this->equalTo('jean.dupont@test.com'), $this->equalTo('Jean'))
            ->willReturn(true);

        // Act
        $response = $this->authController->register($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($result['success']);
        $this->assertEquals(42, $result['userId']);
        $this->assertTrue($result['emailSent']);
        $this->assertStringContainsString('réussie', $result['message']);
    }

    public function test_register_fails_with_invalid_data(): void
    {
        // Arrange - Données null (simule JSON invalide)
        $request = Request::createFromJson(null);

        // Act
        $response = $this->authController->register($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('invalides', $result['message']);
    }

    public function test_register_fails_with_validation_errors(): void
    {
        // Arrange - Données invalides (email manquant)
        $inputData = [
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'password' => 'Password123'
            // email manquant
        ];

        $request = Request::createFromJson($inputData);

        // Mock: la validation échoue
        $this->userValidatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($inputData)
            ->willReturn(['isValid' => false, 'errors' => ['email' => 'Le champ email est requis.']]);

        // Act
        $response = $this->authController->register($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('email', $result['errors']);
    }

    public function test_register_fails_when_email_already_exists(): void
    {
        // Arrange
        $inputData = [
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'email' => 'exists@test.com',
            'password' => 'Password123',
            'phone' => '0123456789',
            'address' => '123 Rue Test',
            'city' => 'Paris',
            'postalCode' => '75001'
        ];

        $request = Request::createFromJson($inputData);

        // Mock: la validation réussit
        $this->userValidatorMock
            ->expects($this->once())
            ->method('validate')
            ->willReturn(['isValid' => true, 'errors' => []]);

        // Mock: hashPassword
        $this->authServiceMock
            ->method('hashPassword')
            ->willReturn('$2y$10$hashedpassword');

        // Mock: createUser lève une exception EMAIL_EXISTS
        $this->userServiceMock
            ->expects($this->once())
            ->method('createUser')
            ->willThrowException(UserServiceException::emailExists());

        // Act
        $response = $this->authController->register($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('déjà utilisé', $result['message']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('email', $result['errors']);
    }

    public function test_register_success_even_if_email_sending_fails(): void
    {
        // Arrange
        $inputData = [
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'email' => 'jean@test.com',
            'password' => 'Password123',
            'phone' => '0123456789',
            'address' => '123 Rue Test',
            'city' => 'Paris',
            'postalCode' => '75001'
        ];

        $request = Request::createFromJson($inputData);

        $this->userValidatorMock->method('validate')->willReturn(['isValid' => true, 'errors' => []]);
        $this->authServiceMock->method('hashPassword')->willReturn('$2y$10$hash');
        $this->userServiceMock->method('createUser')->willReturn(42);
        $this->authServiceMock->method('generateToken')->willReturn('token');

        // Mock: sendWelcomeEmail échoue
        $this->mailerServiceMock
            ->expects($this->once())
            ->method('sendWelcomeEmail')
            ->willReturn(false);

        // Act
        $response = $this->authController->register($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($result['success']);
        $this->assertEquals(42, $result['userId']);
        $this->assertFalse($result['emailSent']);
        $this->assertStringContainsString("n'a pas pu être envoyé", $result['message']);
    }

    // ==========================================
    // TESTS POUR login()
    // ==========================================

    public function test_login_success_with_valid_credentials(): void
    {
        // Arrange
        $inputData = [
            'email' => 'user@test.com',
            'password' => 'ValidPassword123'
        ];

        $request = Request::createFromJson($inputData);

        $user = [
            'id' => 10,
            'email' => 'user@test.com',
            'passwordHash' => '$2y$10$validhash',
            'role' => 'CLIENT'
        ];

        // Mock: la validation réussit
        $this->loginValidatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($inputData)
            ->willReturn(['isValid' => true, 'errors' => []]);

        // Mock: findByEmail retourne l'utilisateur
        $this->userServiceMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($this->equalTo('user@test.com'))
            ->willReturn($user);

        // Mock: verifyPassword réussit (pas d'exception)
        $this->authServiceMock
            ->expects($this->once())
            ->method('verifyPassword')
            ->with($this->equalTo('ValidPassword123'), $this->equalTo('$2y$10$validhash'));

        // Mock: generateToken retourne un JWT
        $this->authServiceMock
            ->expects($this->once())
            ->method('generateToken')
            ->with($this->equalTo(10), $this->equalTo('CLIENT'))
            ->willReturn('jwt-token');

        // Act
        $response = $this->authController->login($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['userId']);
        $this->assertStringContainsString('réussie', $result['message']);
    }

    public function test_login_fails_with_invalid_data(): void
    {
        // Arrange - Données null (simule JSON invalide)
        $request = Request::createFromJson(null);

        // Act
        $response = $this->authController->login($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('invalides', $result['message']);
    }

    public function test_login_fails_with_validation_errors(): void
    {
        // Arrange - Email invalide
        $inputData = [
            'email' => 'invalid-email',
            'password' => 'password'
        ];

        $request = Request::createFromJson($inputData);

        // Mock: la validation échoue
        $this->loginValidatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($inputData)
            ->willReturn(['isValid' => false, 'errors' => ['email' => 'Le format de l\'email est invalide.']]);

        // Act
        $response = $this->authController->login($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
    }

    public function test_login_fails_when_email_not_exists_with_timing_attack_protection(): void
    {
        // Arrange
        $inputData = [
            'email' => 'nonexistent@test.com',
            'password' => 'SomePassword123'
        ];

        $request = Request::createFromJson($inputData);

        // Mock: la validation réussit
        $this->loginValidatorMock->method('validate')->willReturn(['isValid' => true, 'errors' => []]);

        // Mock: findByEmail retourne null (utilisateur non trouvé)
        $this->userServiceMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($this->equalTo('nonexistent@test.com'))
            ->willReturn(null);

        // Mock: verifyPassword est appelé même si l'utilisateur n'existe pas (timing attack protection)
        // Il devrait être appelé avec un hash factice. On simule l'exception qu'il lèverait.
        $this->authServiceMock
            ->expects($this->once())
            ->method('verifyPassword')
            ->with($this->equalTo('SomePassword123'), $this->equalTo('$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG'))
            ->willThrowException(InvalidCredentialsException::invalidCredentials());

        // Le logger devrait enregistrer la tentative
        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                $this->equalTo('Tentative de connexion avec email inexistant'),
                $this->arrayHasKey('email')
            );

        // Act
        $response = $this->authController->login($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('incorrect', $result['message']);
    }

    public function test_login_fails_when_password_is_incorrect(): void
    {
        // Arrange
        $inputData = [
            'email' => 'user@test.com',
            'password' => 'WrongPassword'
        ];

        $request = Request::createFromJson($inputData);

        $user = [
            'id' => 10,
            'email' => 'user@test.com',
            'passwordHash' => '$2y$10$validhash',
            'role' => 'CLIENT'
        ];

        // Mock: la validation réussit
        $this->loginValidatorMock->method('validate')->willReturn(['isValid' => true, 'errors' => []]);

        // Mock: findByEmail retourne l'utilisateur
        $this->userServiceMock
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        // Mock: verifyPassword lève une exception (mot de passe incorrect)
        $this->authServiceMock
            ->expects($this->once())
            ->method('verifyPassword')
            ->with($this->equalTo('WrongPassword'), $this->equalTo('$2y$10$validhash'))
            ->willThrowException(InvalidCredentialsException::invalidCredentials());

        // Act
        $response = $this->authController->login($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('incorrect', $result['message']);
    }

    public function test_login_returns_generic_error_on_unexpected_exception(): void
    {
        // Arrange
        $inputData = [
            'email' => 'user@test.com',
            'password' => 'Password123'
        ];

        $request = Request::createFromJson($inputData);

        // Mock: la validation réussit
        $this->loginValidatorMock->method('validate')->willReturn(['isValid' => true, 'errors' => []]);

        // Mock: findByEmail lève une exception inattendue
        $this->userServiceMock
            ->expects($this->once())
            ->method('findByEmail')
            ->willThrowException(new \Exception('Database connection error'));

        // Le logger devrait enregistrer l'erreur
        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Erreur lors de la connexion'),
                $this->anything()
            );

        // Act
        $response = $this->authController->login($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('erreur est survenue', $result['message']);
    }

    // ==========================================
    // TESTS POUR logout()
    // ==========================================

    public function test_logout_returns_success(): void
    {
        // Arrange
        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('Utilisateur déconnecté avec succès'));

        // Act
        $response = $this->authController->logout();
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Déconnexion réussie', $result['message']);
    }

    // ==========================================
    // TESTS POUR checkAuth()
    // ==========================================

    public function test_checkAuth_returns_authenticated_when_user_present(): void
    {
        // Arrange
        $request = new Request();
        
        $tokenData = (object)[
            'sub' => 25,
            'role' => 'ADMIN'
        ];

        $request->setAttribute('user', $tokenData);

        // Mock: getUserById retourne l'utilisateur
        $this->userServiceMock
            ->expects($this->once())
            ->method('getUserById')
            ->with($this->equalTo(25))
            ->willReturn([
                'id' => 25,
                'email' => 'admin@test.com',
                'prenom' => 'Admin',
                'nom' => 'User',
                'role' => 'ADMIN'
            ]);

        // Act
        $response = $this->authController->checkAuth($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($result['isAuthenticated']);
        $this->assertEquals(25, $result['user']['id']);
        $this->assertEquals('ADMIN', $result['user']['role']);
    }

    public function test_checkAuth_returns_not_authenticated_when_user_missing(): void
    {
        // Arrange
        $request = new Request();
        $request->setAttribute('user', null);

        // Le logger devrait enregistrer l'erreur
        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains("checkAuth atteint sans attribut 'user'"));

        // Act
        $response = $this->authController->checkAuth($request);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($result['isAuthenticated']);
    }
}
