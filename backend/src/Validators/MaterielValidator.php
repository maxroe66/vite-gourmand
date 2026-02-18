<?php

namespace App\Validators;

class MaterielValidator
{
    /**
     * Valide les données de création/modification d'un matériel.
     * @param array $data
     * @return array [isValid => bool, errors => array]
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Libellé (obligatoire, max 100 caractères)
        if (empty($data['libelle'])) {
            $errors['libelle'] = 'Le libellé est requis.';
        } elseif (!is_string($data['libelle'])) {
            $errors['libelle'] = 'Le libellé doit être une chaîne de caractères.';
        } elseif (strlen($data['libelle']) > 100) {
            $errors['libelle'] = 'Le libellé ne doit pas dépasser 100 caractères.';
        }

        // Description (optionnel, type string)
        if (isset($data['description']) && $data['description'] !== null && $data['description'] !== '') {
            if (!is_string($data['description'])) {
                $errors['description'] = 'La description doit être une chaîne de caractères.';
            }
        }

        // Valeur unitaire (obligatoire, > 0)
        if (!isset($data['valeur_unitaire']) && !isset($data['valeurUnitaire'])) {
            $errors['valeur_unitaire'] = 'La valeur unitaire est requise.';
        } else {
            $valeur = $data['valeur_unitaire'] ?? $data['valeurUnitaire'] ?? null;
            if (!is_numeric($valeur) || (float)$valeur <= 0) {
                $errors['valeur_unitaire'] = 'La valeur unitaire doit être un nombre supérieur à 0.';
            }
        }

        // Stock disponible (obligatoire, >= 0)
        if (!isset($data['stock_disponible']) && !isset($data['stockDisponible'])) {
            $errors['stock_disponible'] = 'Le stock disponible est requis.';
        } else {
            $stock = $data['stock_disponible'] ?? $data['stockDisponible'] ?? null;
            if (!is_numeric($stock) || (int)$stock < 0) {
                $errors['stock_disponible'] = 'Le stock disponible doit être un nombre positif ou nul.';
            }
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
