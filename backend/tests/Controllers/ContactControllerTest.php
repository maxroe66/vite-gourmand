<?php

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\ContactController;
use App\Services\ContactService;
use App\Core\Request;

class ContactControllerTest extends TestCase
{
    private ContactController $controller;
    private $contactService;

    protected function setUp(): void
    {
        $this->contactService = $this->createMock(ContactService::class);
        $this->controller = new ContactController($this->contactService);
    }

    // ── Tests soumission réussie ──

    public function testSubmitReturns201OnSuccess(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getJsonBody')->willReturn([
            'titre' => 'Test',
            'email' => 'user@example.com',
            'description' => 'Ceci est un message de test assez long.'
        ]);

        $this->contactService->expects($this->once())
            ->method('submitContact')
            ->willReturn([
                'success' => true,
                'message' => 'Votre message a bien été envoyé.'
            ]);

        $response = $this->controller->submit($request);

        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('envoyé', $data['message']);
    }

    // ── Tests validation échouée ──

    public function testSubmitReturns422OnValidationError(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getJsonBody')->willReturn([
            'titre' => '',
            'email' => 'invalid',
            'description' => ''
        ]);

        $this->contactService->expects($this->once())
            ->method('submitContact')
            ->willReturn([
                'success' => false,
                'errors' => [
                    'titre' => 'Le titre est requis.',
                    'email' => "Le format de l'adresse email est invalide.",
                    'description' => 'Le message est requis.'
                ]
            ]);

        $response = $this->controller->submit($request);

        $this->assertSame(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertCount(3, $data['errors']);
    }

    // ── Tests erreur serveur ──

    public function testSubmitReturns500OnServerError(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getJsonBody')->willReturn([
            'titre' => 'Test',
            'email' => 'user@example.com',
            'description' => 'Un message valide pour test.'
        ]);

        $this->contactService->expects($this->once())
            ->method('submitContact')
            ->willReturn([
                'success' => false,
                'message' => 'Une erreur est survenue.'
            ]);

        $response = $this->controller->submit($request);

        $this->assertSame(500, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    // ── Tests body vide ──

    public function testSubmitReturns400OnEmptyBody(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getJsonBody')->willReturn(null);

        // Le service ne doit PAS être appelé si le body est vide
        $this->contactService->expects($this->never())->method('submitContact');

        $response = $this->controller->submit($request);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('invalide', $data['message']);
    }

    public function testSubmitReturns400OnEmptyArray(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getJsonBody')->willReturn([]);

        $this->contactService->expects($this->never())->method('submitContact');

        $response = $this->controller->submit($request);

        $this->assertSame(400, $response->getStatusCode());
    }
}
