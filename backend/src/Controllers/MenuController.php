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

        // Récupérer le menu existant pour fusionner les données
        $existingMenu = $this->menuService->getMenuDetails($id);
        if (!$existingMenu) {
            return (new Response())
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setJsonContent(['error' => 'Menu non trouvé']);
        }
        
        // On retire les plats et images existants car on ne veut pas les traiter s'ils ne sont pas envoyés
        // et cela évite des problèmes de format (tableau d'objets vs tableau d'IDs)
        if (!isset($data['plats'])) {
            unset($existingMenu['plats']);
        }
        unset($existingMenu['images']); // Suppression images pour éviter conflits

        // Mapping des champs DB vers DTO pour la validation et l'update
        // La DB retourne 'stock_disponible', le validateur/repo attend 'stock'
        if (isset($existingMenu['stock_disponible'])) {
            $existingMenu['stock'] = $existingMenu['stock_disponible'];
        }
        // Mapping nombre_personne_min (DB) -> nb_personnes_min (Validator/Repo)
        if (isset($existingMenu['nombre_personne_min'])) {
            $existingMenu['nb_personnes_min'] = $existingMenu['nombre_personne_min'];
        }

        // Fusion des données existantes avec les nouvelles données
        $mergedData = array_merge($existingMenu, $data);

        // Casting des types pour éviter les erreurs de validation
        if (isset($mergedData['prix'])) $mergedData['prix'] = (float)$mergedData['prix'];
        if (isset($mergedData['nb_personnes_min'])) $mergedData['nb_personnes_min'] = (int)$mergedData['nb_personnes_min'];
        if (isset($mergedData['stock'])) $mergedData['stock'] = (int)$mergedData['stock'];
        if (isset($mergedData['id_theme'])) $mergedData['id_theme'] = (int)$mergedData['id_theme'];
        if (isset($mergedData['id_regime'])) $mergedData['id_regime'] = (int)$mergedData['id_regime'];

        // Validation
        $validation = $this->menuValidator->validate($mergedData);
        if (!$validation['isValid']) {
            error_log('Validation Menu Update Error: ' . print_r($validation['errors'], true));
            return (new Response())
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setJsonContent(['errors' => $validation['errors']]);
        }

        try {
            if (!$this->menuService->updateMenu($id, $mergedData)) {
                 return (new Response())
                    ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->setJsonContent(['error' => 'Echec de la mise à jour']);
            }
            return (new Response())
                ->setStatusCode(Response::HTTP_OK)
                ->setJsonContent(['message' => 'Menu mis à jour avec succès']);
        } catch (Exception $e) {
             error_log('Menu Update Error: ' . $e->getMessage());
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
