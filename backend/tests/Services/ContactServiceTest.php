<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ContactService;
use App\Services\MailerService;
use App\Repositories\ContactRepository;
use App\Validators\ContactValidator;
use Psr\Log\LoggerInterface;

class ContactServiceTest extends TestCase
{
    private ContactService $service;
    private $contactRepo;
    private $contactValidator;
    private $mailerService;
    private $logger;
    private array $validData;

    protected function setUp(): void
    {
        $this->contactRepo = $this->createMock(ContactRepository::class);
        $this->contactValidator = $this->createMock(ContactValidator::class);
        $this->mailerService = $this->createMock(MailerService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new ContactService(
            $this->contactRepo,
            $this->contactValidator,
            $this->mailerService,
            $this->logger
        );

        $this->validData = [
            'titre' => 'Demande de devis',
            'email' => 'visiteur@example.com',
            'description' => 'Bonjour, je souhaite un devis pour un repas de 30 personnes.'
        ];
    }

    // ── Tests soumission réussie ──

    public function testSubmitContactSuccess(): void
    {
        $this->contactValidator->method('validate')
            ->willReturn(['isValid' => true, 'errors' => []]);

        $this->contactRepo->expects($this->once())
            ->method('create')
            ->with('Demande de devis', 'Bonjour, je souhaite un devis pour un repas de 30 personnes.', 'visiteur@example.com')
            ->willReturn(42);

        $this->mailerService->expects($this->once())
            ->method('sendContactNotification')
            ->with('visiteur@example.com', 'Demande de devis', 'Bonjour, je souhaite un devis pour un repas de 30 personnes.')
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Message de contact enregistré', $this->callback(function ($context) {
                return $context['id'] === 42 && $context['email'] === 'visiteur@example.com';
            }));

        $result = $this->service->submitContact($this->validData);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('bien été envoyé', $result['message']);
    }

    // ── Tests validation échouée ──

    public function testSubmitContactValidationFailure(): void
    {
        $expectedErrors = [
            'titre' => 'Le titre est requis.',
            'email' => "L'adresse email est requise."
        ];

        $this->contactValidator->method('validate')
            ->willReturn(['isValid' => false, 'errors' => $expectedErrors]);

        // Le repository ne doit PAS être appelé si la validation échoue
        $this->contactRepo->expects($this->never())->method('create');
        $this->mailerService->expects($this->never())->method('sendContactNotification');

        $result = $this->service->submitContact([]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertCount(2, $result['errors']);
    }

    // ── Tests erreur base de données ──

    public function testSubmitContactDatabaseError(): void
    {
        $this->contactValidator->method('validate')
            ->willReturn(['isValid' => true, 'errors' => []]);

        $this->contactRepo->method('create')
            ->willThrowException(new \PDOException('Connection refused'));

        // L'email ne doit PAS être envoyé si la BDD échoue
        $this->mailerService->expects($this->never())->method('sendContactNotification');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('enregistrement du message'),
                $this->callback(function ($ctx) {
                    return $ctx['error'] === 'Connection refused';
                })
            );

        $result = $this->service->submitContact($this->validData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('erreur', $result['message']);
    }

    // ── Tests email échoué (non bloquant) ──

    public function testSubmitContactEmailFailureIsNotBlocking(): void
    {
        $this->contactValidator->method('validate')
            ->willReturn(['isValid' => true, 'errors' => []]);

        $this->contactRepo->method('create')->willReturn(7);

        $this->mailerService->method('sendContactNotification')
            ->willThrowException(new \Exception('SMTP timeout'));

        // Le warning doit être logué
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('non envoyé'),
                $this->callback(function ($ctx) {
                    return $ctx['contactId'] === 7 && str_contains($ctx['error'], 'SMTP');
                })
            );

        // La soumission doit quand même réussir (le message est en BDD)
        $result = $this->service->submitContact($this->validData);

        $this->assertTrue($result['success']);
    }

    // ── Tests données trimées ──

    public function testSubmitContactTrimsData(): void
    {
        $data = [
            'titre' => '  Demande avec espaces  ',
            'email' => '  visiteur@example.com  ',
            'description' => '  Description avec espaces partout  '
        ];

        $this->contactValidator->method('validate')
            ->willReturn(['isValid' => true, 'errors' => []]);

        $this->contactRepo->expects($this->once())
            ->method('create')
            ->with(
                'Demande avec espaces',
                'Description avec espaces partout',
                'visiteur@example.com'
            )
            ->willReturn(1);

        $this->mailerService->method('sendContactNotification')->willReturn(true);

        $result = $this->service->submitContact($data);

        $this->assertTrue($result['success']);
    }
}
