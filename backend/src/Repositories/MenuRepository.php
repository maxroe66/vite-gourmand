<?php

namespace App\Repositories;

use App\Models\Menu;
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
        $sql = 'SELECT m.*, (SELECT url FROM IMAGE_MENU im WHERE im.id_menu = m.id_menu LIMIT 1) as image 
                FROM MENU m 
                WHERE m.actif = true'; // On affiche que les actifs par défaut dans la liste publique
        $params = [];

        // Note: Le WHERE 1=1 a été remplacé par WHERE actif=true pour la sécurité publique
        // Si on veut aussi les inactifs (pour admin), il faudra un paramètre en plus, 
        // mais ici c'est findAll pour l'affichage liste

        if (!empty($filters['prix_min'])) {
            $sql .= ' AND m.prix >= :prix_min';
            $params[':prix_min'] = $filters['prix_min'];
        }

        if (!empty($filters['prix_max'])) {
            $sql .= ' AND m.prix <= :prix_max';
            $params[':prix_max'] = $filters['prix_max'];
        }

        if (!empty($filters['theme'])) {
            $sql .= ' AND m.id_theme = :id_theme';
            $params[':id_theme'] = $filters['theme'];
        }

        if (!empty($filters['regime'])) {
            $sql .= ' AND m.id_regime = :id_regime';
            $params[':id_regime'] = $filters['regime'];
        }

        if (!empty($filters['nb_personnes'])) {
            $sql .= ' AND m.nombre_personne_min <= :nb_personnes';
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

        // Récupérer les plats associés avec leurs allergènes
        $stmt = $this->pdo->prepare('
            SELECT p.* FROM PLAT p
            JOIN PROPOSE mp ON p.id_plat = mp.id_plat
            WHERE mp.id_menu = :id
        ');
        $stmt->execute(['id' => $id]);
        $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pour chaque plat, récupérer les allergènes
        foreach ($plats as &$plat) {
            $stmtAllergene = $this->pdo->prepare('
                SELECT a.* FROM ALLERGENE a
                JOIN PLAT_ALLERGENE c ON a.id_allergene = c.id_allergene
                WHERE c.id_plat = :id_plat
            ');
            $stmtAllergene->execute(['id_plat' => $plat['id_plat']]);
            $plat['allergenes'] = $stmtAllergene->fetchAll(PDO::FETCH_ASSOC);
        }
        $menu['plats'] = $plats;

        // Récupérer les images
        $stmt = $this->pdo->prepare('SELECT url FROM IMAGE_MENU WHERE id_menu = :id');
        $stmt->execute(['id' => $id]);
        $menu['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Récupérer le matériel associé (pour l'affichage et la gestion)
        $stmtMat = $this->pdo->prepare('
            SELECT m.id_materiel, m.libelle, m.description, mm.quantite_par_personne as quantite, m.stock_disponible
            FROM MATERIEL m
            JOIN MENU_MATERIEL mm ON m.id_materiel = mm.id_materiel
            WHERE mm.id_menu = :id
        ');
        $stmtMat->execute(['id' => $id]);
        $menu['materiels'] = $stmtMat->fetchAll(PDO::FETCH_ASSOC);

        return $menu;
    }

    /**
     * Trouve une entité Menu par ID (Logique Commande).
     */
    public function findEntityById(int $id): ?Menu
    {
        $stmt = $this->pdo->prepare("SELECT * FROM MENU WHERE id_menu = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new Menu($data);
    }

    public function updateStock(int $id, int $newStock): bool
    {
        $stmt = $this->pdo->prepare("UPDATE MENU SET stock_disponible = :stock WHERE id_menu = :id");
        return $stmt->execute(['stock' => $newStock, 'id' => $id]);
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
            'conditions' => $data['conditions'] ?? null,
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
        $stmt = $this->pdo->prepare('UPDATE MENU SET stock_disponible = stock_disponible - :quantity WHERE id_menu = :id AND stock_disponible >= :quantity');
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

    /**
     * Ajoute une image à un menu.
     * @param int $menuId
     * @param string $url
     * @param int $position
     * @return bool
     */
    public function addImage(int $menuId, string $url, int $position): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO IMAGE_MENU (id_menu, url, position) VALUES (:menuId, :url, :position)');
        return $stmt->execute(['menuId' => $menuId, 'url' => $url, 'position' => $position]);
    }

    /**
     * Supprime toutes les images d'un menu.
     * @param int $menuId
     * @return bool
     */
    public function deleteImages(int $menuId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM IMAGE_MENU WHERE id_menu = :menuId');
        return $stmt->execute(['menuId' => $menuId]);
    }

    /**
     * Associe un matériel à un menu par défaut.
     */
    public function associateMaterial(int $menuId, int $materielId, int $quantite): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO MENU_MATERIEL (id_menu, id_materiel, quantite_par_personne) VALUES (:menuId, :matId, :qty)');
        return $stmt->execute(['menuId' => $menuId, 'matId' => $materielId, 'qty' => $quantite]);
    }

    /**
     * Dissocie tout le matériel d'un menu.
     */
    public function dissociateAllMaterials(int $menuId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM MENU_MATERIEL WHERE id_menu = :menuId');
        return $stmt->execute(['menuId' => $menuId]);
    }
}
