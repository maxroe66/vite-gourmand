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

        $stmt = $this->pdo->prepare('DELETE FROM PROPOSE WHERE id_plat = :id');
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

    /**
     * Vérifie si un plat est utilisé dans au moins un menu actif.
     * @param int $platId
     * @return bool
     */
    public function isUsedInMenu(int $platId): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM PROPOSE p
            JOIN MENU m ON p.id_menu = m.id_menu
            WHERE p.id_plat = :platId AND m.actif = TRUE
        ');
        $stmt->execute(['platId' => $platId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Associe un allergène à un plat.
     * @param int $platId
     * @param int $allergenId
     * @return bool
     */
    public function associateAllergen(int $platId, int $allergenId): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO PLAT_ALLERGENE (id_plat, id_allergene) VALUES (:platId, :allergenId)');
        return $stmt->execute(['platId' => $platId, 'allergenId' => $allergenId]);
    }

    /**
     * Dissocie tous les allergènes d'un plat.
     * @param int $platId
     * @return bool
     */
    public function dissociateAllAllergens(int $platId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM PLAT_ALLERGENE WHERE id_plat = :platId');
        return $stmt->execute(['platId' => $platId]);
    }
}