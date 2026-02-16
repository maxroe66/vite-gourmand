<?php

namespace App\Validators;

/**
 * Validateur pour les messages de contact.
 * Valide les données soumises via le formulaire de contact public.
 */
class ContactValidator
{
    /**
     * Valide les données du formulaire de contact.
     *
     * @param array $data Données à valider (titre, email, description)
     * @return array ['isValid' => bool, 'errors' => array]
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Titre (obligatoire, string, max 150 caractères)
        if (empty($data['titre'])) {
            $errors['titre'] = 'Le titre est requis.';
        } elseif (!is_string($data['titre'])) {
            $errors['titre'] = 'Le titre doit être une chaîne de caractères.';
        } elseif (mb_strlen(trim($data['titre'])) > 150) {
            $errors['titre'] = 'Le titre ne doit pas dépasser 150 caractères.';
        } elseif (mb_strlen(trim($data['titre'])) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
        }

        // Email (obligatoire, format valide)
        if (empty($data['email'])) {
            $errors['email'] = "L'adresse email est requise.";
        } elseif (!is_string($data['email'])) {
            $errors['email'] = "L'adresse email doit être une chaîne de caractères.";
        } elseif (!filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Le format de l'adresse email est invalide.";
        }

        // Description (obligatoire, string, min 10 caractères)
        if (empty($data['description'])) {
            $errors['description'] = 'Le message est requis.';
        } elseif (!is_string($data['description'])) {
            $errors['description'] = 'Le message doit être une chaîne de caractères.';
        } elseif (mb_strlen(trim($data['description'])) < 10) {
            $errors['description'] = 'Le message doit contenir au moins 10 caractères.';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
