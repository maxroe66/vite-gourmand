<?php
namespace Backend\Services;

class AuthService
{
    /**
     * Hash le mot de passe
     * @param string $password
     * @return string
     */
    public function hashPassword(string $password): string
    {
        // Utiliser password_hash (Argon2 ou bcrypt)
        return '';
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
