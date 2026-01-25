<?php

namespace App\Models;

class Materiel
{
    public int $id;
    public string $nom;
    public string $description;
    public float $valeur;
    public int $stock;
    public bool $disponible;

    public function __construct(array $data)
    {
        $this->id = (int)($data['id_materiel'] ?? 0);
        $this->nom = $data['nom'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->valeur = (float)($data['valeur_unitaire'] ?? 0.0);
        $this->stock = (int)($data['stock_disponible'] ?? 0);
        $this->disponible = (bool)($data['disponible'] ?? true);
    }
}
