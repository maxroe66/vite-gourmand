<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\AuthService;
use App\Services\MailerService;
use App\Repositories\UserRepository;
use App\Repositories\ResetTokenRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private LoggerInterface $logger;
    private UserRepository $userRepository;
    private ResetTokenRepository $resetTokenRepository;
    private MailerService $mailerService;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../config/config.php';

        // Mock du logger pour les tests
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->resetTokenRepository = $this->createMock(ResetTokenRepository::class);
        $this->mailerService = $this->createMock(MailerService::class);

        $this->authService = new AuthService(
            $config, 
            $this->logger,
            $this->userRepository,
            $this->resetTokenRepository,
            $this->mailerService
        );
    }

    public function testGenerateToken(): void
    {
        // Debug : afficher le secret chargé
        $config = require __DIR__ . '/../config/config.php';
        $secret = $config['jwt']['secret'];
        echo "\n🔍 JWT_SECRET chargé: " . $secret . " (longueur: " . strlen($secret) . " bytes)\n";

        // Générer un token
        $token = $this->authService->generateToken(123, 'client');

        // Vérifier que le token n'est pas vide
        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        echo "🔑 Token généré: " . substr($token, 0, 50) . "...\n";
    }

    public function testTokenCanBeDecoded(): void
    {
        // Générer un token
        $userId = 456;
        $role = 'admin';
        $token = $this->authService->generateToken($userId, $role);

        // Charger la config pour obtenir le secret
        $config = require __DIR__ . '/../config/config.php';
        $secret = $config['jwt']['secret'];

        // Décoder le token
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));

        // Vérifier le contenu
        $this->assertEquals($userId, $decoded->sub);
        $this->assertEquals($role, $decoded->role);
        $this->assertEquals('vite-gourmand', $decoded->iss);
        $this->assertGreaterThan(time() - 5, $decoded->iat); // émis il y a moins de 5 secondes
        $this->assertGreaterThan(time(), $decoded->exp); // pas encore expiré

        echo "\n✅ Token décodé avec succès:\n";
        echo "   - User ID: {$decoded->sub}\n";
        echo "   - Role: {$decoded->role}\n";
        echo "   - Émis à: " . date('Y-m-d H:i:s', $decoded->iat) . "\n";
        echo "   - Expire à: " . date('Y-m-d H:i:s', $decoded->exp) . "\n";
    }

    public function testRequestPasswordReset_Success(): void
    {
        $email = 'test@example.com';
        $user = ['id' => 1, 'email' => $email, 'prenom' => 'Jean'];

        // 1. Mock UserRepository : trouve l'utilisateur
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        // 2. Mock ResetTokenRepository : crée le token
        $this->resetTokenRepository->expects($this->once())
            ->method('create')
            ->with($this->equalTo(1), $this->isType('string'), $this->isType('string'));

        // 3. Mock MailerService : envoie l'email
        $this->mailerService->expects($this->once())
            ->method('sendPasswordResetEmail')
            ->with($email, $this->isType('string'), 'Jean')
            ->willReturn(true);

        $result = $this->authService->requestPasswordReset($email);
        $this->assertTrue($result);
    }

    public function testRequestPasswordReset_UserNotFound(): void
    {
        $email = 'unknown@example.com';

        // Mock UserRepository : ne trouve pas l'utilisateur
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        // Les autres repositories ne doivent PAS être appelés
        $this->resetTokenRepository->expects($this->never())->method('create');
        $this->mailerService->expects($this->never())->method('sendPasswordResetEmail');

        $result = $this->authService->requestPasswordReset($email);
        
        // Doit retourner true quand même (security by obscurity)
        $this->assertTrue($result);
    }

    public function testResetPassword_Success(): void
    {
        $token = 'valid_token';
        $newPassword = 'newPassword123';
        $tokenData = ['id_token' => 10, 'id_utilisateur' => 1];

        // 1. Mock ResetTokenRepository : trouve le token
        $this->resetTokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($tokenData);

        // 2. Mock UserRepository : met à jour le mot de passe
        $this->userRepository->expects($this->once())
            ->method('updatePassword')
            ->with($this->equalTo(1), $this->isType('string'));

        // 3. Mock ResetTokenRepository : marque le token comme utilisé
        $this->resetTokenRepository->expects($this->once())
            ->method('markAsUsed')
            ->with(10);

        $result = $this->authService->resetPassword($token, $newPassword);
        $this->assertTrue($result);
    }

    public function testResetPassword_InvalidToken(): void
    {
        $token = 'invalid_token';
        $newPassword = 'newPassword123';

        // Mock ResetTokenRepository : ne trouve pas le token
        $this->resetTokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Token invalide ou expiré');

        $this->authService->resetPassword($token, $newPassword);
    }

    public function testHashPassword(): void
    {
        $password = 'MonMotDePasse123!';
        $hash = $this->authService->hashPassword($password);

        $this->assertNotEmpty($hash);
        $this->assertStringStartsWith('$argon2id$', $hash); // Argon2ID (recommandé OWASP)
        $this->assertNotEquals($password, $hash);

        echo "\n🔒 Password hashé: " . substr($hash, 0, 30) . "...\n";
    }

    public function test_verifyPassword_success(): void
    {
        // Arrange
        $password = 'SecurePass123!';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Act & Assert - Pas d'exception = succès
        $this->authService->verifyPassword($password, $hash);
        
        // Si on arrive ici, c'est que aucune exception n'a été levée ✅
        $this->assertTrue(true);
    }

    public function test_verifyPassword_throwsException_when_password_invalid(): void
    {
        // Arrange
        $correctPassword = 'SecurePass123!';
        $wrongPassword = 'WrongPassword';
        $hash = password_hash($correctPassword, PASSWORD_DEFAULT);

        // Assert - On s'attend à une exception
        $this->expectException(\App\Exceptions\InvalidCredentialsException::class);
        
        // Act
        $this->authService->verifyPassword($wrongPassword, $hash);
    }
}
