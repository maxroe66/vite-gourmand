<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AvisService;

class AvisController
{
    private AvisService $avisService;

    public function __construct(AvisService $avisService)
    {
        $this->avisService = $avisService;
    }

    public function create(Request $request): Response
    {
        $user = $request->getAttribute('user');
        if (!$user) {
            return Response::json(['error' => 'Non autorisé'], 401);
        }

        $data = $request->getJsonBody();
        
        try {
            $result = $this->avisService->createAvis($data, $user->sub);
            return Response::json($result, 201);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function list(Request $request): Response
    {
        $params = $request->getQueryParams();
        $status = isset($params['status']) ? strtoupper((string)$params['status']) : null;
        $user = $request->getAttribute('user');

        // Par défaut, lister uniquement les avis validés pour le public.
        if ($status === null) {
            $status = 'VALIDE';
        }

        // Les statuts autres que VALIDE sont réservés à l'admin (modération).
        $isModerationView = $status !== 'VALIDE';
        if ($isModerationView) {
            if (!$user) {
                return Response::json(['error' => 'Non autorisé'], 401);
            }

            $allowedRoles = ['ADMINISTRATEUR', 'EMPLOYE'];
            if (!isset($user->role) || !in_array($user->role, $allowedRoles, true)) {
                return Response::json(['error' => 'Accès interdit'], 403);
            }
        }

        try {
            $avisList = $this->avisService->getAvis($status);
            
            // Formatage des données (masque les identifiants sensibles en mode public)
            $data = array_map(function($avis) use ($isModerationView) {
                $payload = [
                    'id' => $avis->id,
                    'note' => $avis->note,
                    'commentaire' => $avis->commentaire,
                    'date_creation' => $avis->dateAvis,
                    'statut' => $avis->statutValidation
                ];

                if ($isModerationView) {
                    $payload['commande_id'] = $avis->commandeId;
                    $payload['user_id'] = $avis->userId;
                }

                return $payload;
            }, $avisList);

            return Response::json(['data' => $data], 200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function listPublic(Request $request): Response
    {
        try {
            // Appelle le service qui va chercher dans MongoDB (ou MySQL en fallback)
            $avisList = $this->avisService->getPublicAvis();
            return Response::json(['data' => $avisList], 200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function validate(Request $request, array $params): Response
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            return Response::json(['error' => 'Non autorisé'], 401);
        }

        // Seuls les administrateurs peuvent valider un avis (cohérent avec RoleMiddleware)
        if (!isset($user->role) || $user->role !== 'ADMINISTRATEUR') {
            return Response::json(['error' => 'Accès interdit'], 403);
        }

        if (!isset($params['id']) || !is_numeric($params['id']) || (int)$params['id'] <= 0) {
            return Response::json(['error' => 'Identifiant invalide'], 400);
        }

        $id = (int)$params['id'];

        try {
            $this->avisService->validateAvis($id, $user->sub);
            return Response::json(['success' => true], 200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function delete(Request $request, array $params): Response
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            return Response::json(['error' => 'Non autorisé'], 401);
        }

        // Seuls les administrateurs peuvent supprimer un avis (cohérent avec RoleMiddleware)
        if (!isset($user->role) || $user->role !== 'ADMINISTRATEUR') {
            return Response::json(['error' => 'Accès interdit'], 403);
        }

        if (!isset($params['id']) || !is_numeric($params['id']) || (int)$params['id'] <= 0) {
            return Response::json(['error' => 'Identifiant invalide'], 400);
        }

        $id = (int)$params['id'];

        try {
            $this->avisService->deleteAvis($id);
            return Response::json(['success' => true], 200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }
}
