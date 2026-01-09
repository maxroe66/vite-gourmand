<?php

namespace App\Validators;

class UserValidator
{
    /**
     * Valide les données d'inscription utilisateur.
     * La méthode n'est plus statique.
     * @param array $data
     * @return array [isValid => bool, errors => array]
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Prénom (obligatoire et type string)
        if (empty($data['firstName'])) {
            $errors['firstName'] = 'Le prénom est requis.';
        } elseif (!is_string($data['firstName'])) {
            $errors['firstName'] = 'Le prénom doit être une chaîne de caractères.';
        } elseif (!preg_match('/^[a-zA-ZÀ-ÖØ-öø-ÿ\-\s]+$/u', $data['firstName'])) {
            $errors['firstName'] = 'Le prénom ne doit contenir que des lettres (sans emoji, chiffre ou symbole).';
        }

        // Nom (obligatoire et type string)
        if (empty($data['lastName'])) {
            $errors['lastName'] = 'Le nom de famille est requis.';
        } elseif (!is_string($data['lastName'])) {
            $errors['lastName'] = 'Le nom de famille doit être une chaîne de caractères.';
        }

        // Email (obligatoire, type string, format)
        if (empty($data['email'])) {
            $errors['email'] = "L'adresse email est requise.";
        } elseif (!is_string($data['email'])) {
            $errors['email'] = "L'adresse email doit être une chaîne de caractères.";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Le format de l'adresse email est invalide.";
        }

        // Mot de passe (obligatoire, type string, min 8 caractères, 1 maj, 1 min, 1 chiffre)
        if (empty($data['password'])) {
            $errors['password'] = 'Le mot de passe est requis.';
        } elseif (!is_string($data['password'])) {
            $errors['password'] = 'Le mot de passe doit être une chaîne de caractères.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $data['password'])) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.';
        }

        // GSM / Téléphone (obligatoire, type string, format simple)
        if (empty($data['phone'])) {
            $errors['phone'] = 'Le numéro de téléphone est requis.';
        } elseif (!is_string($data['phone'])) {
            $errors['phone'] = 'Le numéro de téléphone doit être une chaîne de caractères.';
        } elseif (!preg_match('/^[0-9\s\-\+]{10,}$/', $data['phone'])) {
            $errors['phone'] = 'Le format du numéro de téléphone est invalide.';
        }

        // Adresse (obligatoire, type string)
        if (empty($data['address'])) {
            $errors['address'] = 'L\'adresse est requise.';
        } elseif (!is_string($data['address'])) {
            $errors['address'] = 'L\'adresse doit être une chaîne de caractères.';
        } elseif (strlen($data['address']) < 5) {
            $errors['address'] = 'L\'adresse est trop courte (minimum 5 caractères).';
        }

        // Ville (obligatoire, type string)
        if (empty($data['city'])) {
            $errors['city'] = 'La ville est requise.';
        } elseif (!is_string($data['city'])) {
            $errors['city'] = 'La ville doit être une chaîne de caractères.';
        }

        // Code postal (obligatoire, type string, format FR)
        if (empty($data['postalCode'])) {
            $errors['postalCode'] = 'Le code postal est requis.';
        } elseif (!is_string($data['postalCode'])) {
            $errors['postalCode'] = 'Le code postal doit être une chaîne de caractères.';
        } elseif (!preg_match('/^\d{5}$/', $data['postalCode'])) {
            $errors['postalCode'] = 'Le format du code postal est invalide (5 chiffres attendus).';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
