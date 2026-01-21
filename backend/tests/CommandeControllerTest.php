<?php

use PHPUnit\Framework\TestCase;
use App\Controllers\CommandeController;
use App\Services\CommandeService;
use App\Validators\CommandeValidator;
use App\Core\Request;
use App\Core\Response;

class CommandeControllerTest extends TestCase
{
    private CommandeController $controller;
    private $service;
    private $validator;

    protected function setUp(): void
    {
        $this->service = $this->createMock(CommandeService::class);
        $this->validator = $this->createMock(CommandeValidator::class);
        $this->controller = new CommandeController($this->service, $this->validator);
    }

    public function testCalculateSuccess(): void
    {
        // Mock Request
        $request = $this->createMock(Request::class);
        $data = ['menuId' => 1, 'nombrePersonnes' => 10, 'adresseLivraison' => 'Bordeaux'];
        $request->method('getJsonBody')->willReturn($data);

        // Mock Service
        $expectedResult = ['prixTotal' => 1005.0];
        $this->service->method('calculatePrice')->willReturn($expectedResult);

        $response = $this->controller->calculate($request);

        $this->assertEquals(200, $response->getStatusCode());
        //$this->assertStringContainsString('1005', $response->getContent());
    }

    public function testCalculateMissingData(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getJsonBody')->willReturn([]);

        $response = $this->controller->calculate($request);

        $this->assertEquals(400, $response->getStatusCode());
        //$this->assertStringContainsString('manquant', $response->getContent());
    }

    public function testCreateSuccess(): void
    {
        $request = $this->createMock(Request::class);
        
        // Mock User param from middleware
        $user = (object)['sub' => 99, 'role' => 'CLIENT'];
        $request->method('getAttribute')->with('user')->willReturn($user);

        $data = ['menuId' => 1 /*...*/];
        $request->method('getJsonBody')->willReturn($data);

        // Validator returns empty errors
        $this->validator->method('validateCreate')->willReturn([]);

        // Service returns ID
        $this->service->method('createCommande')->willReturn(12345);

        $response = $this->controller->create($request);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testCreateUnauthorized(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')->willReturn(null);

        $response = $this->controller->create($request);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testCreateValidationError(): void
    {
        $request = $this->createMock(Request::class);
        $user = (object)['sub' => 99];
        $request->method('getAttribute')->with('user')->willReturn($user);
        $request->method('getJsonBody')->willReturn([]);

        $this->validator->method('validateCreate')->willReturn(['menuId' => 'Requis']);

        $response = $this->controller->create($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testUpdateStatusForbidden(): void
    {
        $request = $this->createMock(Request::class);
        // Client role -> Forbidden
        $user = (object)['sub' => 99, 'role' => 'CLIENT'];
        $request->method('getAttribute')->with('user')->willReturn($user);

        $response = $this->controller->updateStatus($request, 1);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testUpdateStatusSuccess(): void
    {
        $request = $this->createMock(Request::class);
        // Employe role -> OK
        $user = (object)['sub' => 55, 'role' => 'EMPLOYE'];
        $request->method('getAttribute')->with('user')->willReturn($user);
        $request->method('getJsonBody')->willReturn(['status' => 'VALIDE']);

        $this->service->expects($this->once())->method('updateStatus');

        $response = $this->controller->updateStatus($request, 1);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testLoanMaterialEmployee(): void
    {
        $request = $this->createMock(Request::class);
        $user = (object)['role' => 'EMPLOYE'];
        $request->method('getAttribute')->with('user')->willReturn($user);
        $request->method('getJsonBody')->willReturn([['id' => 1, 'quantite' => 1]]);

        $response = $this->controller->loanMaterial($request, 10);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testLoanMaterialForbidden(): void
    {
        $request = $this->createMock(Request::class);
        $user = (object)['role' => 'CLIENT'];
        $request->method('getAttribute')->with('user')->willReturn($user);

        $response = $this->controller->loanMaterial($request, 10);
        $this->assertEquals(403, $response->getStatusCode());
    }
}
