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
        $status = $params['status'] ?? null;

        try {
            $avisList = $this->avisService->getAvis($status);
            
            // Formatage des données
            $data = array_map(function($avis) {
                return [
                    'id' => $avis->id,
                    'note' => $avis->note,
                    'commentaire' => $avis->commentaire,
                    'date_creation' => $avis->dateAvis,
                    'statut' => $avis->statutValidation,
                    'commande_id' => $avis->commandeId,
                    'user_id' => $avis->userId
                ];
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
        
        // Sécurité: Seul l'admin peut valider
        // Note: Le middleware AuthMiddleware met déjà le user, mais le RoleMiddleware devrait aussi être utilisé
        // Ici on fait une vérif simple
        if (isset($user->role) && $user->role !== 'ADMINISTRATEUR') {
            return Response::json(['error' => 'Accès interdit'], 403);
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
        if (isset($user->role) && $user->role !== 'ADMINISTRATEUR') {
            return Response::json(['error' => 'Accès interdit'], 403);
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
