<?php
namespace App\Services;

class AuthService
{
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
     * Vérifie le mot de passe
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        // Utiliser password_verify
        return false;
    }

    /**
     * Génère un JWT
     * @param int $userId
     * @param string $role
     * @return string
     */
    public function generateToken(int $userId, string $role): string
    {
        // Génération du JWT
        return '';
    }
}
