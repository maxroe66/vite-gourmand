<?php

namespace App\Middlewares;

use Psr\Log\LoggerInterface;

/**
 * Middleware pour gérer les en-têtes CORS (Cross-Origin Resource Sharing).
 * Ce middleware ajoute les en-têtes nécessaires pour autoriser les requêtes
 * cross-origin depuis les domaines spécifiés dans la configuration.
 */
class CorsMiddleware
{
    private array $config;
    private LoggerInterface $logger;

    public function __construct(array $config, LoggerInterface $logger)
    {
        // On récupère uniquement la section 'cors' de la configuration
        $this->config = $config['cors'] ?? [];
        $this->logger = $logger;
    }

    /**
     * Exécute la logique du middleware.
     * Gère les requêtes pre-flight (OPTIONS) et ajoute les en-têtes CORS.
     */
    public function handle(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = $this->config['allowed_origins'] ?? [];

        // Vérifie si l'origine de la requête est autorisée
        if (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            // Si l'origine n'est pas autorisée, on n'ajoute aucun header CORS.
            // Le navigateur bloquera la requête.
            // On logue cette tentative pour le débogage.
            if (!empty($origin)) {
                $this->logger->debug("Origine CORS non autorisée bloquée : {$origin}");
            }
            // Pour les requêtes OPTIONS, il faut quand même répondre, mais sans les headers.
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
                http_response_code(204); // No Content
                exit;
            }
            return;
        }

        // Gestion des en-têtes autorisés
        $allowedMethods = implode(', ', $this->config['allowed_methods'] ?? []);
        header('Access-Control-Allow-Methods: ' . $allowedMethods);

        $allowedHeaders = implode(', ', $this->config['allowed_headers'] ?? []);
        header('Access-Control-Allow-Headers: ' . $allowedHeaders);

        // Gestion des credentials (cookies, etc.)
        if ($this->config['allow_credentials'] ?? false) {
            header('Access-Control-Allow-Credentials: true');
        }

        // Gestion de la requête pre-flight OPTIONS
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204); // No Content
            exit;
        }
    }
}
