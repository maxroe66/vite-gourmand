<?php

namespace App\Models;

class CommandeStatut
{
    public ?int $id = null;
    public int $commandeId;
    public string $statut;
    public string $dateChangement;
    public int $modifiePar; // User ID
    public ?string $commentaire = null;

    public function __construct(array $data = null) {
        if ($data) {
            $this->hydrate($data);
        }
    }

    private function hydrate(array $data): void {
        foreach ($data as $key => $value) {
            $property = $this->snakeToCamel($key);
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    private function snakeToCamel(string $string): string {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }
}
