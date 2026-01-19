<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\MenuController;
use App\Services\MenuService;
use App\Validators\MenuValidator;
use App\Repositories\ThemeRepository;
use App\Repositories\RegimeRepository;
use App\Core\Request;
use App\Core\Response;

class MenuControllerTest extends TestCase
{
    private MenuController $menuController;
    private MenuService $menuServiceMock;
    private MenuValidator $menuValidatorMock;
    private ThemeRepository $themeRepositoryMock;
    private RegimeRepository $regimeRepositoryMock;

    protected function setUp(): void
    {
        // Créer les mocks pour les dépendances
        $this->menuServiceMock = $this->createMock(MenuService::class);
        $this->menuValidatorMock = $this->createMock(MenuValidator::class);
        $this->themeRepositoryMock = $this->createMock(ThemeRepository::class);
        $this->regimeRepositoryMock = $this->createMock(RegimeRepository::class);

        // Instancier le contrôleur avec les mocks
        $this->menuController = new MenuController(
            $this->menuServiceMock,
            $this->menuValidatorMock,
            $this->themeRepositoryMock,
            $this->regimeRepositoryMock
        );
    }

    // ==========================================
    // TESTS POUR index()
    // ==========================================

    public function test_index_returns_menus_from_service(): void
    {
        // Arrange
        $expectedMenus = [['id' => 1, 'titre' => 'Menu Test']];
        $request = Request::createFromGlobals(); // Simule une requête simple

        $this->menuServiceMock->expects($this->once())
            ->method('getMenusWithFilters')
            ->with($request->getQueryParams())
            ->willReturn($expectedMenus);

        // Act
        $response = $this->menuController->index($request);
        $content = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($expectedMenus, $content);
    }

    // ==========================================
    // TESTS POUR show()
    // ==========================================

    public function test_show_returns_menu_details_for_valid_id(): void
    {
        // Arrange
        $menuId = 1;
        $expectedMenu = ['id' => $menuId, 'titre' => 'Menu Détail'];
        $request = Request::createFromGlobals();

        $this->menuServiceMock->expects($this->once())
            ->method('getMenuDetails')
            ->with($menuId)
            ->willReturn($expectedMenu);

        // Act
        $response = $this->menuController->show($request, $menuId);
        $content = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($expectedMenu, $content);
    }

    public function test_show_returns_404_for_invalid_id(): void
    {
        // Arrange
        $menuId = 999;
        $request = Request::createFromGlobals();

        $this->menuServiceMock->expects($this->once())
            ->method('getMenuDetails')
            ->with($menuId)
            ->willReturn(false);

        // Act
        $response = $this->menuController->show($request, $menuId);

        // Assert
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    // ==========================================
    // TESTS POUR store()
    // ==========================================

    public function test_store_creates_menu_with_valid_data(): void
    {
        // Arrange
        $inputData = ['titre' => 'Nouveau Menu', 'description' => 'Description valide', 'prix' => 25.5, 'nb_personnes_min' => 2, 'stock' => 10, 'id_theme' => 1, 'id_regime' => 1];
        $request = Request::createFromJson($inputData);

        $this->menuValidatorMock->expects($this->once())
            ->method('validate')
            ->with($inputData)
            ->willReturn(['isValid' => true, 'errors' => []]);

        $this->menuServiceMock->expects($this->once())
            ->method('createMenu')
            ->with($inputData)
            ->willReturn(123); // Nouvel ID du menu

        // Act
        $response = $this->menuController->store($request);
        $content = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals(123, $content['id']);
    }

    public function test_store_returns_422_with_invalid_data(): void
    {
        // Arrange
        $inputData = ['titre' => '']; // Données invalides
        $request = Request::createFromJson($inputData);

        $this->menuValidatorMock->expects($this->once())
            ->method('validate')
            ->with($inputData)
            ->willReturn(['isValid' => false, 'errors' => ['titre' => 'Le titre est requis.']]);

        // Act
        $response = $this->menuController->store($request);

        // Assert
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    // ==========================================
    // TESTS POUR update()
    // ==========================================

    public function test_update_modifies_menu_with_valid_data(): void
    {
        // Arrange
        $menuId = 1;
        $inputData = ['titre' => 'Menu Mis à Jour', 'description' => 'Description valide', 'prix' => 30.0, 'nb_personnes_min' => 2, 'stock' => 5, 'id_theme' => 1, 'id_regime' => 1];
        $request = Request::createFromJson($inputData);

        $this->menuValidatorMock->expects($this->once())
            ->method('validate')
            ->with($inputData)
            ->willReturn(['isValid' => true, 'errors' => []]);

        $this->menuServiceMock->expects($this->once())
            ->method('updateMenu')
            ->with($menuId, $inputData)
            ->willReturn(true);

        // Act
        $response = $this->menuController->update($request, $menuId);
        $content = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Menu mis à jour avec succès', $content['message']);
    }

    public function test_update_returns_422_with_invalid_data(): void
    {
        // Arrange
        $menuId = 1;
        $inputData = ['titre' => '']; // Données invalides
        $request = Request::createFromJson($inputData);

        $this->menuValidatorMock->expects($this->once())
            ->method('validate')
            ->with($inputData)
            ->willReturn(['isValid' => false, 'errors' => ['titre' => 'Le titre est requis.']]);

        // Act
        $response = $this->menuController->update($request, $menuId);

        // Assert
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    // ==========================================
    // TESTS POUR destroy()
    // ==========================================

    public function test_destroy_deletes_menu_and_returns_204(): void
    {
        // Arrange
        $menuId = 1;
        $request = Request::createFromGlobals();

        $this->menuServiceMock->expects($this->once())
            ->method('deleteMenu')
            ->with($menuId)
            ->willReturn(true);

        // Act
        $response = $this->menuController->destroy($request, $menuId);

        // Assert
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function test_destroy_returns_404_if_menu_not_found(): void
    {
        // Arrange
        $menuId = 999;
        $request = Request::createFromGlobals();

        $this->menuServiceMock->expects($this->once())
            ->method('deleteMenu')
            ->with($menuId)
            ->willReturn(false);

        // Act
        $response = $this->menuController->destroy($request, $menuId);

        // Assert
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    // ==========================================
    // TESTS POUR getThemes() et getRegimes()
    // ==========================================

    public function test_get_themes_returns_all_themes(): void
    {
        // Arrange
        $expectedThemes = [['id' => 1, 'libelle' => 'Asiatique']];
        $request = Request::createFromGlobals();

        $this->themeRepositoryMock->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedThemes);

        // Act
        $response = $this->menuController->getThemes($request);
        $content = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($expectedThemes, $content);
    }

    public function test_get_regimes_returns_all_regimes(): void
    {
        // Arrange
        $expectedRegimes = [['id' => 1, 'libelle' => 'Végétarien']];
        $request = Request::createFromGlobals();

        $this->regimeRepositoryMock->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedRegimes);

        // Act
        $response = $this->menuController->getRegimes($request);
        $content = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($expectedRegimes, $content);
    }
}
