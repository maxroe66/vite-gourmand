<?php

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\HoraireController;
use App\Repositories\HoraireRepository;
use App\Validators\HoraireValidator;
use App\Models\Horaire;
use App\Core\Request;
use App\Core\Response;

/**
 * Tests unitaires pour HoraireController.
 * Couvre : index (public), update (protégé EMPLOYE/ADMINISTRATEUR).
 */
class HoraireControllerTest extends TestCase
{
    private HoraireController $controller;
    private $repository;
    private $validator;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(HoraireRepository::class);
        $this->validator = $this->createMock(HoraireValidator::class);
        $this->controller = new HoraireController($this->repository, $this->validator);
    }

    /**
     * Crée un objet Horaire de test.
     */
    private function makeHoraire(int $id, string $jour, ?string $ouverture = '09:00', ?string $fermeture = '18:00', bool $ferme = false): Horaire
    {
        return new Horaire([
            'id_horaire' => $id,
            'jour' => $jour,
            'heure_ouverture' => $ouverture,
            'heure_fermeture' => $fermeture,
            'ferme' => $ferme,
        ]);
    }

    // ==========================================
    // TESTS POUR index()
    // ==========================================

    public function testIndexReturnsAllHoraires(): void
    {
        $request = $this->createMock(Request::class);

        $horaires = [
            $this->makeHoraire(1, 'LUNDI'),
            $this->makeHoraire(2, 'MARDI'),
            $this->makeHoraire(3, 'DIMANCHE', null, null, true),
        ];

        $this->repository->method('findAll')->willReturn($horaires);

        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(3, $data['data']);
        $this->assertEquals('LUNDI', $data['data'][0]['jour']);
        $this->assertFalse($data['data'][0]['ferme']);
        $this->assertTrue($data['data'][2]['ferme']);
    }

    public function testIndexReturnsEmptyArray(): void
    {
        $request = $this->createMock(Request::class);
        $this->repository->method('findAll')->willReturn([]);

        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(0, $data['data']);
    }

    // ==========================================
    // TESTS POUR update()
    // ==========================================

    public function testUpdateSuccessForAdmin(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);
        $request->method('getJsonBody')->willReturn([
            'ferme' => false,
            'heureOuverture' => '10:00',
            'heureFermeture' => '20:00',
        ]);

        $existing = $this->makeHoraire(1, 'LUNDI');
        $updated = $this->makeHoraire(1, 'LUNDI', '10:00', '20:00');

        $this->repository->method('findById')
            ->with(1)
            ->willReturnOnConsecutiveCalls($existing, $updated);

        $this->validator->method('validate')
            ->willReturn(['isValid' => true, 'errors' => []]);
        $this->repository->expects($this->once())->method('update');

        $response = $this->controller->update($request, ['id' => '1']);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('mis à jour', $data['message']);
        $this->assertEquals('10:00', $data['horaire']['heureOuverture']);
    }

    public function testUpdateSuccessForEmploye(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'EMPLOYE']);
        $request->method('getJsonBody')->willReturn([
            'ferme' => true,
        ]);

        $existing = $this->makeHoraire(7, 'DIMANCHE');
        $updated = $this->makeHoraire(7, 'DIMANCHE', null, null, true);

        $this->repository->method('findById')
            ->willReturnOnConsecutiveCalls($existing, $updated);
        $this->validator->method('validate')
            ->willReturn(['isValid' => true, 'errors' => []]);
        $this->repository->expects($this->once())->method('update');

        $response = $this->controller->update($request, ['id' => '7']);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['horaire']['ferme']);
    }

    public function testUpdateReturns403ForClient(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'UTILISATEUR']);

        $response = $this->controller->update($request, ['id' => '1']);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('interdit', $data['error']);
    }

    public function testUpdateReturns403WhenNotAuthenticated(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')->willReturn(null);

        $response = $this->controller->update($request, ['id' => '1']);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testUpdateReturns400WithInvalidId(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);

        $response = $this->controller->update($request, ['id' => '0']);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('invalide', $data['error']);
    }

    public function testUpdateReturns404WhenHoraireNotFound(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);

        $this->repository->method('findById')->with(99)->willReturn(null);

        $response = $this->controller->update($request, ['id' => '99']);

        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('non trouvé', $data['error']);
    }

    public function testUpdateReturns400WithNullBody(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);
        $request->method('getJsonBody')->willReturn(null);

        $this->repository->method('findById')->with(1)
            ->willReturn($this->makeHoraire(1, 'LUNDI'));

        $response = $this->controller->update($request, ['id' => '1']);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('invalides', $data['error']);
    }

    public function testUpdateReturns422WithValidationErrors(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);
        $request->method('getJsonBody')->willReturn([
            'ferme' => false,
            'heureOuverture' => '20:00',
            'heureFermeture' => '09:00',
        ]);

        $this->repository->method('findById')->with(1)
            ->willReturn($this->makeHoraire(1, 'LUNDI'));
        $this->validator->method('validate')
            ->willReturn([
                'isValid' => false,
                'errors' => ['heureFermeture' => "L'heure de fermeture doit être postérieure à l'heure d'ouverture."]
            ]);

        $response = $this->controller->update($request, ['id' => '1']);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('heureFermeture', $data['errors']);
    }
}
