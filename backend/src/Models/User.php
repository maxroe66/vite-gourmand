<?php

namespace App\Models;

final class User
{
    public ?int $id_utilisateur = null;

    public string $email;
    public string $prenom;
    public string $nom;
    public string $gsm;
    public string $adresse_postale;
    public string $ville;
    public string $code_postal;

    /**
     * Mot de passe HASHÉ (ce champ est ce qui part en DB).
     */
    public string $mot_de_passe;

    public string $role = 'UTILISATEUR';

    public function __construct(array $data)
    {
        // Champs attendus par ton API (Postman) : firstName/lastName/phone/address/city/postalCode/password
        $this->email = (string)($data['email'] ?? '');
        $this->prenom = (string)($data['firstName'] ?? '');
        $this->nom = (string)($data['lastName'] ?? '');
        $this->gsm = (string)($data['phone'] ?? '');
        $this->adresse_postale = (string)($data['address'] ?? '');
        $this->ville = (string)($data['city'] ?? '');
        $this->code_postal = (string)($data['postalCode'] ?? '');

        // Supporte 2 cas :
        // - passwordHash fourni (déjà hashé)
        // - password fourni (en clair) => on le hash ici (cas register)
        if (!empty($data['passwordHash'])) {
            $this->mot_de_passe = (string)$data['passwordHash'];
        } else {
            $plainPassword = (string)($data['password'] ?? '');
            $this->mot_de_passe = password_hash($plainPassword, PASSWORD_ARGON2ID);
        }

        if (isset($data['role']) && is_string($data['role']) && $data['role'] !== '') {
            $this->role = $data['role'];
        }
    }
}
