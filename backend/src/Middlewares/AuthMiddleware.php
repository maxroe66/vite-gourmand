<?php

namespace App\Middlewares;

use App\Core\Request;
use App\Exceptions\AuthException;
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
     * @param Request $request
     * @param array $args Arguments optionnels (non utilisés ici)
     * @throws AuthException si l'authentification échoue.
     */
    public function handle(Request $request, array $args = []): void
    {
        // 1. Récupérer le token (cookie ou header)
        $token = $this->getTokenFromRequest();
        if (!$token) {
            throw AuthException::tokenMissing();
        }

        // 2. Valider le token
        $secret = $this->config['jwt']['secret'];
        if (!$secret) {
            $this->logger->error("La clé secrète JWT n'est pas configurée côté serveur.");
            throw AuthException::configError();
        }

        try {
            // JWT::decode lèvera une exception si le token est invalide
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            // 3. Attacher les données utilisateur à l'objet Request
            $request->setAttribute('user', $decoded);

        } catch (Exception $e) {
            $this->logger->warning("Tentative d'accès avec un token invalide ou expiré.", ['error' => $e->getMessage()]);
            // On relance une exception typée pour que le routeur la capture.
            throw AuthException::tokenInvalid();
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

        $authHeader = null;

        // 1. Essayer via getallheaders (Apache mod_php)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $authHeader = $headers['Authorization'];
            }
        }

        // 2. Fallback via $_SERVER (PHP-FPM / Nginx)
        if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if ($authHeader) {
            // On s'attend à un format "Bearer <token>"
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
