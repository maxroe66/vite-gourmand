<?php

namespace App\Models;

class Horaire
{
    public ?int $id = null;
    public string $jour;
    public ?string $heureOuverture = null;
    public ?string $heureFermeture = null;
    public bool $ferme = false;

    /**
     * @param array|null $data Données brutes de la BD
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->hydrate($data);
        }
    }

    private function hydrate(array $data): void
    {
        $this->id = isset($data['id_horaire']) ? (int)$data['id_horaire'] : null;
        $this->jour = $data['jour'] ?? '';
        $this->heureOuverture = $data['heure_ouverture'] ?? null;
        $this->heureFermeture = $data['heure_fermeture'] ?? null;
        $this->ferme = (bool)($data['ferme'] ?? false);
    }

    /**
     * Convertit le modèle en tableau associatif pour la réponse JSON.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'jour' => $this->jour,
            'heureOuverture' => $this->heureOuverture,
            'heureFermeture' => $this->heureFermeture,
            'ferme' => $this->ferme,
        ];
    }
}
