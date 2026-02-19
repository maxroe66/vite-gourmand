<?php

namespace App\Validators;

class EmployeeValidator
{
    /**
     * Valide les données minimales pour créer un employé.
     * Champs requis : email, password, firstName, lastName.
     * Champs facultatifs : phone, address, city, postalCode.
     * Retourne [isValid => bool, errors => array].
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Email
        if (empty($data['email']) || !is_string($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Email invalide.";
        }

        // Mot de passe (10+, 1 maj, 1 min, 1 chiffre, 1 spécial)
        if (empty($data['password']) || !is_string($data['password']) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{10,}$/', $data['password'])) {
            $errors['password'] = "Le mot de passe doit contenir au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
        }

        // Prénom
        if (empty($data['firstName']) || !is_string($data['firstName'])) {
            $errors['firstName'] = 'Le prénom est requis.';
        }

        // Nom
        if (empty($data['lastName']) || !is_string($data['lastName'])) {
            $errors['lastName'] = 'Le nom est requis.';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
