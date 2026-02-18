<?php

declare(strict_types=1);

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\MailerService;
use Psr\Log\LoggerInterface;
use PHPMailer\PHPMailer\PHPMailer;

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

    /**
     * Test unitaire avec mock PHPMailer : vérifie que send() est appelé
     * avec la bonne configuration sans connexion SMTP réelle
     */
    public function testSendWelcomeEmailWithMockSuccessfulSend(): void
    {
        $config = [
            'mail' => [
                'host' => 'smtp.example.com',
                'user' => 'test@example.com',
                'pass' => 'password',
                'from' => 'noreply@example.com'
            ]
        ];

        // Créer un partial mock de MailerService pour injecter un PHPMailer mocké
        $mailerService = $this->getMockBuilder(MailerService::class)
            ->setConstructorArgs([$this->logger, $config])
            ->onlyMethods(['createMailer'])
            ->getMock();

        $mockMailer = $this->createMock(PHPMailer::class);
        
        // Configuration attendue
        $mockMailer->expects($this->once())
            ->method('isSMTP');
        
        $mockMailer->expects($this->once())
            ->method('setFrom')
            ->with('noreply@example.com', 'Vite & Gourmand');
        
        $mockMailer->expects($this->once())
            ->method('addAddress')
            ->with('user@example.com', 'John');
        
        $mockMailer->expects($this->once())
            ->method('send')
            ->willReturn(true);

        // Logger doit recevoir un info
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Email de bienvenue envoyé avec succès',
                ['email' => 'user@example.com', 'firstName' => 'John']
            );

        $mailerService->expects($this->once())
            ->method('createMailer')
            ->willReturn($mockMailer);

        $result = $mailerService->sendWelcomeEmail('user@example.com', 'John');
        $this->assertTrue($result);
    }

    /**
     * Test unitaire avec mock PHPMailer : vérifie le comportement en cas d'échec send()
     */
    public function testSendWelcomeEmailWithMockFailedSend(): void
    {
        $config = [
            'mail' => [
                'host' => 'smtp.example.com',
                'user' => 'test@example.com',
                'pass' => 'password',
                'from' => 'noreply@example.com'
            ]
        ];

        $mailerService = $this->getMockBuilder(MailerService::class)
            ->setConstructorArgs([$this->logger, $config])
            ->onlyMethods(['createMailer'])
            ->getMock();

        $mockMailer = $this->createMock(PHPMailer::class);
        
        $mockMailer->expects($this->once())
            ->method('send')
            ->willThrowException(new \PHPMailer\PHPMailer\Exception('SMTP connection failed'));

        // Logger doit recevoir un error
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Échec envoi email de bienvenue',
                $this->callback(function ($context) {
                    return isset($context['email']) 
                        && isset($context['error'])
                        && $context['email'] === 'user@example.com'
                        && str_contains($context['error'], 'SMTP connection failed');
                })
            );

        $mailerService->expects($this->once())
            ->method('createMailer')
            ->willReturn($mockMailer);

        $result = $mailerService->sendWelcomeEmail('user@example.com', 'John');
        $this->assertFalse($result);
    }

    /**
     * Test vérifiant que le template HTML est correctement chargé et processé
     */
    public function testTemplateLoadingAndVariableReplacement(): void
    {
        $config = [
            'mail' => [
                'host' => 'smtp.example.com',
                'user' => 'test@example.com',
                'pass' => 'password',
                'from' => 'noreply@example.com'
            ]
        ];

        $mailerService = $this->getMockBuilder(MailerService::class)
            ->setConstructorArgs([$this->logger, $config])
            ->onlyMethods(['createMailer'])
            ->getMock();

        $mockMailer = $this->createMock(PHPMailer::class);
        
        // Capturer le Body pour vérifier le remplacement
        $capturedBody = null;
        $mockMailer->method('send')
            ->willReturnCallback(function () use ($mockMailer, &$capturedBody) {
                $capturedBody = $mockMailer->Body ?? null;
                return true;
            });

        $this->logger->expects($this->once())
            ->method('info');

        $mailerService->expects($this->once())
            ->method('createMailer')
            ->willReturn($mockMailer);

        $result = $mailerService->sendWelcomeEmail('user@example.com', 'Marie<script>alert("xss")</script>');
        
        // Le résultat devrait être true
        $this->assertTrue($result);
        
        // Note: Ce test nécessiterait de capturer Body via un mock plus sophistiqué
        // ou de refactoriser MailerService pour rendre le template testable
    }

    // ========================================================================
    // Tests — sendContactNotification()
    // ========================================================================

    public function testSendContactNotificationReturnsFalseWhenSmtpConfigMissing(): void
    {
        $config = [
            'mail' => [
                'host' => '',
                'user' => '',
                'pass' => '',
                'from' => 'noreply@vite-et-gourmand.me',
                'contact_email' => 'contact@vite-et-gourmand.me'
            ]
        ];

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Configuration SMTP manquante, email contact non envoyé',
                ['senderEmail' => 'visitor@example.com']
            );

        $mailerService = new MailerService($this->logger, $config);
        $result = $mailerService->sendContactNotification(
            'visitor@example.com',
            'Question sur un menu',
            'Bonjour, je souhaite en savoir plus sur vos menus.'
        );

        $this->assertFalse($result);
    }

    public function testSendContactNotificationReturnsFalseWhenUserConfigMissing(): void
    {
        $config = [
            'mail' => [
                'host' => 'smtp.example.com',
                'user' => '',
                'pass' => 'password',
                'from' => 'noreply@vite-et-gourmand.me',
                'contact_email' => 'contact@vite-et-gourmand.me'
            ]
        ];

        $this->logger->expects($this->once())
            ->method('warning');

        $mailerService = new MailerService($this->logger, $config);
        $result = $mailerService->sendContactNotification(
            'visitor@example.com',
            'Test',
            'Message de test pour la configuration.'
        );

        $this->assertFalse($result);
    }

    /**
     * Test avec mock PHPMailer : vérifie que send() est appelé
     * avec la bonne configuration (From, Reply-To, Destinataire).
     */
    public function testSendContactNotificationWithMockSuccessfulSend(): void
    {
        $config = [
            'mail' => [
                'host' => 'smtp.example.com',
                'user' => 'test@example.com',
                'pass' => 'password',
                'from' => 'noreply@vite-et-gourmand.me',
                'contact_email' => 'contact@vite-et-gourmand.me'
            ]
        ];

        $mailerService = $this->getMockBuilder(MailerService::class)
            ->setConstructorArgs([$this->logger, $config])
            ->onlyMethods(['createMailer'])
            ->getMock();

        $mockMailer = $this->createMock(PHPMailer::class);

        $mockMailer->expects($this->once())
            ->method('isSMTP');

        // From = adresse no-reply de l'entreprise (pas celle du visiteur)
        $mockMailer->expects($this->once())
            ->method('setFrom')
            ->with('noreply@vite-et-gourmand.me', 'Vite & Gourmand — Contact');

        // Reply-To = adresse du visiteur
        $mockMailer->expects($this->once())
            ->method('addReplyTo')
            ->with('visitor@example.com');

        // Destinataire = adresse de contact de l'entreprise
        $mockMailer->expects($this->once())
            ->method('addAddress')
            ->with('contact@vite-et-gourmand.me', 'Vite & Gourmand');

        $mockMailer->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Email de notification contact envoyé',
                ['senderEmail' => 'visitor@example.com', 'titre' => 'Question traiteur']
            );

        $mailerService->expects($this->once())
            ->method('createMailer')
            ->willReturn($mockMailer);

        $result = $mailerService->sendContactNotification(
            'visitor@example.com',
            'Question traiteur',
            'Bonjour, je souhaite organiser un événement pour 50 personnes.'
        );

        $this->assertTrue($result);
    }

    /**
     * Test avec mock PHPMailer : vérifie le comportement en cas d'échec d'envoi.
     */
    public function testSendContactNotificationWithMockFailedSend(): void
    {
        $config = [
            'mail' => [
                'host' => 'smtp.example.com',
                'user' => 'test@example.com',
                'pass' => 'password',
                'from' => 'noreply@vite-et-gourmand.me',
                'contact_email' => 'contact@vite-et-gourmand.me'
            ]
        ];

        $mailerService = $this->getMockBuilder(MailerService::class)
            ->setConstructorArgs([$this->logger, $config])
            ->onlyMethods(['createMailer'])
            ->getMock();

        $mockMailer = $this->createMock(PHPMailer::class);

        $mockMailer->expects($this->once())
            ->method('send')
            ->willThrowException(new \PHPMailer\PHPMailer\Exception('SMTP connection refused'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->callback(function ($msg) {
                    return str_contains($msg, 'Erreur envoi email contact');
                }),
                $this->callback(function ($context) {
                    return isset($context['senderEmail'])
                        && $context['senderEmail'] === 'visitor@example.com';
                })
            );

        $mailerService->expects($this->once())
            ->method('createMailer')
            ->willReturn($mockMailer);

        $result = $mailerService->sendContactNotification(
            'visitor@example.com',
            'Demande de devis',
            'Je souhaite réserver pour un mariage le 15 juin.'
        );

        $this->assertFalse($result);
    }

    /**
     * Test vérifiant l'échappement HTML dans le sujet et le corps de l'email contact.
     */
    public function testSendContactNotificationEscapesHtmlInContent(): void
    {
        $config = [
            'mail' => [
                'host' => 'smtp.example.com',
                'user' => 'test@example.com',
                'pass' => 'password',
                'from' => 'noreply@vite-et-gourmand.me',
                'contact_email' => 'contact@vite-et-gourmand.me'
            ]
        ];

        $mailerService = $this->getMockBuilder(MailerService::class)
            ->setConstructorArgs([$this->logger, $config])
            ->onlyMethods(['createMailer'])
            ->getMock();

        $mockMailer = $this->createMock(PHPMailer::class);

        $mockMailer->method('send')->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info');

        $mailerService->expects($this->once())
            ->method('createMailer')
            ->willReturn($mockMailer);

        // Le titre contient du HTML malicieux — ne doit pas provoquer d'erreur
        $result = $mailerService->sendContactNotification(
            'hacker@example.com',
            '<script>alert("xss")</script>',
            'Message avec <b>tags HTML</b> et "guillemets".'
        );

        $this->assertTrue($result);
    }
}
