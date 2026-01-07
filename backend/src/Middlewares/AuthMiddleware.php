<?php

namespace App\Middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthMiddleware
{
    private array $config;

    /**
     * Le constructeur reçoit la configuration (sera injectée par le conteneur).
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Exécute la logique du middleware.
     * @throws Exception si l'authentification échoue.
     */
    public function handle(): void
    {
        // 1. Récupérer le token (cookie ou header)
        $token = $this->getTokenFromRequest();
        if (!$token) {
            throw new Exception('Token d\'authentification manquant.');
        }

        // 2. Valider le token
        $secret = $this->config['jwt']['secret'];
        if (!$secret) {
            throw new Exception('La clé secrète JWT n\'est pas configurée côté serveur.');
        }

        try {
            // JWT::decode lèvera une exception si le token est invalide
            JWT::decode($token, new Key($secret, 'HS256'));
            // Si on arrive ici, le token est valide. On ne fait rien de plus.
            // Le routeur continuera vers le contrôleur.
        } catch (Exception $e) {
            // On relance une exception générique pour que le routeur la capture.
            throw new Exception('Token invalide ou expiré.');
        }
    }

    /**
     * Récupère le token depuis le cookie ou le header Authorization.
     */
    private function getTokenFromRequest(): ?string
    {
        // Vérifier le cookie authToken en priorité
        if (isset($_COOKIE['authToken'])) {
            return $_COOKIE['authToken'];
        }

        // Fallback: vérifier le header Authorization
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            // On s'attend à un format "Bearer <token>"
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
