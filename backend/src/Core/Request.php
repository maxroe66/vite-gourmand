<?php

namespace App\Core;

/**
 * Classe Request pour encapsuler les données HTTP
 * Gère les attributs, le parsing JSON et les données de requête
 */
class Request
{
    private array $attributes = [];
    private ?array $parsedBody = null;
    private ?string $rawBody = null;
    private ?array $queryParams = null;

    /**
     * Définit un attribut personnalisé (utilisé par les middlewares)
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Récupère un attribut personnalisé
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Récupère le corps brut de la requête
     * @return string
     */
    public function getRawBody(): string
    {
        if ($this->rawBody === null) {
            $this->rawBody = file_get_contents('php://input');
        }
        return $this->rawBody;
    }

    /**
     * Définit le corps brut (utile pour les tests)
     * @param string $body
     */
    public function setRawBody(string $body): void
    {
        $this->rawBody = $body;
        $this->parsedBody = null; // Reset le corps parsé
    }

    /**
     * Récupère le corps de la requête parsé en JSON
     * @return array|null Tableau associatif ou null si le parsing échoue
     */
    public function getJsonBody(): ?array
    {
        if ($this->parsedBody === null) {
            $rawBody = $this->getRawBody();
            $this->parsedBody = json_decode($rawBody, true);
            
            // Si json_decode retourne null et qu'il y a une erreur JSON, on garde null
            // Si le body est vide, on retourne null aussi
            if ($this->parsedBody === null && json_last_error() === JSON_ERROR_NONE && empty($rawBody)) {
                $this->parsedBody = null;
            }
        }
        return $this->parsedBody;
    }

    /**
     * Définit le corps parsé directement (utile pour les tests)
     * @param array|null $data
     */
    public function setParsedBody(?array $data): void
    {
        $this->parsedBody = $data;
    }

    /**
     * Récupère une valeur spécifique du corps JSON
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getJsonParam(string $key, $default = null)
    {
        $body = $this->getJsonBody();
        return $body[$key] ?? $default;
    }

    /**
     * Crée une instance de Request à partir du contexte global
     * @return self
     */
    public static function createFromGlobals(): self
    {
        return new self();
    }

    /**
     * Crée une instance de Request pour les tests avec des données JSON
     * @param array|null $jsonData
     * @return self
     */
    public static function createFromJson(?array $jsonData): self
    {
        $request = new self();
        if ($jsonData !== null) {
            $request->setParsedBody($jsonData);
        }
        return $request;
    }

    /**
     * Récupère tous les paramètres de requête (query string)
     */
    public function getQueryParams(): array
    {
        if ($this->queryParams === null) {
            $this->queryParams = $_GET ?? [];
        }
        return $this->queryParams;
    }

    /**
     * Récupère un paramètre de requête individuel
     */
    public function getQueryParam(string $key, $default = null)
    {
        $params = $this->getQueryParams();
        return $params[$key] ?? $default;
    }
}
