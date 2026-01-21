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

    public function findById(int $id): ?Menu
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
}
