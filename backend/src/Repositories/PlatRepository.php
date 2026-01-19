<?php

namespace App\Repositories;

use PDO;

class PlatRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les plats.
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM PLAT');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouve un plat par son ID.
     * @param int $id
     * @return array|false
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM PLAT WHERE id_plat = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Trouve les plats par type (ENTREE, PLAT, DESSERT).
     * @param string $type
     * @return array
     */
    public function findByType(string $type): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM PLAT WHERE type = :type');
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouveau plat.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO PLAT (libelle, description, type) 
             VALUES (:libelle, :description, :type)'
        );

        $stmt->execute([
            'libelle' => $data['libelle'],
            'description' => $data['description'],
            'type' => $data['type']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Met à jour un plat.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE PLAT SET 
                libelle = :libelle, 
                description = :description, 
                type = :type 
             WHERE id_plat = :id'
        );

        return $stmt->execute(array_merge($data, ['id' => $id]));
    }

    /**
     * Supprime un plat.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Avant de supprimer le plat, il faut supprimer ses associations
        $stmt = $this->pdo->prepare('DELETE FROM PLAT_ALLERGENE WHERE id_plat = :id');
        $stmt->execute(['id' => $id]);

        $stmt = $this->pdo->prepare('DELETE FROM MENU_PLAT WHERE id_plat = :id');
        $stmt->execute(['id' => $id]);

        $stmt = $this->pdo->prepare('DELETE FROM PLAT WHERE id_plat = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Récupère les allergènes pour un plat donné.
     * @param int $platId
     * @return array
     */
    public function getAllergens(int $platId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT a.* FROM ALLERGENE a
            JOIN PLAT_ALLERGENE pa ON a.id_allergene = pa.id_allergene
            WHERE pa.id_plat = :platId
        ');
        $stmt->execute(['platId' => $platId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}