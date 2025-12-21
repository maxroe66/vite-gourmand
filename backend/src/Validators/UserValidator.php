<?php
namespace App\Validators;

class UserValidator
{
    /**
     * Valide les données d'inscription utilisateur
     * @param array $data
     * @return array [isValid => bool, errors => array]
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Prénom (obligatoire)
        if (empty($data['firstName'])) {
            $errors['firstName'] = 'Le prénom est requis.';
        }

        // Nom (obligatoire)
        if (empty($data['lastName'])) {
            $errors['lastName'] = 'Le nom de famille est requis.';
        }

        // Email (obligatoire, format)
        if (empty($data['email'])) {
            $errors['email'] = "L'adresse email est requise.";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Le format de l'adresse email est invalide.";
        }

        // Mot de passe (obligatoire, min 8 caractères, 1 maj, 1 min, 1 chiffre)
        if (empty($data['password'])) {
            $errors['password'] = 'Le mot de passe est requis.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $data['password'])) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.';
        }

        // GSM / Téléphone (obligatoire, format simple)
        if (empty($data['phone'])) {
            $errors['phone'] = 'Le numéro de téléphone est requis.';
        } elseif (!preg_match('/^[0-9\s\-\+]{10,}$/', $data['phone'])) {
            $errors['phone'] = 'Le format du numéro de téléphone est invalide.';
        }

        // Adresse (obligatoire)
        if (empty($data['address'])) {
            $errors['address'] = 'L\'adresse est requise.';
        } elseif (strlen($data['address']) < 5) {
            $errors['address'] = 'L\'adresse est trop courte (minimum 5 caractères).';
        }

        // Ville (obligatoire)
        if (empty($data['city'])) {
            $errors['city'] = 'La ville est requise.';
        }

        // Code postal (obligatoire, format FR)
        if (empty($data['postalCode'])) {
            $errors['postalCode'] = 'Le code postal est requis.';
        } elseif (!preg_match('/^\d{5}$/', $data['postalCode'])) {
            $errors['postalCode'] = 'Le format du code postal est invalide (5 chiffres attendus).';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
