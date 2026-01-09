<?php

namespace App\Validators;

class LoginValidator
{
    /**
     * Valide les données de connexion utilisateur.
     * La méthode n'est plus statique.
     * @param array $data
     * @return array [isValid => bool, errors => array]
     */
    public function validate(array $data): array
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
