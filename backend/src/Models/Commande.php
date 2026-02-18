<?php

namespace App\Models;

class Commande
{
    public ?int $id = null;
    public int $userId;
    public int $menuId;
    public string $dateCommande;
    
    // Informations de livraison
    public string $datePrestation;
    public string $heureLivraison;
    public string $adresseLivraison;
    public string $ville;
    public string $codePostal;
    public string $gsm;
    public ?string $remarques = null;
    
    // Tarification (Snapshots)
    public int $nombrePersonnes;
    public int $nombrePersonneMinSnapshot;
    public float $prixMenuUnitaire;
    public float $montantReduction = 0.0;
    public bool $reductionAppliquee = false;
    public float $fraisLivraison = 0.0;
    public float $prixTotal;
    
    // Livraison hors Bordeaux
    public bool $horsBordeaux = false;
    public float $distanceKm = 0.0;
    
    // Statut et suivi
    public string $statut = 'EN_ATTENTE';
    public bool $hasAvis = false;
    public bool $materielPret = false;
    public ?string $dateLivraisonEffective = null;
    public ?string $dateRetourMateriel = null;

    /**
     * @param array $data Données brutes de la BD ou du formulaire
     */
    public function __construct(array $data = null) {
        if ($data) {
            $this->hydrate($data);
        }
    }

    private function hydrate(array $data): void {
        // Custom mapping for ID fields that don't follow snakeToCamel strictly
        $customMapping = [
            'id_commande' => 'id',
            'id_utilisateur' => 'userId',
            'id_menu' => 'menuId'
        ];

        foreach ($data as $key => $value) {
            // 1. Check custom mapping first
            if (isset($customMapping[$key])) {
                $property = $customMapping[$key];
                if (property_exists($this, $property)) {
                    $this->$property = $value;
                }
                continue;
            }

            // 2. Standard camelCase conversion
            $property = $this->snakeToCamel($key);
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    private function snakeToCamel(string $string): string {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }
    
    // Liste des statuts autorisés (Enum)
    public const STATUS_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUS_ACCEPTE = 'ACCEPTE';
    public const STATUS_EN_PREPARATION = 'EN_PREPARATION';
    public const STATUS_EN_LIVRAISON = 'EN_LIVRAISON';
    public const STATUS_LIVRE = 'LIVRE';
    public const STATUS_EN_ATTENTE_RETOUR = 'EN_ATTENTE_RETOUR';
    public const STATUS_TERMINEE = 'TERMINEE';
    public const STATUS_ANNULEE = 'ANNULEE';

    public static function getAllowedStatuses(): array {
        return [
            self::STATUS_EN_ATTENTE,
            self::STATUS_ACCEPTE,
            self::STATUS_EN_PREPARATION,
            self::STATUS_EN_LIVRAISON,
            self::STATUS_LIVRE,
            self::STATUS_EN_ATTENTE_RETOUR,
            self::STATUS_TERMINEE,
            self::STATUS_ANNULEE
        ];
    }
    
    /**
     * Vérifie si la commande peut recevoir un avis.
     * Règle métier : Statut TERMINEE ou LIVRE et pas encore d'avis.
     */
    public function canBeReviewed(): bool
    {
        return in_array($this->statut, [self::STATUS_TERMINEE, self::STATUS_LIVRE], true) && !$this->hasAvis;
    }
}
