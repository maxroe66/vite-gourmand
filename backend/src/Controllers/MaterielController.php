<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\MaterielRepository;
use App\Validators\MaterielValidator;

class MaterielController
{
    private MaterielRepository $materielRepository;
    private MaterielValidator $materielValidator;

    public function __construct(MaterielRepository $materielRepository, MaterielValidator $materielValidator)
    {
        $this->materielRepository = $materielRepository;
        $this->materielValidator = $materielValidator;
    }

    /**
     * Liste de tout le matériel (protégé)
     */
    public function index(Request $request): Response
    {
        $user = $request->getAttribute('user');

        if (!$user || !isset($user->role)) {
            return (new Response())
                ->setStatusCode(Response::HTTP_UNAUTHORIZED)
                ->setJsonContent(['error' => 'Non authentifié']);
        }

        $allowedRoles = ['ADMINISTRATEUR', 'EMPLOYE'];
        if (!in_array($user->role, $allowedRoles, true)) {
            return (new Response())
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->setJsonContent(['error' => 'Accès interdit']);
        }

        $materiels = $this->materielRepository->findAll();

        return (new Response())
            ->setStatusCode(Response::HTTP_OK)
            ->setJsonContent($materiels);
    }

    /**
     * Détail d'un matériel
     */
    public function show(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Identifiant invalide']);
        }

        $materiel = $this->materielRepository->findById($id);
        if (!$materiel) {
            return (new Response())
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setJsonContent(['error' => 'Matériel non trouvé']);
        }

        return (new Response())
            ->setStatusCode(Response::HTTP_OK)
            ->setJsonContent($materiel);
    }

    /**
     * Création d'un matériel (protégé)
     */
    public function store(Request $request): Response
    {
        $user = $request->getAttribute('user');
        if (!$user || !in_array($user->role ?? '', ['ADMINISTRATEUR', 'EMPLOYE'], true)) {
            return (new Response())
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->setJsonContent(['error' => 'Accès interdit']);
        }

        $data = $request->getJsonBody();
        if (!$data) {
            return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Données invalides ou manquantes']);
        }

        // Normalisation des clés camelCase → snake_case
        if (isset($data['valeurUnitaire']) && !isset($data['valeur_unitaire'])) {
            $data['valeur_unitaire'] = $data['valeurUnitaire'];
        }
        if (isset($data['stockDisponible']) && !isset($data['stock_disponible'])) {
            $data['stock_disponible'] = $data['stockDisponible'];
        }

        $validation = $this->materielValidator->validate($data);
        if (!$validation['isValid']) {
            return (new Response())
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setJsonContent(['errors' => $validation['errors']]);
        }

        try {
            $id = $this->materielRepository->create($data);
            return (new Response())
                ->setStatusCode(Response::HTTP_CREATED)
                ->setJsonContent(['id' => $id, 'message' => 'Matériel créé avec succès']);
        } catch (\Exception $e) {
            return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    }

    /**
     * Mise à jour d'un matériel (protégé)
     */
    public function update(Request $request, array $params): Response
    {
        $user = $request->getAttribute('user');
        if (!$user || !in_array($user->role ?? '', ['ADMINISTRATEUR', 'EMPLOYE'], true)) {
            return (new Response())
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->setJsonContent(['error' => 'Accès interdit']);
        }

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Identifiant invalide']);
        }

        $existing = $this->materielRepository->findById($id);
        if (!$existing) {
            return (new Response())
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setJsonContent(['error' => 'Matériel non trouvé']);
        }

        $data = $request->getJsonBody();
        if (!$data) {
            return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Données invalides ou manquantes']);
        }

        // Normalisation des clés camelCase → snake_case
        if (isset($data['valeurUnitaire']) && !isset($data['valeur_unitaire'])) {
            $data['valeur_unitaire'] = $data['valeurUnitaire'];
        }
        if (isset($data['stockDisponible']) && !isset($data['stock_disponible'])) {
            $data['stock_disponible'] = $data['stockDisponible'];
        }

        $validation = $this->materielValidator->validate($data);
        if (!$validation['isValid']) {
            return (new Response())
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setJsonContent(['errors' => $validation['errors']]);
        }

        try {
            $this->materielRepository->update($id, $data);
            return (new Response())
                ->setStatusCode(Response::HTTP_OK)
                ->setJsonContent(['message' => 'Matériel mis à jour avec succès']);
        } catch (\Exception $e) {
            return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    }

    /**
     * Suppression d'un matériel (protégé)
     */
    public function destroy(Request $request, array $params): Response
    {
        $user = $request->getAttribute('user');
        if (!$user || !in_array($user->role ?? '', ['ADMINISTRATEUR', 'EMPLOYE'], true)) {
            return (new Response())
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->setJsonContent(['error' => 'Accès interdit']);
        }

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Identifiant invalide']);
        }

        try {
            $deleted = $this->materielRepository->delete($id);
            if (!$deleted) {
                return (new Response())
                    ->setStatusCode(Response::HTTP_NOT_FOUND)
                    ->setJsonContent(['error' => 'Matériel non trouvé']);
            }
            return (new Response())
                ->setStatusCode(Response::HTTP_OK)
                ->setJsonContent(['message' => 'Matériel supprimé avec succès']);
        } catch (\RuntimeException $e) {
            return (new Response())
                ->setStatusCode(Response::HTTP_CONFLICT)
                ->setJsonContent(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    }
}
