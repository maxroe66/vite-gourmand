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

        // Nombre de personnes minimum (obligatoire, entier, positif, max 500)
        if (empty($data['nb_personnes_min'])) {
            $errors['nb_personnes_min'] = 'Le nombre de personnes minimum est requis.';
        } elseif (filter_var($data['nb_personnes_min'], FILTER_VALIDATE_INT) === false) {
            $errors['nb_personnes_min'] = 'Le nombre de personnes doit être un entier.';
        } elseif ((int)$data['nb_personnes_min'] <= 0) {
            $errors['nb_personnes_min'] = 'Le nombre de personnes doit être un entier positif.';
        } elseif ((int)$data['nb_personnes_min'] > 500) {
            $errors['nb_personnes_min'] = 'Le nombre de personnes ne peut pas dépasser 500.';
        }

        // Stock (obligatoire, entier, positif ou nul, max 10000)
        if (!isset($data['stock'])) {
            $errors['stock'] = 'Le stock est requis.';
        } elseif (filter_var($data['stock'], FILTER_VALIDATE_INT) === false && $data['stock'] !== 0 && $data['stock'] !== '0') {
            $errors['stock'] = 'Le stock doit être un entier.';
        } elseif ((int)$data['stock'] < 0) {
            $errors['stock'] = 'Le stock ne peut pas être négatif.';
        } elseif ((int)$data['stock'] > 10000) {
            $errors['stock'] = 'Le stock ne peut pas dépasser 10000.';
        }
        
        // Conditions (optionnel, string)
        if (isset($data['conditions']) && !is_string($data['conditions'])) {
            $errors['conditions'] = 'Les conditions doivent être une chaîne de caractères.';
        }

        // id_theme (obligatoire, entier positif)
        if (empty($data['id_theme'])) {
            $errors['id_theme'] = 'Le thème est requis.';
        } elseif (filter_var($data['id_theme'], FILTER_VALIDATE_INT) === false) {
            $errors['id_theme'] = 'L\'ID du thème doit être un entier.';
        } elseif ((int)$data['id_theme'] <= 0) {
            $errors['id_theme'] = 'L\'ID du thème doit être un entier positif.';
        }

        // id_regime (obligatoire, entier positif)
        if (empty($data['id_regime'])) {
            $errors['id_regime'] = 'Le régime est requis.';
        } elseif (filter_var($data['id_regime'], FILTER_VALIDATE_INT) === false) {
            $errors['id_regime'] = 'L\'ID du régime doit être un entier.';
        } elseif ((int)$data['id_regime'] <= 0) {
            $errors['id_regime'] = 'L\'ID du régime doit être un entier positif.';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
