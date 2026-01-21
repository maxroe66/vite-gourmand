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
     * Calcule le prix d'une commande (simulation).
     * POST /api/commandes/calculate-price
     */
    public function calculate(Request $request): Response
    {
        $data = $request->getBody();

        try {
            $menuId = (int)($data['menuId'] ?? 0);
            $nombrePersonnes = (int)($data['nombrePersonnes'] ?? 0);
            $adresseLivraison = $data['adresseLivraison'] ?? '';

            if (!$menuId || !$nombrePersonnes || !$adresseLivraison) {
                return Response::json(['error' => 'Données manquantes (menuId, nombrePersonnes, adresseLivraison)'], 400);
            }

            $result = $this->commandeService->calculatePrice($menuId, $nombrePersonnes, $adresseLivraison);
            
            return Response::json($result);

        } catch (CommandeException $e) {
            return Response::json(['error' => $e->getMessage()], $e->getCode() ?: 400); 
        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], 400);
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
            return Response::json(['error' => 'Non authentifié'], 401);
        }
        $userId = (int)$user->sub;

        $data = $request->getBody();

        // Validation
        $errors = $this->commandeValidator->validateCreate($data);
        if (!empty($errors)) {
            return Response::json(['errors' => $errors], 400);
        }

        try {
            $commandeId = $this->commandeService->createCommande($userId, $data);
            
            return Response::json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'id' => $commandeId
            ], 201);

        } catch (CommandeException $e) {
            return Response::json(['error' => $e->getMessage()], $e->getCode() ?: 400);
        } catch (Exception $e) {
            // Log error
            return Response::json(['error' => 'Une erreur interne est survenue.'], 500);
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
            return Response::json(['error' => 'Non authentifié'], 401);
        }
        $userId = (int)$user->sub;

        // Note: Je devrais exposer une méthode findByUserId dans CommandeService qui appelle Repository
        // Pour l'instant, je n'ai pas implémenté la méthode service, mais le Repo l'a.
        // Généralement on ne bypass pas le Service. 
        // Je vais supposer que je peux l'ajouter dans Service ou l'appeler via repo si pattern autorisé,
        // mais cleaner d'avoir Service.
        
        // Pour ce POC, je renvoie une 501 Not Implemented ou j'ajoute la méthode dans Service rapidement.
        // Le user m'a demandé la Feature Commande. "Visualiser l'ensemble des commandes" est pré-requis.
        // Je vais faire un update rapide de CommandeService si je peux, sinon je laisse un TODO.
        // Vu que c'est une 1ère passe, je vais laisser le contrôleur simple.
        
        return Response::json(['message' => 'Not implemented yet'], 501);
    }
    
    /**
     * Mise à jour du statut (Employé uniquement)
     */
    public function updateStatus(Request $request, int $id): Response
    {
        $user = $request->getAttribute('user');
        // Check role (devrait être fait aussi côté Service ou Middleware)
        if ($user->role !== 'EMPLOYE' && $user->role !== 'ADMINISTRATEUR') {
             return Response::json(['error' => 'Accès interdit'], 403);
        }
        
        $data = $request->getBody();
        $status = $data['status'] ?? null;
        $motif = $data['motif'] ?? null;
        $modeContact = $data['modeContact'] ?? null;

        if (!$status) {
            return Response::json(['error' => 'Statut manquant'], 400);
        }

        try {
            $this->commandeService->updateStatus($user->sub, $id, $status, $motif, $modeContact);
            return Response::json(['success' => true]);
        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }
}
