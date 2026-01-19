<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PlatService;
use App\Validators\PlatValidator;
use App\Repositories\PlatRepository;
use Exception;

class PlatController
{
    private PlatService $platService;
    private PlatValidator $platValidator;
    private PlatRepository $platRepository; // Pour le listing simple sans logique métier complexe

    public function __construct(
        PlatService $platService,
        PlatValidator $platValidator,
        PlatRepository $platRepository
    ) {
        $this->platService = $platService;
        $this->platValidator = $platValidator;
        $this->platRepository = $platRepository;
    }

    /**
     * Liste des plats (public/admin)
     */
    public function index(Request $request): Response
    {
        // On pourrait ajouter des filtres ici si nécessaire
        $plats = $this->platRepository->findAll();
        
        return (new Response())
            ->setStatusCode(Response::HTTP_OK)
            ->setJsonContent($plats);
    }

    /**
     * Détail d'un plat (public/admin)
     */
    public function show(Request $request, int $id): Response
    {
        $plat = $this->platRepository->findById($id);
        
        if (!$plat) {
            return (new Response())
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setJsonContent(['error' => 'Plat non trouvé']);
        }
        
        // On ajoute les allergènes aux détails du plat
        $plat['allergenes'] = $this->platRepository->getAllergens($id);

        return (new Response())
            ->setStatusCode(Response::HTTP_OK)
            ->setJsonContent($plat);
    }

    /**
     * Liste des plats par type (ENTREE, PLAT, DESSERT)
     */
    public function getByType(Request $request): Response
    {
       $type = $request->getQueryParams()['type'] ?? null;
       
       if (!$type || !in_array($type, ['ENTREE', 'PLAT', 'DESSERT'])) {
            return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Type de plat invalide ou manquant']);
       }

       $plats = $this->platRepository->findByType($type);
       return (new Response())
            ->setStatusCode(Response::HTTP_OK)
            ->setJsonContent($plats);
    }

    /**
     * Création d'un plat (protégé)
     */
    public function store(Request $request): Response
    {
        $data = $request->getJsonBody();

        if (!$data) {
             return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Données invalides ou manquantes']);
        }

        // Mapping pour compatibilité avec le test Postman (nom -> libelle)
        if (isset($data['nom']) && !isset($data['libelle'])) {
            $data['libelle'] = $data['nom'];
        }

        // Normalisation du type en majuscules (plat -> PLAT)
        if (isset($data['type'])) {
            $data['type'] = strtoupper($data['type']);
        }

        // Validation
        $validation = $this->platValidator->validate($data);
        if (!$validation['isValid']) {
            return (new Response())
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setJsonContent(['errors' => $validation['errors']]);
        }

        try {
            $id = $this->platService->createDish($data);
            return (new Response())
                ->setStatusCode(Response::HTTP_CREATED)
                ->setJsonContent(['id' => $id, 'message' => 'Plat créé avec succès']);
        } catch (Exception $e) {
            error_log('Plat Create Error: ' . $e->getMessage());
            return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    }

    /**
     * Mise à jour d'un plat (protégé)
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
        $validation = $this->platValidator->validate($data);
        if (!$validation['isValid']) {
            return (new Response())
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setJsonContent(['errors' => $validation['errors']]);
        }

        try {
            if (!$this->platService->updateDish($id, $data)) {
                 return (new Response())
                    ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->setJsonContent(['error' => 'Echec de la mise à jour']);
            }
            return (new Response())
                ->setStatusCode(Response::HTTP_OK)
                ->setJsonContent(['message' => 'Plat mis à jour avec succès']);
        } catch (Exception $e) {
             return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    }

    /**
     * Suppression d'un plat (protégé)
     */
    public function destroy(Request $request, int $id): Response
    {
        try {
            if ($this->platService->deleteDish($id)) {
                return (new Response())->setStatusCode(Response::HTTP_NO_CONTENT);
            }
            return (new Response())
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setJsonContent(['error' => 'Plat non trouvé']);
        } catch (Exception $e) {
            // Ici on gère l'exception si le plat est utilisé dans un menu
            return (new Response())
                ->setStatusCode(Response::HTTP_CONFLICT) // 409 Conflict est approprié ici
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    }
}
