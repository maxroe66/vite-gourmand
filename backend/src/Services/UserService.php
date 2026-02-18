<?php

namespace App\Services;

use App\Exceptions\UserServiceException;
use App\Repositories\UserRepository;
use PDOException;
use Psr\Log\LoggerInterface;

class UserService
{
    private UserRepository $userRepository;
    private LoggerInterface $logger;

    public function __construct(UserRepository $userRepository, LoggerInterface $logger)
    {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    /**
     * Crée un nouvel utilisateur
     * @param array $data
     * @return int userId
     * @throws UserServiceException
     */
    public function createUser(array $data): int
    {
        try {
            // Vérifier si l'email existe déjà en utilisant le Repository
            if ($this->userRepository->findByEmail($data['email'])) {
                throw UserServiceException::emailExists();
            }

            // Appeler le Repository pour créer l'utilisateur
            return $this->userRepository->create($data);

        } catch (PDOException $e) {
            // Log l'erreur PDO et relance une exception de service
            $this->logger->error('Erreur PDO lors de la création utilisateur', [
                'error' => $e->getMessage()
            ]);
            throw new UserServiceException('Erreur de base de données lors de la création de l\'utilisateur.');
        }
    }

    /**
     * Trouve un utilisateur par email
     * @param string $email
     * @return array|null Tableau associatif avec les données de l'utilisateur ou null si non trouvé
     */
    public function findByEmail(string $email): ?array
    {
        $result = $this->userRepository->findByEmail($email);
        // PDO::fetch() retourne false si aucune ligne trouvée, on le convertit en null
        return $result === false ? null : $result;
    }

    /**
     * Trouve un utilisateur par ID
     * @param int $id
     * @return array|null
     */
    public function getUserById(int $id): ?array
    {
        $result = $this->userRepository->findById($id);
        return $result === false ? null : $result;
    }

    /**
     * Récupère la liste des employés
     * @return array
     */
    public function getEmployees(): array
    {
        return $this->userRepository->findAllByRole('EMPLOYE');
    }

    /**
     * Met à jour le profil d'un utilisateur
     * @param int $userId
     * @param array $data Données validées (clés frontend : firstName, lastName, phone, address, city, postalCode)
     * @return array Données utilisateur mises à jour
     * @throws UserServiceException
     */
    public function updateProfile(int $userId, array $data): array
    {
        try {
            // Mapping clés frontend → colonnes BDD
            $dbData = [];
            if (isset($data['firstName'])) $dbData['prenom'] = trim($data['firstName']);
            if (isset($data['lastName']))  $dbData['nom'] = trim($data['lastName']);
            if (isset($data['phone']))     $dbData['gsm'] = trim($data['phone']);
            if (isset($data['address']))   $dbData['adresse_postale'] = trim($data['address']);
            if (isset($data['city']))      $dbData['ville'] = trim($data['city']);
            if (isset($data['postalCode'])) $dbData['code_postal'] = trim($data['postalCode']);

            if (empty($dbData)) {
                throw new UserServiceException('Aucune donnée à mettre à jour.');
            }

            $this->userRepository->updateProfile($userId, $dbData);

            // Retourner les données fraîches
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                throw new UserServiceException('Utilisateur introuvable après mise à jour.');
            }
            return $user;
        } catch (PDOException $e) {
            $this->logger->error('Erreur PDO lors de la mise à jour du profil', ['userId' => $userId, 'error' => $e->getMessage()]);
            throw new UserServiceException('Erreur de base de données lors de la mise à jour du profil.');
        }
    }

    /**
     * Désactive un compte utilisateur (soft delete)
     * @param int $id
     * @throws UserServiceException
     */
    public function disableUser(int $id): void
    {
        try {
            $this->userRepository->updateActif($id, false);
        } catch (PDOException $e) {
            $this->logger->error('Erreur PDO lors de la désactivation utilisateur', ['$id' => $id, 'error' => $e->getMessage()]);
            throw new UserServiceException("Erreur lors de la désactivation de l'utilisateur.");
        }
    }
}
