<?php

namespace App\Validators;

class ResetPasswordValidator
{
    /**
     * Valide les champs pour un reset de mot de passe.
     * Requiert : token non vide, password (10+, 1 maj, 1 min, 1 chiffre, 1 spécial).
     * Retourne [isValid => bool, errors => array].
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['token']) || !is_string($data['token'])) {
            $errors['token'] = 'Token requis.';
        }

        if (empty($data['password']) || !is_string($data['password']) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{10,}$/', $data['password'])) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
