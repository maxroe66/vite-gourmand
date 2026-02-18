<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\HoraireRepository;
use App\Validators\HoraireValidator;

class HoraireController
{
    private HoraireRepository $horaireRepository;
    private HoraireValidator $horaireValidator;

    public function __construct(HoraireRepository $horaireRepository, HoraireValidator $horaireValidator)
    {
        $this->horaireRepository = $horaireRepository;
        $this->horaireValidator = $horaireValidator;
    }

    /**
     * Liste de tous les horaires (public — accessible sans authentification).
     */
    public function index(Request $request): Response
    {
        $horaires = $this->horaireRepository->findAll();
        $data = array_map(fn($h) => $h->toArray(), $horaires);

        return (new Response())
            ->setStatusCode(Response::HTTP_OK)
            ->setJsonContent(['data' => $data]);
    }

    /**
     * Mise à jour d'un horaire (protégé : EMPLOYE, ADMINISTRATEUR).
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

        $existing = $this->horaireRepository->findById($id);
        if (!$existing) {
            return (new Response())
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setJsonContent(['error' => 'Horaire non trouvé']);
        }

        $data = $request->getJsonBody();
        if (!$data) {
            return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Données invalides ou manquantes']);
        }

        // Validation
        $validation = $this->horaireValidator->validate($data);
        if (!$validation['isValid']) {
            return (new Response())
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setJsonContent(['errors' => $validation['errors']]);
        }

        // Normalisation camelCase → snake_case pour le repository
        $dbData = [];
        $dbData['ferme'] = (bool)($data['ferme'] ?? false);

        if ($dbData['ferme']) {
            // Si fermé, on peut nullifier les heures
            $dbData['heure_ouverture'] = null;
            $dbData['heure_fermeture'] = null;
        } else {
            $dbData['heure_ouverture'] = $data['heureOuverture'] ?? $data['heure_ouverture'] ?? null;
            $dbData['heure_fermeture'] = $data['heureFermeture'] ?? $data['heure_fermeture'] ?? null;
        }

        try {
            $this->horaireRepository->update($id, $dbData);
            $updated = $this->horaireRepository->findById($id);

            return (new Response())
                ->setStatusCode(Response::HTTP_OK)
                ->setJsonContent([
                    'message' => 'Horaire mis à jour avec succès',
                    'horaire' => $updated->toArray()
                ]);
        } catch (\Exception $e) {
            return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    }
}
