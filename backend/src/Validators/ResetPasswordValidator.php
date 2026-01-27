<?php

namespace App\Validators;

class ResetPasswordValidator
{
    /**
     * Valide les champs pour un reset de mot de passe.
     * Requiert : token non vide, password (8+, 1 maj, 1 min, 1 chiffre).
     * Retourne [isValid => bool, errors => array].
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['token']) || !is_string($data['token'])) {
            $errors['token'] = 'Token requis.';
        }

        if (empty($data['password']) || !is_string($data['password']) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $data['password'])) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
