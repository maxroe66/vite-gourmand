<?php

namespace App\Repositories;

use App\Models\Horaire;
use PDO;

class HoraireRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les horaires, ordonnés par jour de la semaine.
     * @return Horaire[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('
            SELECT * FROM HORAIRE 
            ORDER BY FIELD(jour, "LUNDI", "MARDI", "MERCREDI", "JEUDI", "VENDREDI", "SAMEDI", "DIMANCHE")
        ');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Horaire($row), $rows);
    }

    /**
     * Récupère un horaire par son ID.
     * @param int $id
     * @return Horaire|null
     */
    public function findById(int $id): ?Horaire
    {
        $stmt = $this->pdo->prepare('SELECT * FROM HORAIRE WHERE id_horaire = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Horaire($row) : null;
    }

    /**
     * Met à jour un horaire existant (les 7 jours sont fixes, pas de create/delete).
     * @param int $id
     * @param array $data
     */
    public function update(int $id, array $data): void
    {
        $setClauses = [];
        $params = ['id' => $id];

        if (array_key_exists('heure_ouverture', $data)) {
            $setClauses[] = 'heure_ouverture = :heure_ouverture';
            $params['heure_ouverture'] = $data['heure_ouverture'];
        }
        if (array_key_exists('heure_fermeture', $data)) {
            $setClauses[] = 'heure_fermeture = :heure_fermeture';
            $params['heure_fermeture'] = $data['heure_fermeture'];
        }
        if (array_key_exists('ferme', $data)) {
            $setClauses[] = 'ferme = :ferme';
            $params['ferme'] = $data['ferme'] ? 1 : 0;
        }

        if (empty($setClauses)) {
            return;
        }

        $sql = 'UPDATE HORAIRE SET ' . implode(', ', $setClauses) . ' WHERE id_horaire = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
}
