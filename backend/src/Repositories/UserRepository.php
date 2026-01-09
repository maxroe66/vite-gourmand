<?php

namespace App\Repositories;

use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Trouve un utilisateur par son email.
     * @param string $email
     * @return array|false Tableau associatif des données utilisateur ou false si non trouvé
     */
    public function findByEmail(string $email)
    {
        $stmt = $this->pdo->prepare('
            SELECT id_utilisateur AS id, email, prenom, nom, gsm, 
                   adresse_postale, ville, code_postal, mot_de_passe AS passwordHash, 
                   role, actif, date_creation 
            FROM UTILISATEUR 
            WHERE email = :email
        ');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouvel utilisateur en base de données.
     * @param array $data
     * @return int L'ID du nouvel utilisateur.
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO UTILISATEUR (email, prenom, nom, gsm, adresse_postale, ville, code_postal, mot_de_passe, role) 
             VALUES (:email, :prenom, :nom, :gsm, :adresse_postale, :ville, :code_postal, :mot_de_passe, :role)'
        );

        $stmt->execute([
            'email' => $data['email'],
            'prenom' => $data['firstName'],
            'nom' => $data['lastName'],
            'gsm' => $data['phone'],
            'adresse_postale' => $data['address'],
            'ville' => $data['city'],
            'code_postal' => $data['postalCode'],
            'mot_de_passe' => $data['passwordHash'],
            'role' => $data['role'] ?? 'UTILISATEUR'
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Met à jour le mot de passe d'un utilisateur
     * @param int $userId
     * @param string $passwordHash
     */
    public function updatePassword(int $userId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare("UPDATE UTILISATEUR SET mot_de_passe = :password WHERE id_utilisateur = :id");
        $stmt->execute([
            'password' => $passwordHash,
            'id' => $userId
        ]);
    }
}
