<?php

namespace App\Models;

class Menu
{
    public ?int $id = null;
    public string $titre;
    public int $nombrePersonneMin;
    public float $prix;
    public int $stockDisponible;
    public bool $actif;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    private function hydrate(array $data): void
    {
        if (isset($data['id_menu'])) $this->id = (int)$data['id_menu'];
        if (isset($data['titre'])) $this->titre = $data['titre'];
        if (isset($data['nombre_personne_min'])) $this->nombrePersonneMin = (int)$data['nombre_personne_min'];
        if (isset($data['prix'])) $this->prix = (float)$data['prix'];
        if (isset($data['stock_disponible'])) $this->stockDisponible = (int)$data['stock_disponible'];
        if (isset($data['actif'])) $this->actif = (bool)$data['actif'];
    }
}
