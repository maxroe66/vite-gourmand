<?php

namespace App\Services;

use App\Models\Avis;
use App\Repositories\AvisRepository;
use App\Repositories\CommandeRepository;
use MongoDB\Client as MongoClient;

class AvisService
{
    private AvisRepository $avisRepository;
    private CommandeRepository $commandeRepository;
    private MongoClient $mongoClient;
    private string $mongoDbName;

    public function __construct(
        AvisRepository $avisRepository, 
        CommandeRepository $commandeRepository,
        MongoClient $mongoClient,
        array $config
    )
    {
        $this->avisRepository = $avisRepository;
        $this->commandeRepository = $commandeRepository;
        $this->mongoClient = $mongoClient;
        $this->mongoDbName = $config['mongo']['database'] ?? 'vite_gourmand';
    }

    public function createAvis(array $data, int $userId): array
    {
        if (!isset($data['commandeId'], $data['note'], $data['commentaire'])) {
            throw new \InvalidArgumentException("Données incomplètes (commandeId, note, commentaire)");
        }

        $commandeId = (int)$data['commandeId'];
        $note = (int)$data['note'];
        
        if ($note < 1 || $note > 5) {
            throw new \InvalidArgumentException("La note doit être entre 1 et 5");
        }

        $commande = $this->commandeRepository->findById($commandeId);
        if (!$commande) {
            throw new \Exception("Commande introuvable");
        }
        
        if ($commande->userId !== $userId) {
            throw new \Exception("Vous ne pouvez pas noter une commande qui ne vous appartient pas");
        }

        // Vérification statut
        if (!in_array($commande->statut, ['TERMINEE', 'LIVRE'])) {
             throw new \Exception("La commande doit être terminée ou livrée pour laisser un avis (Statut actuel: {$commande->statut})");
        }

        if ($this->avisRepository->hasAvisForCommande($commandeId)) {
            throw new \Exception("Cette commande a déjà un avis");
        }

        $avis = new Avis();
        $avis->note = $note;
        $avis->commentaire = strip_tags($data['commentaire']);
        $avis->userId = $userId;
        $avis->commandeId = $commandeId;
        $avis->menuId = $commande->menuId;
        $avis->statutValidation = 'EN_ATTENTE';

        $success = $this->avisRepository->create($avis);

        if (!$success) {
            throw new \Exception("Erreur lors de l'enregistrement de l'avis");
        }

        // On devrait update has_avis côté commande, mais faisons simple pour l'exercice.
        // Si le repository Commande expose update, on pourrait faire:
        // $commande->hasAvis = true;
        // $this->commandeRepository->update($commande); 
        // Mais il semble que le mapping soit manuel.

        return ['success' => true, 'message' => 'Avis envoyé avec succès, en attente de modération'];
    }

    public function getAvis(?string $status): array
    {
        // Retourne un tableau d'objets Avis, qu'on devra peut-être transformer en tableau associatif
        // pour le JSON response dans le Controller
        return $this->avisRepository->findAllByStatus($status);
    }

    public function getPublicAvis(): array
    {
        // 1. Essayer MongoDB
        try {
            $collection = $this->mongoClient->selectCollection($this->mongoDbName, 'avis');
            /* @var \MongoDB\Model\BSONDocument[] $cursor */
            $cursor = $collection->find(
                ['statut_validation' => 'VALIDE'],
                [
                    'sort' => ['date_avis' => -1],
                    'limit' => 10
                ]
            );

            // Convertir les documents BSON en tableau
            $avisList = [];
            foreach ($cursor as $doc) {
                // Conversion BSON -> Array simplifiée
                $avisList[] = [
                   'note' => $doc['note'],
                   'commentaire' => $doc['commentaire'],
                   'date_avis' => $doc['date_avis'], // BSON Date
                   // On limite les infos publiques (pas d'ID, pas d'ID User)
                ];
            }
            
            // Si on a des résultats, on les retourne
            if (!empty($avisList)) return $avisList;

        } catch (\Exception $e) {
            error_log("Lecture MongoDB impossible pour avis publics (Fallback MySQL) : " . $e->getMessage());
        }

        // 2. Fallback MySQL si MongoDB vide ou en erreur
        $sqlAvis = $this->avisRepository->findAllByStatus('VALIDE');
        
        // Maper l'objet Avis vers un array structurellement identique à celui de Mongo
        return array_map(function($avis) {
            return [
                'note' => $avis->note,
                'commentaire' => $avis->commentaire,
                'date_avis' => $avis->dateAvis
            ];
        }, $sqlAvis);
    }

    public function validateAvis(int $id, int $moderatorId): bool
    {
        $success = $this->avisRepository->validate($id, $moderatorId, 'VALIDE');
        if ($success) {
            $this->syncToMongoDB($id);
        }
        return $success;
    }

    private function syncToMongoDB(int $sqlId): void
    {
        try {
            // Récupérer les données complètes depuis MySQL
            $avis = $this->avisRepository->findById($sqlId);
            if (!$avis) return;
            
            $collection = $this->mongoClient->selectCollection($this->mongoDbName, 'avis');

            // Insérer ou Mettre à jour
            $collection->updateOne(
                ['mysql_id' => $sqlId],
                ['$set' => [
                    'note' => $avis->note,
                    'commentaire' => $avis->commentaire,
                    'statut_validation' => $avis->statutValidation,
                    'date_avis' => new \MongoDB\BSON\UTCDateTime(strtotime($avis->dateAvis) * 1000),
                    'id_utilisateur' => $avis->userId,
                    'id_commande' => $avis->commandeId,
                    'id_menu' => $avis->menuId,
                    'modere_par' => $avis->moderePar,
                    'date_validation' => $avis->dateValidation ? new \MongoDB\BSON\UTCDateTime(strtotime($avis->dateValidation) * 1000) : null,
                    'mysql_synced' => true,
                    'mysql_id' => $sqlId
                ]],
                ['upsert' => true]
            );

        } catch (\Exception $e) {
            // Log error
            error_log("MongoDB Sync Error for Avis {$sqlId}: " . $e->getMessage());
        }
    }

    public function deleteAvis(int $id): bool
    {
         // D'abord supprimer de MongoDB si présent (pour cohérence)
         try {
             $this->mongoClient->selectCollection($this->mongoDbName, 'avis')->deleteOne(['mysql_id' => $id]);
         } catch (\Exception $e) {
             error_log("MongoDB Delete Error for Avis {$id}: " . $e->getMessage());
         }

         return $this->avisRepository->delete($id);
    }
}
