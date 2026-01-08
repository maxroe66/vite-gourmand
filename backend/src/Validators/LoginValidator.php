<?php

namespace App\Validators;

class LoginValidator
{
    /**
     * Valide les données de connexion utilisateur
     * @param array $data
     * @return array [isValid => bool, errors => array]
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Email (obligatoire, type string, format)
        if (empty($data['email'])) {
            $errors['email'] = "L'email est requis.";
        } elseif (!is_string($data['email'])) {
            $errors['email'] = "L'email doit être une chaîne de caractères.";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'email n'est pas valide.";
        }

        // Mot de passe (obligatoire, type string)
        // Note: Pas de validation de complexité pour le login, 
        // on vérifie juste que le champ existe (le hash sera vérifié par AuthService)
        if (empty($data['password'])) {
            $errors['password'] = 'Le mot de passe est requis.';
        } elseif (!is_string($data['password'])) {
            $errors['password'] = 'Le mot de passe doit être une chaîne de caractères.';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
