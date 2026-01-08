<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use Psr\Log\LoggerInterface;

class AuthService
{
    private array $config;
    private LoggerInterface $logger;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Hash le mot de passe
     * @param string $password
     * @return string
     */
    public function hashPassword(string $password): string
    {
        // Utilise password_hash avec l'algorithme par défaut (bcrypt ou Argon2)
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Vérifie le mot de passe et lève une exception si invalide.
     * 
     * @param string $password Le mot de passe en clair fourni par l'utilisateur
     * @param string $hash Le hash stocké en base de données
     * @return void
     * @throws InvalidCredentialsException Si le mot de passe ne correspond pas au hash
     */
    public function verifyPassword(string $password, string $hash): void
    {
        if (!password_verify($password, $hash)) {
            $this->logger->warning('Tentative de connexion avec mot de passe invalide');
            throw InvalidCredentialsException::invalidCredentials();
        }
        
        $this->logger->info('Mot de passe vérifié avec succès');
    }

    /**
     * Génère un JWT
     * @param int $userId
     * @param string $role
     * @return string
     */
    public function generateToken(int $userId, string $role): string
    {
        $secret = $this->config['jwt']['secret'];
        $expire = $this->config['jwt']['expire'];

        $payload = [
            'iss' => 'vite-gourmand',  // émetteur
            'sub' => $userId,           // sujet (user ID)
            'role' => $role,
            'iat' => time(),            // émis à
            'exp' => time() + $expire   // expire à
        ];

        $token = \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
        $this->logger->info('Token JWT généré', ['userId' => $userId, 'role' => $role]);

        return $token;
    }
}
