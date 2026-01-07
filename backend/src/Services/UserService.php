<?php

namespace App\Services;

use App\Exceptions\UserServiceException;
use App\Repositories\UserRepository;
use PDOException;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
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
            \App\Utils\MonologLogger::getLogger()->error('Erreur PDO lors de la création utilisateur', [
                'error' => $e->getMessage()
            ]);
            throw new UserServiceException('Erreur de base de données lors de la création de l\'utilisateur.');
        }
    }
}
