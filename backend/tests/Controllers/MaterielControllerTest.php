<?php

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\MaterielController;
use App\Repositories\MaterielRepository;
use App\Validators\MaterielValidator;
use App\Core\Request;
use App\Core\Response;

/**
 * Tests unitaires pour MaterielController.
 * Couvre : index, show, store, update, destroy.
 */
class MaterielControllerTest extends TestCase
{
    private MaterielController $controller;
    private $repository;
    private $validator;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(MaterielRepository::class);
        $this->validator = $this->createMock(MaterielValidator::class);
        $this->controller = new MaterielController($this->repository, $this->validator);
    }

    // ==========================================
    // TESTS POUR index()
    // ==========================================

    public function testIndexReturnsListForAdmin(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);

        $materiels = [
            ['id_materiel' => 1, 'libelle' => 'Chaise'],
            ['id_materiel' => 2, 'libelle' => 'Table'],
        ];
        $this->repository->method('findAll')->willReturn($materiels);

        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testIndexReturnsListForEmploye(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'EMPLOYE']);

        $this->repository->method('findAll')->willReturn([]);

        $response = $this->controller->index($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testIndexReturns401WhenNotAuthenticated(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')->willReturn(null);

        $response = $this->controller->index($request);

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('authentifié', $data['error']);
    }

    public function testIndexReturns403ForClient(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'UTILISATEUR']);

        $response = $this->controller->index($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('interdit', $data['error']);
    }

    // ==========================================
    // TESTS POUR show()
    // ==========================================

    public function testShowReturnsExistingMateriel(): void
    {
        $request = $this->createMock(Request::class);
        $materiel = ['id_materiel' => 1, 'libelle' => 'Chaise', 'valeur_unitaire' => 15.00];
        $this->repository->method('findById')->with(1)->willReturn($materiel);

        $response = $this->controller->show($request, ['id' => '1']);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Chaise', $data['libelle']);
    }

    public function testShowReturns404WhenNotFound(): void
    {
        $request = $this->createMock(Request::class);
        $this->repository->method('findById')->with(999)->willReturn(null);

        $response = $this->controller->show($request, ['id' => '999']);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testShowReturns400WithInvalidId(): void
    {
        $request = $this->createMock(Request::class);

        $response = $this->controller->show($request, ['id' => '0']);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('invalide', $data['error']);
    }

    // ==========================================
    // TESTS POUR store()
    // ==========================================

    public function testStoreCreatesWithValidData(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);

        $inputData = [
            'libelle' => 'Nappe blanche',
            'valeur_unitaire' => 12.00,
            'stock_disponible' => 50,
        ];
        $request->method('getJsonBody')->willReturn($inputData);

        $this->validator->method('validate')
            ->willReturn(['isValid' => true, 'errors' => []]);

        $this->repository->expects($this->once())
            ->method('create')
            ->willReturn(10);

        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(10, $data['id']);
        $this->assertStringContainsString('créé', $data['message']);
    }

    public function testStoreReturns403ForUnauthorizedRole(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'UTILISATEUR']);

        $response = $this->controller->store($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testStoreReturns400WithNullBody(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'EMPLOYE']);
        $request->method('getJsonBody')->willReturn(null);

        $response = $this->controller->store($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testStoreReturns422WithValidationErrors(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);
        $request->method('getJsonBody')->willReturn(['libelle' => '']);

        $this->validator->method('validate')
            ->willReturn(['isValid' => false, 'errors' => ['libelle' => 'Le libellé est requis.']]);

        $response = $this->controller->store($request);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('libelle', $data['errors']);
    }

    public function testStoreNormalizeCamelCaseKeys(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);

        // Données avec clés camelCase (envoyées par le frontend)
        $inputData = [
            'libelle' => 'Verre',
            'valeurUnitaire' => 2.50,
            'stockDisponible' => 200,
        ];
        $request->method('getJsonBody')->willReturn($inputData);

        $this->validator->method('validate')
            ->willReturn(['isValid' => true, 'errors' => []]);
        $this->repository->method('create')->willReturn(11);

        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
    }

    // ==========================================
    // TESTS POUR update()
    // ==========================================

    public function testUpdateModifiesExistingMateriel(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'EMPLOYE']);

        $inputData = ['libelle' => 'Chaise modifiée', 'valeur_unitaire' => 20.00, 'stock_disponible' => 80];
        $request->method('getJsonBody')->willReturn($inputData);

        $this->repository->method('findById')->with(1)->willReturn(['id_materiel' => 1]);
        $this->validator->method('validate')
            ->willReturn(['isValid' => true, 'errors' => []]);
        $this->repository->expects($this->once())->method('update');

        $response = $this->controller->update($request, ['id' => '1']);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('mis à jour', $data['message']);
    }

    public function testUpdateReturns404WhenNotFound(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);

        $this->repository->method('findById')->with(999)->willReturn(null);

        $response = $this->controller->update($request, ['id' => '999']);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testUpdateReturns403ForUnauthorizedRole(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'UTILISATEUR']);

        $response = $this->controller->update($request, ['id' => '1']);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testUpdateReturns400WithInvalidId(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);

        $response = $this->controller->update($request, ['id' => '-1']);

        $this->assertEquals(400, $response->getStatusCode());
    }

    // ==========================================
    // TESTS POUR destroy()
    // ==========================================

    public function testDestroyDeletesMateriel(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);

        $this->repository->expects($this->once())
            ->method('delete')
            ->with(5)
            ->willReturn(true);

        $response = $this->controller->destroy($request, ['id' => '5']);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('supprimé', $data['message']);
    }

    public function testDestroyReturns404WhenNotFound(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);

        $this->repository->method('delete')->with(999)->willReturn(false);

        $response = $this->controller->destroy($request, ['id' => '999']);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDestroyReturns409WhenMaterielHasActiveLoans(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);

        $this->repository->method('delete')
            ->willThrowException(new \RuntimeException('Ce matériel a des prêts actifs'));

        $response = $this->controller->destroy($request, ['id' => '3']);

        $this->assertEquals(409, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('prêts actifs', $data['error']);
    }

    public function testDestroyReturns403ForUnauthorizedRole(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'UTILISATEUR']);

        $response = $this->controller->destroy($request, ['id' => '1']);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testDestroyReturns400WithNegativeId(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['role' => 'ADMINISTRATEUR']);

        $response = $this->controller->destroy($request, ['id' => '0']);

        $this->assertEquals(400, $response->getStatusCode());
    }
}
