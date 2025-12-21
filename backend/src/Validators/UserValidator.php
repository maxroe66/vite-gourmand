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
            $errors['firstName'] = 'Prénom requis.';
        }

        // Nom (obligatoire)
        if (empty($data['lastName'])) {
            $errors['lastName'] = 'Nom requis.';
        }

        // Email (obligatoire, format)
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        }

        // Mot de passe (obligatoire, min 8 caractères, 1 maj, 1 min, 1 chiffre)
        if (empty($data['password']) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $data['password'])) {
            $errors['password'] = 'Mot de passe trop faible.';
        }

        // GSM / Téléphone (obligatoire, format simple)
        if (empty($data['phone'])) {
            $errors['phone'] = 'Téléphone requis.';
        } elseif (!preg_match('/^[0-9\s\-\+]{10,}$/', $data['phone'])) {
            $errors['phone'] = 'Téléphone invalide.';
        }

        // Adresse (obligatoire)
        if (empty($data['address'])) {
            $errors['address'] = 'Adresse requise.';
        } elseif (strlen($data['address']) < 5) {
            $errors['address'] = 'Adresse trop courte.';
        }

        // Ville (obligatoire)
        if (empty($data['city'])) {
            $errors['city'] = 'Ville requise.';
        }

        // Code postal (obligatoire, format FR)
        if (empty($data['postalCode'])) {
            $errors['postalCode'] = 'Code postal requis.';
        } elseif (!preg_match('/^\d{5}$/', $data['postalCode'])) {
            $errors['postalCode'] = 'Code postal invalide.';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
