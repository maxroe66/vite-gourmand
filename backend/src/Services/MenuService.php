<?php

namespace App\Services;

use App\Repositories\MenuRepository;
use App\Repositories\PlatRepository;

class MenuService
{
    private MenuRepository $menuRepository;
    private PlatRepository $platRepository;

    public function __construct(MenuRepository $menuRepository, PlatRepository $platRepository)
    {
        $this->menuRepository = $menuRepository;
        $this->platRepository = $platRepository;
    }

    /**
     * Récupère les menus avec filtres.
     * @param array $filters
     * @return array
     */
    public function getMenusWithFilters(array $filters): array
    {
        // Ici, on pourrait ajouter de la logique de transformation des filtres si nécessaire
        return $this->menuRepository->findAll($filters);
    }

    /**
     * Récupère le détail d'un menu.
     * @param int $id
     * @return array|false
     */
    public function getMenuDetails(int $id)
    {
        return $this->menuRepository->findById($id);
    }

    /**
     * Crée un nouveau menu et associe les plats.
     * @param array $data
     * @return int
     */
    public function createMenu(array $data): int
    {
        // On sépare les données du menu des plats associés
        $menuData = $data;
        unset($menuData['plats']);

        $menuId = $this->menuRepository->create($menuData);

        if (!empty($data['plats'])) {
            $this->associateDishes($menuId, $data['plats']);
        }
        
        // La gestion des images sera ajoutée ici plus tard
        // if (!empty($data['images'])) {
        //     $this->addImages($menuId, $data['images']);
        // }

        return $menuId;
    }

    /**
     * Met à jour un menu et ses associations.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateMenu(int $id, array $data): bool
    {
        $menuData = $data;
        unset($menuData['plats']);

        $this->menuRepository->update($id, $menuData);

        if (isset($data['plats'])) {
            $this->associateDishes($id, $data['plats']);
        }

        return true;
    }

    /**
     * Supprime logiquement un menu.
     * @param int $id
     * @return bool
     */
    public function deleteMenu(int $id): bool
    {
        // On pourrait ajouter une vérification ici pour empêcher la suppression si des commandes sont en cours
        return $this->menuRepository->delete($id);
    }

    /**
     * Associe des plats à un menu.
     * @param int $menuId
     * @param array $dishes (ex: ['entrees' => [1, 2], 'plats' => [3], 'desserts' => [4]] OU [1, 2, 3, 4])
     */
    public function associateDishes(int $menuId, array $dishes): void
    {
        // D'abord, on supprime les anciennes associations pour éviter les doublons
        $this->menuRepository->dissociateAllDishes($menuId);

        $position = 1;

        // Ensuite, on ajoute les nouvelles associations
        // On gère à la fois le format catégorisé (tableau de tableaux) et le format plat (tableau d'IDs)
        foreach ($dishes as $val) {
            if (is_array($val)) {
                // Format catégorisé : ['entrees' => [1, 2], ...]
                foreach ($val as $platId) {
                    $this->menuRepository->associateDish($menuId, (int)$platId, $position++);
                }
            } else {
                // Format plat : [1, 2, 3, ...]
                $this->menuRepository->associateDish($menuId, (int)$val, $position++);
            }
        }
    }
    
    /**
     * Calcule le prix d'un menu pour un certain nombre de personnes avec une réduction.
     * @param int $menuId
     * @param int $nbPersonnes
     * @return float|null
     */
    public function calculatePrice(int $menuId, int $nbPersonnes): ?float
    {
        $menu = $this->menuRepository->findById($menuId);
        if (!$menu) {
            return null;
        }

        if ($nbPersonnes < $menu['nb_personnes_min']) {
            // On pourrait lancer une exception ici
            return null; 
        }

        $prixTotal = $menu['prix'] * $nbPersonnes;

        // Appliquer une réduction de 10% si le nombre de personnes est au moins 5 de plus que le minimum requis
        if ($nbPersonnes >= $menu['nb_personnes_min'] + 5) {
            $prixTotal *= 0.90;
        }

        return $prixTotal;
    }

    /**
     * Vérifie la disponibilité d'un menu.
     * @param int $menuId
     * @param int $quantity
     * @return bool
     */
    public function checkAvailability(int $menuId, int $quantity): bool
    {
        $menu = $this->menuRepository->findById($menuId);
        return $menu && $menu['stock'] >= $quantity && $menu['actif'];
    }
}
