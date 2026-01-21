<?php

namespace App\Repositories;

use PDO;

class RegimeRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les régimes.
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM REGIME');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouve un régime par son ID.
     * @param int $id
     * @return array|false
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM REGIME WHERE id_regime = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouveau régime.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO REGIME (libelle) VALUES (:libelle)');
        $stmt->execute(['libelle' => $data['libelle']]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Met à jour un régime.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare('UPDATE REGIME SET libelle = :libelle WHERE id_regime = :id');
        return $stmt->execute(['id' => $id, 'libelle' => $data['libelle']]);
    }

    /**
     * Supprime un régime.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM REGIME WHERE id_regime = :id');
        return $stmt->execute(['id' => $id]);
    }
}
