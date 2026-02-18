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

    /**
     * Crée un nouveau matériel.
     * @param array $data
     * @return int ID du matériel créé
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO MATERIEL (libelle, description, valeur_unitaire, stock_disponible)
            VALUES (:libelle, :description, :valeur_unitaire, :stock_disponible)
        ');
        $stmt->execute([
            'libelle' => $data['libelle'],
            'description' => $data['description'] ?? null,
            'valeur_unitaire' => $data['valeur_unitaire'],
            'stock_disponible' => $data['stock_disponible'] ?? 0
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Met à jour un matériel existant.
     * @param int $id
     * @param array $data
     */
    public function update(int $id, array $data): void
    {
        $allowedFields = ['libelle', 'description', 'valeur_unitaire', 'stock_disponible'];
        $setClauses = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($setClauses)) {
            return;
        }

        $sql = 'UPDATE MATERIEL SET ' . implode(', ', $setClauses) . ' WHERE id_materiel = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Supprime un matériel s'il n'est pas actuellement prêté.
     * @param int $id
     * @return bool
     * @throws \RuntimeException Si le matériel est actuellement prêté
     */
    public function delete(int $id): bool
    {
        // Vérifier que le matériel n'est pas actuellement prêté (non retourné)
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM COMMANDE_MATERIEL 
            WHERE id_materiel = :id AND date_retour_effectif IS NULL
        ');
        $stmt->execute(['id' => $id]);
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) {
            throw new \RuntimeException('Ce matériel est actuellement prêté et ne peut pas être supprimé.');
        }

        $stmt = $this->pdo->prepare('DELETE FROM MATERIEL WHERE id_materiel = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
