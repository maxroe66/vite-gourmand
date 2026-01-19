<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\MenuService;
use App\Validators\MenuValidator;
use App\Repositories\ThemeRepository;
use App\Repositories\RegimeRepository;
use Exception;

class MenuController
{
    private MenuService $menuService;
    private MenuValidator $menuValidator;
    private ThemeRepository $themeRepository;
    private RegimeRepository $regimeRepository;

    public function __construct(
        MenuService $menuService,
        MenuValidator $menuValidator,
        ThemeRepository $themeRepository,
        RegimeRepository $regimeRepository
    ) {
        $this->menuService = $menuService;
        $this->menuValidator = $menuValidator;
        $this->themeRepository = $themeRepository;
        $this->regimeRepository = $regimeRepository;
    }

    /**
     * Liste des menus (public)
     */
    public function index(Request $request): Response
    {
        $filters = $request->getQueryParams();
        $menus = $this->menuService->getMenusWithFilters($filters);
        
        return (new Response())
            ->setStatusCode(Response::HTTP_OK)
            ->setJsonContent($menus);
    }

    /**
     * Détail d'un menu (public)
     */
    public function show(Request $request, int $id): Response
    {
        $menu = $this->menuService->getMenuDetails($id);
        
        if (!$menu) {
            return (new Response())
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setJsonContent(['error' => 'Menu non trouvé']);
        }

        return (new Response())
            ->setStatusCode(Response::HTTP_OK)
            ->setJsonContent($menu);
    }

    /**
     * Création d'un menu (protégé)
     */
    public function store(Request $request): Response
    {
        $data = $request->getJsonBody();

        if (!$data) {
             return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Données invalides ou manquantes']);
        }

        // Validation
        $validation = $this->menuValidator->validate($data);
        if (!$validation['isValid']) {
            return (new Response())
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setJsonContent(['errors' => $validation['errors']]);
        }

        try {
            $id = $this->menuService->createMenu($data);
            return (new Response())
                ->setStatusCode(Response::HTTP_CREATED)
                ->setJsonContent(['id' => $id, 'message' => 'Menu créé avec succès']);
        } catch (Exception $e) {
            return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    }

    /**
     * Mise à jour d'un menu (protégé)
     */
    public function update(Request $request, int $id): Response
    {
        $data = $request->getJsonBody();

        if (!$data) {
             return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Données invalides ou manquantes']);
        }

        // Validation
        $validation = $this->menuValidator->validate($data);
        if (!$validation['isValid']) {
            return (new Response())
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setJsonContent(['errors' => $validation['errors']]);
        }

        try {
            if (!$this->menuService->updateMenu($id, $data)) {
                 return (new Response())
                    ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->setJsonContent(['error' => 'Echec de la mise à jour']);
            }
            return (new Response())
                ->setStatusCode(Response::HTTP_OK)
                ->setJsonContent(['message' => 'Menu mis à jour avec succès']);
        } catch (Exception $e) {
             return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    }

    /**
     * Suppression d'un menu (protégé)
     */
    public function destroy(Request $request, int $id): Response
    {
        try {
            if ($this->menuService->deleteMenu($id)) {
                return (new Response())->setStatusCode(Response::HTTP_NO_CONTENT);
            }
            return (new Response())
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setJsonContent(['error' => 'Menu non trouvé ou impossible à supprimer']);
        } catch (Exception $e) {
            return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    }

    /**
     * Liste des thèmes (public)
     */
    public function getThemes(Request $request): Response
    {
        $themes = $this->themeRepository->findAll();
        return (new Response())
            ->setStatusCode(Response::HTTP_OK)
            ->setJsonContent($themes);
    }

    /**
     * Liste des régimes (public)
     */
    public function getRegimes(Request $request): Response
    {
        $regimes = $this->regimeRepository->findAll();
         return (new Response())
            ->setStatusCode(Response::HTTP_OK)
            ->setJsonContent($regimes);
    }
}
