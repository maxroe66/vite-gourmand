<?php
namespace Backend\Services;

use Backend\Models\User;
require_once __DIR__ . '/../models/User.php';

class UserService
{
    /**
     * Crée un nouvel utilisateur
     * @param array $data
     * @return int|null userId ou null en cas d'échec
     */
    public function createUser(array $data): ?int
    {
        $user = new User($data);
        try {
            $pdo = $this->getConnection();
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute(['email' => $user->email]);
            if ($stmt->fetch()) {
                return null; // Email déjà utilisé
            }
            // Insertion
            $stmt = $pdo->prepare('INSERT INTO UTILISATEUR (email, prenom, nom, gsm, adresse_postale, ville, code_postal, mot_de_passe, role) VALUES (:email, :prenom, :nom, :gsm, :adresse_postale, :ville, :code_postal, :mot_de_passe, :role)');
            $stmt->execute([
                'email' => $user->email,
                'prenom' => $user->prenom,
                'nom' => $user->nom,
                'gsm' => $user->gsm,
                'adresse_postale' => $user->adresse_postale,
                'ville' => $user->ville,
                'code_postal' => $user->code_postal,
                'mot_de_passe' => $user->mot_de_passe,
                'role' => $user->role
            ]);
            return (int)$pdo->lastInsertId();
        } catch (\PDOException $e) {
            // Log possible ici
            return null;
        }
    }

    private function getConnection(): \PDO
    {
        // À adapter selon ta config
        $host = 'localhost';
        $db   = 'vite_et_gourmand';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new \PDO($dsn, $user, $pass, $options);
    }
}
