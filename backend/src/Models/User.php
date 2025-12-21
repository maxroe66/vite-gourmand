<?php
namespace App\Models;

class User
{
    public ?int $id_utilisateur = null;
    public string $email;
    public string $prenom;
    public string $nom;
    public string $gsm;
    public string $adresse_postale;
    public string $ville;
    public string $code_postal;
    public string $mot_de_passe;
    public string $role = 'UTILISATEUR';

    public function __construct(array $data)
    {
        $this->email = $data['email'];
        $this->prenom = $data['firstName'];
        $this->nom = $data['lastName'];
        $this->gsm = $data['phone'];
        $this->adresse_postale = $data['address'];
        $this->ville = $data['city'];
        $this->code_postal = $data['postalCode'];
        $this->mot_de_passe = $data['passwordHash'];
        if (isset($data['role'])) {
            $this->role = $data['role'];
        }
    }
}
