<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\PlatController;
use App\Services\PlatService;
use App\Validators\PlatValidator;
use App\Repositories\PlatRepository;
use App\Repositories\AllergeneRepository;
use App\Core\Request;
use App\Core\Response;
use Exception;

class PlatControllerTest extends TestCase
{
    private PlatController $platController;
    private PlatService $platServiceMock;
    private PlatValidator $platValidatorMock;
    private PlatRepository $platRepositoryMock;
    private AllergeneRepository $allergeneRepositoryMock;

    protected function setUp(): void
    {
        $this->platServiceMock = $this->createMock(PlatService::class);
        $this->platValidatorMock = $this->createMock(PlatValidator::class);
        $this->platRepositoryMock = $this->createMock(PlatRepository::class);
        $this->allergeneRepositoryMock = $this->createMock(AllergeneRepository::class);

        $this->platController = new PlatController(
            $this->platServiceMock,
            $this->platValidatorMock,
            $this->platRepositoryMock,
            $this->allergeneRepositoryMock
        );
    }

    private function makeAuthorizedRequest(?array $jsonData = null): Request
    {
        $request = $jsonData !== null
            ? Request::createFromJson($jsonData)
            : Request::createFromGlobals();

        $request->setAttribute('user', (object) [
            'role' => 'ADMINISTRATEUR',
            'sub' => 1,
        ]);

        return $request;
    }

    // ==========================================
    // TESTS POUR index()
    // ==========================================

    public function test_index_returns_all_plats(): void
    {
        // Arrange
        $expectedPlats = [['id' => 1, 'libelle' => 'Plat Test']];
        $request = Request::createFromGlobals();

        $this->platRepositoryMock->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedPlats);

        // Act
        $response = $this->platController->index($request);
        $content = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($expectedPlats, $content);
    }

    // ==========================================
    // TESTS POUR show()
    // ==========================================

    public function test_show_returns_plat_details_with_allergens(): void
    {
        // Arrange
        $platId = 1;
        $platData = ['id' => $platId, 'libelle' => 'Soupe Pho'];
        $allergens = [['id' => 1, 'libelle' => 'Arachide']];
        $request = Request::createFromGlobals();

        $this->platRepositoryMock->expects($this->once())
            ->method('findById')
            ->with($platId)
            ->willReturn($platData);
        
        $this->platRepositoryMock->expects($this->once())
            ->method('getAllergens')
            ->with($platId)
            ->willReturn($allergens);

        // Act
        $response = $this->platController->show($request, $platId);
        $content = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($platData['libelle'], $content['libelle']);
        $this->assertEquals($allergens, $content['allergenes']);
    }

    public function test_show_returns_404_for_invalid_plat_id(): void
    {
        // Arrange
        $platId = 999;
        $request = Request::createFromGlobals();

        $this->platRepositoryMock->expects($this->once())
            ->method('findById')
            ->with($platId)
            ->willReturn(false);

        // Act
        $response = $this->platController->show($request, $platId);

        // Assert
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    // ==========================================
    // TESTS POUR store()
    // ==========================================

    public function test_store_creates_plat_with_valid_data(): void
    {
        // Arrange
        $inputData = ['libelle' => 'Nouveau Plat', 'description' => '...', 'type' => 'PLAT'];
        $request = $this->makeAuthorizedRequest($inputData);

        $this->platValidatorMock->method('validate')->willReturn(['isValid' => true]);
        $this->platServiceMock->method('createDish')->willReturn(123);

        // Act
        $response = $this->platController->store($request);
        $content = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals(123, $content['id']);
    }

    // ==========================================
    // TESTS POUR destroy()
    // ==========================================

    public function test_destroy_deletes_plat_and_returns_204(): void
    {
        // Arrange
        $platId = 1;
        $request = $this->makeAuthorizedRequest();

        $this->platServiceMock->expects($this->once())
            ->method('deleteDish')
            ->with($platId)
            ->willReturn(true);

        // Act
        $response = $this->platController->destroy($request, $platId);

        // Assert
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function test_destroy_returns_409_if_plat_is_used_in_menu(): void
    {
        // Arrange
        $platId = 1;
        $request = $this->makeAuthorizedRequest();
        $exceptionMessage = "Constraint violation"; // Peu importe ce message, le contrôleur le remplace
        $expectedMessage = "Ce plat ne peut pas être supprimé car il est lié à des menus ou contient des allergènes.";

        $this->platServiceMock->expects($this->once())
            ->method('deleteDish')
            ->with($platId)
            ->willThrowException(new Exception($exceptionMessage));

        // Act
        $response = $this->platController->destroy($request, $platId);
        $content = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertEquals($expectedMessage, $content['error']);
    }
}
