<?php

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\AvisController;
use App\Services\AvisService;
use App\Core\Request;

class AvisControllerTest extends TestCase
{
    private AvisController $controller;
    private $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(AvisService::class);
        $this->controller = new AvisController($this->service);
    }

    public function testListDefaultsToValidatedForPublic(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getAttribute')->with('user')->willReturn(null);

        $avis = $this->makeAvis(1, 'VALIDE', 5, 'OK', '2024-01-01 00:00:00', 10, 20);
        $this->service->expects($this->once())
            ->method('getAvis')
            ->with('VALIDE')
            ->willReturn([$avis]);

        $response = $this->controller->list($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);
        $payload = $data['data'][0];
        $this->assertSame('VALIDE', $payload['statut']);
        $this->assertArrayNotHasKey('commande_id', $payload);
        $this->assertArrayNotHasKey('user_id', $payload);
    }

    public function testListPendingRequiresAuthentication(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getQueryParams')->willReturn(['status' => 'EN_ATTENTE']);
        $request->method('getAttribute')->with('user')->willReturn(null);

        $response = $this->controller->list($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testListPendingWithAdminIsAllowed(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getQueryParams')->willReturn(['status' => 'EN_ATTENTE']);
        $request->method('getAttribute')->with('user')->willReturn((object)['role' => 'ADMINISTRATEUR']);

        $avis = $this->makeAvis(4, 'EN_ATTENTE', 5, 'En attente', '2024-02-02 10:00:00', 11, 22);
        $this->service->expects($this->once())
            ->method('getAvis')
            ->with('EN_ATTENTE')
            ->willReturn([$avis]);

        $response = $this->controller->list($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $payload = $data['data'][0];
        $this->assertSame('EN_ATTENTE', $payload['statut']);
        $this->assertArrayHasKey('commande_id', $payload);
        $this->assertArrayHasKey('user_id', $payload);
    }

    private function makeAvis(int $id, string $statut, int $note, string $commentaire, string $date, int $commandeId, int $userId): object
    {
        return (object) [
            'id' => $id,
            'note' => $note,
            'commentaire' => $commentaire,
            'dateAvis' => $date,
            'statutValidation' => $statut,
            'commandeId' => $commandeId,
            'userId' => $userId,
        ];
    }
}
