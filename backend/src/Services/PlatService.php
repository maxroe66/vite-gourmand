<?php

// 1. Correction du namespace
namespace App\Services; 

use App\Repositories\PlatRepository;
use App\Repositories\AllergeneRepository;
use Exception;

class PlatService
{
    private PlatRepository $platRepository;
    private AllergeneRepository $allergeneRepository;

    public function __construct(
        PlatRepository $platRepository,
        AllergeneRepository $allergeneRepository
    ) {
        $this->platRepository = $platRepository;
        $this->allergeneRepository = $allergeneRepository;
    }

    /**
     * 2. Correction du commentaire
     * Crée un nouveau plat et associe ses allergènes.
     * @param array $data
     * @return int
     */
    public function createDish(array $data): int
    {
        // 4. Séparation des données (recommandé)
        $platData = [
            'libelle' => $data['libelle'],
            'description' => $data['description'],
            'type' => $data['type']
        ];
        $platId = $this->platRepository->create($platData);

        if (!empty($data['allergenIds'])) {
            // Appel de la méthode que nous allons créer
            $this->associateAllergens($platId, $data['allergenIds']);
        }

        return $platId;
    }

    // 3. Création de la méthode manquante
    /**
     * Associe une liste d'allergènes à un plat.
     * @param int $platId
     * @param array $allergenIds
     */
    public function associateAllergens(int $platId, array $allergenIds): void
    {
        $this->platRepository->dissociateAllAllergens($platId);

        foreach ($allergenIds as $allergeneId) {
            $this->platRepository->associateAllergen($platId, $allergeneId);
        }
    }
    // 5. Ajout de la méthode de mise à jour
    /**
     * Met à jour un plat et ses allergènes.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateDish(int $id, array $data): bool
    {
        $platData = [
            'libelle' => $data['libelle'],
            'description' => $data['description'],
            'type' => $data['type']
        ];

        $updated = $this->platRepository->update($id, $platData);

        if (isset($data['allergenIds'])) {
            $this->associateAllergens($id, $data['allergenIds']);
        }

        return $updated;
    }

    // 6. Ajout de la méthode de suppression
    /**
     * Supprime un plat après avoir vérifié qu'il n'est pas utilisé.
     * @param int $id
     * @return bool
     * @throws Exception Si le plat est utilisé dans un menu.
     */
    public function deleteDish(int $id): bool
    {
        if ($this->platRepository->isUsedInMenu($id)) {
            throw new Exception("Impossible de supprimer ce plat car il est utilisé dans au moins un menu.");
        }

        // La méthode delete du repository s'occupe de supprimer les associations
        return $this->platRepository->delete($id);
    }


}