<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\CommandeService;
use App\Validators\CommandeValidator;
use App\Exceptions\CommandeException;
use Exception;

class CommandeController
{
    private CommandeService $commandeService;
    private CommandeValidator $commandeValidator;

    public function __construct(CommandeService $commandeService, CommandeValidator $commandeValidator)
    {
        $this->commandeService = $commandeService;
        $this->commandeValidator = $commandeValidator;
    }

    /**
     * Helper pour créer une réponse JSON.
     */
    private function jsonResponse(mixed $data, int $status = 200): Response
    {
        return (new Response())->setStatusCode($status)->setJsonContent($data);
    }

    /**
     * Calcule le prix d'une commande (simulation).
     * POST /api/commandes/calculate-price
     */
    public function calculate(Request $request): Response
    {
        $data = $request->getJsonBody();

        try {
            $menuId = (int)($data['menu_id'] ?? $data['menuId'] ?? 0);
            $nombrePersonnes = (int)($data['nombre_personnes'] ?? $data['nombrePersonnes'] ?? 1);
            $adresseLivraison = $data['user_address'] ?? $data['adresseLivraison'] ?? '';

            if (!$menuId) {
                return $this->jsonResponse(['error' => 'ID du menu manquant (menu_id)'], 400);
            }
            
            if (!$adresseLivraison) {
                return $this->jsonResponse(['error' => 'Adresse manquante'], 400);
            }

            $result = $this->commandeService->calculatePrice($menuId, $nombrePersonnes, $adresseLivraison);
            
            return $this->jsonResponse($result);

        } catch (CommandeException $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], $e->getCode() ?: 400); 
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Crée une nouvelle commande.
     * POST /api/commandes
     */
    public function create(Request $request): Response
    {
        // Récupérer l'utilisateur authentifié (via Middleware)
        $user = $request->getAttribute('user');
        
        if (!$user || !isset($user->sub)) {
            return $this->jsonResponse(['error' => 'Non authentifié'], 401);
        }
        $userId = (int)$user->sub;

        $data = $request->getJsonBody();

        // Validation
        $errors = $this->commandeValidator->validateCreate($data);
        if (!empty($errors)) {
            return $this->jsonResponse(['errors' => $errors], 400);
        }

        try {
            $commandeId = $this->commandeService->createCommande($userId, $data);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'id' => $commandeId
            ], 201);

        } catch (CommandeException $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], $e->getCode() ?: 400);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => 'Une erreur interne est survenue: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Liste les commandes de l'utilisateur connecté.
     * GET /api/commandes/me
     */
    public function listMyOrders(Request $request): Response
    {
        $user = $request->getAttribute('user');
        if (!$user || !isset($user->sub)) {
            return $this->jsonResponse(['error' => 'Non authentifié'], 401);
        }
        $userId = (int)$user->sub;

        try {
            $commandes = $this->commandeService->getUserOrders($userId);
            
            // Formatage pour le frontend (Light DTO)
            $response = array_map(function($cmd) {
                return [
                    'id' => $cmd->id,
                    'dateCommande' => $cmd->dateCommande,
                    'datePrestation' => $cmd->datePrestation,
                    'statut' => $cmd->statut,
                    'prixTotal' => $cmd->prixTotal,
                    'menuId' => $cmd->menuId,
                    // Logique Frontend "Can Review" (Feature Avis)
                    'canReview' => $cmd->canBeReviewed()
                ];
            }, $commandes);

            return $this->jsonResponse($response);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => 'Erreur récupération commandes: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Voir le détail d'une commande avec sa timeline.
     * GET /api/commandes/{id}
     */
    public function show(Request $request, int $id): Response
    {
        $user = $request->getAttribute('user');
        if (!$user || !isset($user->sub)) {
            return $this->jsonResponse(['error' => 'Non authentifié'], 401);
        }

        try {
            $data = $this->commandeService->getOrderWithTimeline((int)$user->sub, $id);
            return $this->jsonResponse($data);
        } catch (CommandeException $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], $e->getCode() ?: 403);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Modification d'une commande par le client (PATCH)
     * "Tout est modifiable, sauf le choix du menu" 
     * Condition: Commande non encore "ACCEPTE" (ou statut avancé)
     */
    public function update(Request $request, int $id): Response
    {
        $user = $request->getAttribute('user');
        if (!$user || !isset($user->sub)) {
             return $this->jsonResponse(['error' => 'Non authentifié'], 401);
        }

        $data = $request->getJsonBody();

        // Règle métier : Interdiction de modifier le menuId
        if (isset($data['menuId']) || isset($data['menu_id'])) {
             return $this->jsonResponse(['error' => 'Impossible de modifier le menu choisi.'], 400);
        }

        try {
             $this->commandeService->updateCommande((int)$user->sub, $id, $data);
             return $this->jsonResponse(['success' => true, 'message' => 'Commande mise à jour']);
        } catch (Exception $e) {
             return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Mise à jour du statut (Employé uniquement)
     */
    public function updateStatus(Request $request, int $id): Response
    {
        $user = $request->getAttribute('user');
        // Check role (devrait être fait aussi côté Service ou Middleware)
        if (!isset($user->role) || ($user->role !== 'EMPLOYE' && $user->role !== 'ADMINISTRATEUR')) {
             return $this->jsonResponse(['error' => 'Accès interdit'], 403);
        }
        
        $data = $request->getJsonBody();
        $status = $data['status'] ?? null;
        $motif = $data['motif'] ?? null;
        $modeContact = $data['modeContact'] ?? null;

        if (!$status) {
            return $this->jsonResponse(['error' => 'Statut manquant'], 400);
        }

        // Règle métier : Annulation requiert motif et mode de contact
        if ($status === 'ANNULE' && (empty($motif) || empty($modeContact))) {
            return $this->jsonResponse(['error' => 'L\'annulation nécessite un motif et un mode de contact (GSM/Email).'], 400);
        }

        try {
            $employeId = (int)$user->sub;
            $this->commandeService->updateStatus($employeId, $id, $status, $motif, $modeContact);
            return $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
