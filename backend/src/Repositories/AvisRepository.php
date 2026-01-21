<?php

namespace App\Repositories;

use App\Models\Avis;
use PDO;

class AvisRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Avis $avis): bool
    {
        $sql = "INSERT INTO AVIS_FALLBACK (
                    note, commentaire, statut_validation, date_avis, 
                    id_utilisateur, id_commande, id_menu
                ) VALUES (
                    :note, :commentaire, :statut, NOW(), 
                    :userId, :commandeId, :menuId
                )";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'note' => $avis->note,
            'commentaire' => $avis->commentaire,
            'statut' => $avis->statutValidation,
            'userId' => $avis->userId,
            'commandeId' => $avis->commandeId,
            'menuId' => $avis->menuId
        ]);
    }

    public function findAllByStatus(?string $status): array
    {
        $sql = "SELECT * FROM AVIS_FALLBACK";
        $params = [];

        if ($status) {
            $sql .= " WHERE statut_validation = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY date_avis DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        return array_map([Avis::class, 'fromArray'], $results);
    }

    public function findById(int $id): ?Avis
    {
        $stmt = $this->pdo->prepare("SELECT * FROM AVIS_FALLBACK WHERE id_avis_fallback = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if (!$data) return null;
        return Avis::fromArray($data);
    }

    public function validate(int $id, int $moderatorId, string $status = 'VALIDE'): bool
    {
        $sql = "UPDATE AVIS_FALLBACK SET 
                statut_validation = :status, 
                modere_par = :moderatorId, 
                date_validation = NOW() 
                WHERE id_avis_fallback = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'moderatorId' => $moderatorId,
            'id' => $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM AVIS_FALLBACK WHERE id_avis_fallback = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    // Check if avis already exists for this command
    public function hasAvisForCommande(int $commandeId): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM AVIS_FALLBACK WHERE id_commande = :id");
        $stmt->execute(['id' => $commandeId]);
        return $stmt->fetchColumn() > 0;
    }
}
