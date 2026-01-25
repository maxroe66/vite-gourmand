<?php

namespace App\Repositories;

use PDO;

class MaterielRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tout le matériel disponible.
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM MATERIEL ORDER BY libelle ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un matériel par ID.
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM MATERIEL WHERE id_materiel = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
