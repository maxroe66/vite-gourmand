<?php

namespace App\Validators;

class MenuValidator
{
    /**
     * Valide les données de création ou de mise à jour d'un menu.
     * @param array $data
     * @return array [isValid => bool, errors => array]
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Titre (obligatoire, string, min 3 caractères)
        if (empty($data['titre'])) {
            $errors['titre'] = 'Le titre est requis.';
        } elseif (!is_string($data['titre'])) {
            $errors['titre'] = 'Le titre doit être une chaîne de caractères.';
        } elseif (strlen($data['titre']) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
        }

        // Description (obligatoire, string, min 10 caractères)
        if (empty($data['description'])) {
            $errors['description'] = 'La description est requise.';
        } elseif (!is_string($data['description'])) {
            $errors['description'] = 'La description doit être une chaîne de caractères.';
        } elseif (strlen($data['description']) < 10) {
            $errors['description'] = 'La description doit contenir au moins 10 caractères.';
        }

        // Prix (obligatoire, numérique, positif)
        if (empty($data['prix'])) {
            $errors['prix'] = 'Le prix est requis.';
        } elseif (!is_numeric($data['prix'])) {
            $errors['prix'] = 'Le prix doit être une valeur numérique.';
        } elseif ($data['prix'] <= 0) {
            $errors['prix'] = 'Le prix doit être un nombre positif.';
        }

        // Nombre de personnes minimum (obligatoire, entier, positif)
        if (empty($data['nb_personnes_min'])) {
            $errors['nb_personnes_min'] = 'Le nombre de personnes minimum est requis.';
        } elseif (!is_int($data['nb_personnes_min'])) {
            $errors['nb_personnes_min'] = 'Le nombre de personnes doit être un entier.';
        } elseif ($data['nb_personnes_min'] <= 0) {
            $errors['nb_personnes_min'] = 'Le nombre de personnes doit être un entier positif.';
        }

        // Stock (obligatoire, entier, positif ou nul)
        if (!isset($data['stock'])) {
            $errors['stock'] = 'Le stock est requis.';
        } elseif (!is_int($data['stock'])) {
            $errors['stock'] = 'Le stock doit être un entier.';
        } elseif ($data['stock'] < 0) {
            $errors['stock'] = 'Le stock ne peut pas être négatif.';
        }
        
        // Conditions (optionnel, string)
        if (isset($data['conditions']) && !is_string($data['conditions'])) {
            $errors['conditions'] = 'Les conditions doivent être une chaîne de caractères.';
        }

        // id_theme (obligatoire, entier)
        if (empty($data['id_theme'])) {
            $errors['id_theme'] = 'Le thème est requis.';
        } elseif (!is_int($data['id_theme'])) {
            $errors['id_theme'] = 'L\'ID du thème doit être un entier.';
        }

        // id_regime (obligatoire, entier)
        if (empty($data['id_regime'])) {
            $errors['id_regime'] = 'Le régime est requis.';
        } elseif (!is_int($data['id_regime'])) {
            $errors['id_regime'] = 'L\'ID du régime doit être un entier.';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
