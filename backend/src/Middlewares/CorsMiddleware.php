<?php

namespace App\Middlewares;

use Psr\Log\LoggerInterface;

/**
 * Middleware CORS (Cross-Origin Resource Sharing).
 *
 * Sécurité :
 * - Vérifie l'origine contre une liste blanche configurée (pas de wildcard avec credentials)
 * - Ajoute Vary: Origin pour un caching correct par les CDN/proxies
 * - Cache les preflight via Access-Control-Max-Age
 * - Logique pure extraite dans process() pour la testabilité
 */
class CorsMiddleware
{
    private array $config;
    private LoggerInterface $logger;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config['cors'] ?? [];
        $this->logger = $logger;
    }

    /**
     * Point d'entrée : applique les en-têtes CORS et termine les preflight.
     */
    public function handle(): void
    {
        $result = $this->process(
            $_SERVER['HTTP_ORIGIN'] ?? '',
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );

        foreach ($result['headers'] as $header) {
            header($header);
        }

        if ($result['status_code'] !== null) {
            http_response_code($result['status_code']);
        }

        if ($result['terminate']) {
            exit;
        }
    }

    /**
     * Logique CORS pure — testable sans effets de bord.
     *
     * @param string $origin  Valeur de l'en-tête Origin de la requête
     * @param string $method  Méthode HTTP de la requête
     * @return array{headers: string[], status_code: int|null, terminate: bool}
     */
    public function process(string $origin, string $method): array
    {
        $headers = [];
        $statusCode = null;
        $terminate = false;

        $allowedOrigins  = $this->config['allowed_origins'] ?? [];
        $allowCredentials = $this->config['allow_credentials'] ?? false;

        // Sécurité : interdire wildcard '*' avec credentials (non conforme RFC 6454)
        if ($allowCredentials && in_array('*', $allowedOrigins, true)) {
            $this->logger->warning(
                'CORS: wildcard "*" interdit avec allow_credentials=true — requête bloquée.'
            );
            if ($method === 'OPTIONS') {
                return ['headers' => [], 'status_code' => 403, 'terminate' => true];
            }
            return ['headers' => [], 'status_code' => null, 'terminate' => false];
        }

        // Vérifie si l'origine est dans la liste blanche (matching strict)
        $isAllowed = !empty($origin) && in_array($origin, $allowedOrigins, true);

        if (!$isAllowed) {
            if (!empty($origin)) {
                $this->logger->debug("Origine CORS non autorisée bloquée : {$origin}");
            }
            if ($method === 'OPTIONS') {
                return ['headers' => [], 'status_code' => 403, 'terminate' => true];
            }
            return ['headers' => [], 'status_code' => null, 'terminate' => false];
        }

        // ── Origine autorisée — envoyer les en-têtes CORS ──

        $headers[] = 'Access-Control-Allow-Origin: ' . $origin;

        // Vary: Origin obligatoire quand la réponse dépend de l'origine (pas wildcard)
        $headers[] = 'Vary: Origin';

        // Méthodes autorisées
        $allowedMethods = implode(', ', $this->config['allowed_methods'] ?? []);
        if (!empty($allowedMethods)) {
            $headers[] = 'Access-Control-Allow-Methods: ' . $allowedMethods;
        }

        // En-têtes autorisés
        $allowedHeaders = implode(', ', $this->config['allowed_headers'] ?? []);
        if (!empty($allowedHeaders)) {
            $headers[] = 'Access-Control-Allow-Headers: ' . $allowedHeaders;
        }

        // Credentials (cookies, Authorization)
        if ($allowCredentials) {
            $headers[] = 'Access-Control-Allow-Credentials: true';
        }

        // Cache preflight (Access-Control-Max-Age)
        $maxAge = $this->config['max_age'] ?? 0;
        if ($maxAge > 0) {
            $headers[] = 'Access-Control-Max-Age: ' . (int)$maxAge;
        }

        // Requête preflight OPTIONS → 204 No Content + terminate
        if ($method === 'OPTIONS') {
            $statusCode = 204;
            $terminate = true;
        }

        return ['headers' => $headers, 'status_code' => $statusCode, 'terminate' => $terminate];
    }
}
