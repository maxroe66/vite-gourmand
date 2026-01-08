<?php

namespace App\Middlewares;

use App\Core\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use Psr\Log\LoggerInterface;

class AuthMiddleware
{
    private array $config;
    private LoggerInterface $logger;

    /**
     * Le constructeur reçoit la configuration et le logger (injectés par le conteneur).
     */
    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Exécute la logique du middleware.
     * Enrichit l'objet Request avec les données utilisateur si le token est valide.
     * @throws Exception si l'authentification échoue.
     */
    public function handle(Request $request): void
    {
        // 1. Récupérer le token (cookie ou header)
        $token = $this->getTokenFromRequest();
        if (!$token) {
            throw new Exception('Token d\'authentification manquant.');
        }

        // 2. Valider le token
        $secret = $this->config['jwt']['secret'];
        if (!$secret) {
            $this->logger->error("La clé secrète JWT n'est pas configurée côté serveur.");
            throw new Exception('Erreur de configuration du serveur.');
        }

        try {
            // JWT::decode lèvera une exception si le token est invalide
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            // 3. Attacher les données utilisateur à l'objet Request
            $request->setAttribute('user', $decoded);

        } catch (Exception $e) {
            $this->logger->warning("Tentative d'accès avec un token invalide ou expiré.", ['error' => $e->getMessage()]);
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
