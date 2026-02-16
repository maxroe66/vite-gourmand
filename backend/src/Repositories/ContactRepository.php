<?php

namespace App\Repositories;

use PDO;

/**
 * Repository pour la table CONTACT.
 * Gère l'accès aux données des messages de contact.
 */
class ContactRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Insère un nouveau message de contact en base de données.
     *
     * @param string $titre   Titre / objet du message
     * @param string $description Contenu du message
     * @param string $email   Adresse email du visiteur
     * @return int ID du message créé
     */
    public function create(string $titre, string $description, string $email): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO CONTACT (titre, description, email) VALUES (:titre, :description, :email)'
        );

        $stmt->execute([
            ':titre'       => $titre,
            ':description' => $description,
            ':email'       => $email,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
