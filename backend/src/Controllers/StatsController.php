<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use MongoDB\Client as MongoDBClient;
use Exception;

class StatsController
{
    private ?MongoDBClient $mongoDBClient;
    private string $mongoDbName;

    public function __construct(string $mongoDbName, ?MongoDBClient $mongoDBClient = null)
    {
        $this->mongoDbName = $mongoDbName;
        $this->mongoDBClient = $mongoDBClient;
    }

    private function jsonResponse(mixed $data, int $status = 200): Response
    {
        return (new Response())->setStatusCode($status)->setJsonContent($data);
    }

    /**
     * Statistiques des commandes par menu (MongoDB)
     * GET /api/menues-commandes-stats
     */
    public function getMenuStats(Request $request): Response
    {
        $user = $request->getAttribute('user');

        if (!$user || !isset($user->role)) {
            return $this->jsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->role !== 'ADMINISTRATEUR') {
            return $this->jsonResponse(['error' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->mongoDBClient) {
            return $this->jsonResponse(['error' => 'Service de statistiques indisponible (MongoDB non connecté)'], 503);
        }

        try {
            $collection = $this->mongoDBClient->selectCollection($this->mongoDbName, 'statistiques_commandes');
            
            $pipeline = [];

            // Filtrage par date et par menu si paramètres fournis
            $startDate = $request->getQueryParam('startDate');
            $endDate = $request->getQueryParam('endDate');
            $menuId = $request->getQueryParam('menuId');

            $isDate = function ($value) {
                return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
            };

            if (($startDate && !$isDate($startDate)) || ($endDate && !$isDate($endDate))) {
                return $this->jsonResponse(['error' => 'Format de date invalide (attendu AAAA-MM-JJ)'], Response::HTTP_BAD_REQUEST);
            }

            if ($menuId !== null && !is_numeric($menuId)) {
                return $this->jsonResponse(['error' => 'menuId doit être numérique'], Response::HTTP_BAD_REQUEST);
            }

            $matchRule = [];

            if ($startDate || $endDate) {
                $dateMatch = [];
                if ($startDate) {
                    $dateMatch['$gte'] = $startDate . ' 00:00:00';
                }
                if ($endDate) {
                    $dateMatch['$lte'] = $endDate . ' 23:59:59';
                }
                $matchRule['dateCommande'] = $dateMatch;
            }

            if ($menuId) {
                // menuId est stocké en int dans MongoDB (cf. CommandeService sync)
                $matchRule['menuId'] = (int)$menuId;
            }

            if (!empty($matchRule)) {
                $pipeline[] = ['$match' => $matchRule];
            }

            // Agrégation : Total CA et Nombre commandes par Menu
            $pipeline[] = [
                '$group' => [
                    '_id' => '$menuId',
                    'totalCommandes' => ['$sum' => 1],
                    'chiffreAffaires' => ['$sum' => '$prixTotal'],
                    'nombrePersonnesTotal' => ['$sum' => '$nombrePersonnes']
                ]
            ];
            
            $pipeline[] = [
                '$sort' => ['chiffreAffaires' => -1]
            ];

            $results = $collection->aggregate($pipeline);
            
            $stats = [];
            foreach ($results as $doc) {
                // On pourrait enrichir avec le nom du menu via MySQL si besoin, 
                // mais pour l'instant on renvoie les ID.
                $stats[] = [
                    'menuId' => $doc['_id'],
                    'totalCommandes' => $doc['totalCommandes'],
                    'chiffreAffaires' => $doc['chiffreAffaires'],
                    'nombrePersonnesTotal' => $doc['nombrePersonnesTotal']
                ];
            }

            return $this->jsonResponse($stats);

        } catch (Exception $e) {
            return $this->jsonResponse(['error' => 'Erreur MongoDB: ' . $e->getMessage()], 500);
        }
    }
}
