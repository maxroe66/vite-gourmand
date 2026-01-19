<?php

namespace App\Repositories;

use PDO;

class ThemeRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les thèmes.
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM THEME');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouve un thème par son ID.
     * @param int $id
     * @return array|false
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM THEME WHERE id_theme = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouveau thème.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO THEME (libelle) VALUES (:libelle)');
        $stmt->execute(['libelle' => $data['libelle']]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Met à jour un thème.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare('UPDATE THEME SET libelle = :libelle WHERE id_theme = :id');
        return $stmt->execute(['id' => $id, 'libelle' => $data['libelle']]);
    }

    /**
     * Supprime un thème.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM THEME WHERE id_theme = :id');
        return $stmt->execute(['id' => $id]);
    }
}
