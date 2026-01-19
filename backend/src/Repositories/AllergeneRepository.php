<?php

namespace App\Repositories;

use PDO;

class AllergeneRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les allergènes.
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM ALLERGENE');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouve un allergène par son ID.
     * @param int $id
     * @return array|false
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ALLERGENE WHERE id_allergene = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouvel allergène.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO ALLERGENE (libelle) VALUES (:libelle)');
        $stmt->execute(['libelle' => $data['libelle']]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Met à jour un allergène.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare('UPDATE ALLERGENE SET libelle = :libelle WHERE id_allergene = :id');
        return $stmt->execute(['id' => $id, 'libelle' => $data['libelle']]);
    }

    /**
     * Supprime un allergène.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM ALLERGENE WHERE id_allergene = :id');
        return $stmt->execute(['id' => $id]);
    }
}
