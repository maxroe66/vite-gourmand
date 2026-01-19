<?php

namespace App\Validators;

class PlatValidator
{
    /**
     * Valide les données de création ou de mise à jour d'un plat.
     * @param array $data
     * @return array [isValid => bool, errors => array]
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Libellé (obligatoire, string, min 3 caractères)
        if (empty($data['libelle'])) {
            $errors['libelle'] = 'Le libellé est requis.';
        } elseif (!is_string($data['libelle'])) {
            $errors['libelle'] = 'Le libellé doit être une chaîne de caractères.';
        } elseif (strlen($data['libelle']) < 3) {
            $errors['libelle'] = 'Le libellé doit contenir au moins 3 caractères.';
        }

        // Description (optionnelle, string)
        if (isset($data['description']) && !is_string($data['description'])) {
            $errors['description'] = 'La description doit être une chaîne de caractères.';
        }

        // Type (obligatoire, string, valeurs autorisées)
        $validTypes = ['ENTREE', 'PLAT', 'DESSERT'];
        if (empty($data['type'])) {
            $errors['type'] = 'Le type est requis.';
        } elseif (!is_string($data['type'])) {
            $errors['type'] = 'Le type doit être une chaîne de caractères.';
        } elseif (!in_array($data['type'], $validTypes)) {
            $errors['type'] = 'Le type doit être l\'un des suivants : ' . implode(', ', $validTypes) . '.';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}