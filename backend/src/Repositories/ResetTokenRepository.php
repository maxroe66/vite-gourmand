<?php

namespace App\Repositories;

use PDO;

class ResetTokenRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crée un token de réinitialisation pour un utilisateur
     * @param int $userId
     * @param string $token
     * @param string $expiration DATETIME format 'Y-m-d H:i:s'
     */
    public function create(int $userId, string $token, string $expiration): void
    {
        // 1. Invalider les anciens tokens de cet utilisateur (pour éviter qu'il y en ait 50 valides)
        $this->invalidateTokensForUser($userId);
        
        // 2. Insérer le nouveau
        $stmt = $this->pdo->prepare("
            INSERT INTO RESET_TOKEN (id_utilisateur, token, expiration, utilise) 
            VALUES (:userId, :token, :expiration, 0)
        ");
        
        $stmt->execute([
            'userId' => $userId,
            'token' => $token,
            'expiration' => $expiration
        ]);
    }

    /**
     * Trouve un token valide (non utilisé et non expiré)
     * @param string $token
     * @return array|null
     */
    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM RESET_TOKEN 
            WHERE token = :token 
            AND utilise = 0 
            AND expiration > NOW()
        ");
        $stmt->execute(['token' => $token]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    /**
     * Marque un token comme utilisé
     * @param int $tokenId
     */
    public function markAsUsed(int $tokenId): void
    {
        $stmt = $this->pdo->prepare("UPDATE RESET_TOKEN SET utilise = 1 WHERE id_token = :id");
        $stmt->execute(['id' => $tokenId]);
    }

    /**
     * Invalide tous les tokens non utilisés d'un utilisateur
     * @param int $userId
     */
    public function invalidateTokensForUser(int $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE RESET_TOKEN SET utilise = 1 WHERE id_utilisateur = :userId AND utilise = 0");
        $stmt->execute(['userId' => $userId]);
    }

    /**
     * Trouve le dernier token non utilisé pour un utilisateur.
     * Uniquement pour les tests automatisés.
     * @param int $userId
     * @return array|null
     */
    public function findLatestTokenForUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM RESET_TOKEN 
            WHERE id_utilisateur = :userId 
            AND utilise = 0 
            ORDER BY expiration DESC
            LIMIT 1
        ");
        $stmt->execute(['userId' => $userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }
}
