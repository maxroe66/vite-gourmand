<?php

namespace App\Repositories;

use PDO;

class MenuRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les menus avec des filtres optionnels.
     * @param array $filters
     * @return array
     */
    public function findAll(array $filters = []): array
    {
        $sql = 'SELECT * FROM MENU WHERE 1=1';
        $params = [];

        if (!empty($filters['prix_max'])) {
            $sql .= ' AND prix <= :prix_max';
            $params[':prix_max'] = $filters['prix_max'];
        }

        if (!empty($filters['theme'])) {
            $sql .= ' AND id_theme = :id_theme';
            $params[':id_theme'] = $filters['theme'];
        }

        if (!empty($filters['regime'])) {
            $sql .= ' AND id_regime = :id_regime';
            $params[':id_regime'] = $filters['regime'];
        }

        if (!empty($filters['nb_personnes'])) {
            $sql .= ' AND nombre_personne_min <= :nb_personnes';
            $params[':nb_personnes'] = $filters['nb_personnes'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouve un menu par son ID avec ses relations.
     * @param int $id
     * @return array|false
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM MENU WHERE id_menu = :id');
        $stmt->execute(['id' => $id]);
        $menu = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$menu) {
            return false;
        }

        // Récupérer les plats associés
        $stmt = $this->pdo->prepare('
            SELECT p.* FROM PLAT p
            JOIN PROPOSE mp ON p.id_plat = mp.id_plat
            WHERE mp.id_menu = :id
        ');
        $stmt->execute(['id' => $id]);
        $menu['plats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les images
        $stmt = $this->pdo->prepare('SELECT url FROM IMAGE_MENU WHERE id_menu = :id');
        $stmt->execute(['id' => $id]);
        $menu['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $menu;
    }

    /**
     * Crée un nouveau menu.
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO MENU (titre, description, prix, nombre_personne_min, conditions, stock_disponible, actif, id_theme, id_regime) 
             VALUES (:titre, :description, :prix, :nb_personnes_min, :conditions, :stock, :actif, :id_theme, :id_regime)'
        );

        $stmt->execute([
            'titre' => $data['titre'],
            'description' => $data['description'],
            'prix' => $data['prix'],
            'nb_personnes_min' => $data['nb_personnes_min'],
            'conditions' => $data['conditions'],
            'stock' => $data['stock'],
            'actif' => $data['actif'] ?? true,
            'id_theme' => $data['id_theme'],
            'id_regime' => $data['id_regime']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Met à jour un menu.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE MENU SET 
                titre = :titre, 
                description = :description, 
                prix = :prix, 
                nombre_personne_min = :nb_personnes_min, 
                conditions = :conditions, 
                stock_disponible = :stock, 
                actif = :actif, 
                id_theme = :id_theme, 
                id_regime = :id_regime 
             WHERE id_menu = :id'
        );

        return $stmt->execute([
            'titre' => $data['titre'],
            'description' => $data['description'],
            'prix' => $data['prix'],
            'nb_personnes_min' => $data['nb_personnes_min'],
            'conditions' => $data['conditions'] ?? null,
            'stock' => $data['stock'],
            'actif' => $data['actif'] ?? true,
            'id_theme' => $data['id_theme'],
            'id_regime' => $data['id_regime'],
            'id' => $id
        ]);
    }

    /**
     * Supprime logiquement un menu.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE MENU SET actif = false WHERE id_menu = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Décrémente le stock d\'un menu.
     * @param int $id
     * @param int $quantity
     * @return bool
     */
    public function decrementStock(int $id, int $quantity): bool
    {
        $stmt = $this->pdo->prepare('UPDATE MENU SET stock = stock - :quantity WHERE id_menu = :id AND stock >= :quantity');
        return $stmt->execute(['id' => $id, 'quantity' => $quantity]);
    }

    /**
     * Récupère les menus actifs.
     * @return array
     */
    public function getMenusActifs(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM MENU WHERE actif = true AND stock > 0');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Associe un plat à un menu.
     * @param int $menuId
     * @param int $platId
     * @param int $position
     * @return bool
     */
    public function associateDish(int $menuId, int $platId, int $position): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO PROPOSE (id_menu, id_plat, position) VALUES (:menuId, :platId, :position)');
        return $stmt->execute(['menuId' => $menuId, 'platId' => $platId, 'position' => $position]);
    }

    /**
     * Dissocie tous les plats d'un menu.
     * @param int $menuId
     * @return bool
     */
    public function dissociateAllDishes(int $menuId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM PROPOSE WHERE id_menu = :menuId');
        return $stmt->execute(['menuId' => $menuId]);
    }
}
