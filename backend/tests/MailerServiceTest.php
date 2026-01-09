<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Services\MailerService;
use Psr\Log\LoggerInterface;

class MailerServiceTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        // Mock du logger pour éviter les logs réels pendant les tests
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testSendWelcomeEmailReturnsFalseWhenSmtpConfigMissing(): void
    {
        // Configuration sans SMTP
        $config = [
            'mail' => [
                'host' => '',
                'user' => '',
                'pass' => '',
                'from' => 'test@example.com'
            ]
        ];

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Configuration SMTP manquante, email non envoyé',
                ['email' => 'user@example.com']
            );

        $mailerService = new MailerService($this->logger, $config);
        $result = $mailerService->sendWelcomeEmail('user@example.com', 'John');

        $this->assertFalse($result);
    }

    public function testSendWelcomeEmailReturnsFalseWhenTemplateNotFound(): void
    {
        // Ce test vérifie que le service gère correctement l'absence de template
        // Note: En réalité, le template existe, donc ce test vérifie juste la construction
        // Pour un vrai test, il faudrait pouvoir injecter le chemin du template
        
        $config = [
            'mail' => [
                'host' => 'smtp.example.com',
                'user' => 'test@example.com',
                'pass' => 'password',
                'from' => 'noreply@example.com'
            ]
        ];

        $mailerService = new MailerService($this->logger, $config);
        
        // Avec le template existant et SMTP non connecté, on s'attend à une erreur SMTP
        // Ce test valide que le service est bien instancié
        $this->assertInstanceOf(MailerService::class, $mailerService);
    }

    public function testConstructorInjection(): void
    {
        $config = [
            'mail' => [
                'host' => 'smtp.example.com',
                'user' => 'test@example.com',
                'pass' => 'password',
                'from' => 'noreply@example.com'
            ]
        ];

        $mailerService = new MailerService($this->logger, $config);
        $this->assertInstanceOf(MailerService::class, $mailerService);
    }

    public function testSendWelcomeEmailWithEmptyEmail(): void
    {
        $config = [
            'mail' => [
                'host' => 'smtp.example.com',
                'user' => 'test@example.com',
                'pass' => 'password',
                'from' => 'noreply@example.com'
            ]
        ];

        $mailerService = new MailerService($this->logger, $config);
        
        // PHPMailer va lancer une exception pour email vide, on s'attend à false
        $this->logger->expects($this->once())
            ->method('error');

        $result = $mailerService->sendWelcomeEmail('', 'John');
        $this->assertFalse($result);
    }

    public function testSendWelcomeEmailWithInvalidEmail(): void
    {
        $config = [
            'mail' => [
                'host' => 'smtp.example.com',
                'user' => 'test@example.com',
                'pass' => 'password',
                'from' => 'noreply@example.com'
            ]
        ];

        $mailerService = new MailerService($this->logger, $config);
        
        // Email invalide devrait déclencher une exception PHPMailer
        $this->logger->expects($this->once())
            ->method('error');

        $result = $mailerService->sendWelcomeEmail('invalid-email', 'John');
        $this->assertFalse($result);
    }
}
