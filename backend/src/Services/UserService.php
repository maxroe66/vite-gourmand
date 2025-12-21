<?php
namespace App\Services;

use App\Models\User;
use App\Exceptions\UserServiceException;
require_once __DIR__ . '/../Models/User.php';

class UserService
{
    /**
     * Crée un nouvel utilisateur
     * @param array $data
     * @return int userId
     * @throws UserServiceException
     */
    public function createUser(array $data): int
    {
        $user = new User($data);
        try {
            $pdo = $this->getConnection();
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare('SELECT id_utilisateur FROM UTILISATEUR WHERE email = :email');
            $stmt->execute(['email' => $user->email]);
            if ($stmt->fetch()) {
                throw UserServiceException::emailExists();
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
        } catch (UserServiceException $e) {
            throw $e;
        } catch (\PDOException $e) {
            \App\Utils\MonologLogger::getLogger()->error('Erreur PDO lors de la création utilisateur', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            throw UserServiceException::dbError();
        }
    }

    private function getConnection(): \PDO
    {
        // À adapter selon ta config
        $host = 'mysql';
        $db   = 'vite_gourmand';
        $user = 'vite_user';
        $pass = 'vite_pass';
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
