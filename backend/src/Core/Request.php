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
    private ?int $jsonErrorCode = null;
    private ?string $jsonErrorMessage = null;
    private ?string $method = null;
    private ?array $uploadedFiles = null;

    /**
     * Définit un attribut personnalisé (utilisé par les middlewares)
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Définir la méthode HTTP (utile pour les tests)
     */
    public function setMethod(string $method): void
    {
        $this->method = strtoupper($method);
    }

    /**
     * Récupérer la méthode HTTP
     */
    public function getMethod(): string
    {
        if ($this->method !== null) {
            return $this->method;
        }

        return isset($_SERVER['REQUEST_METHOD'])
            ? strtoupper((string)$_SERVER['REQUEST_METHOD'])
            : 'GET';
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
            // Cas: body vide -> null sans erreur
            if (trim($rawBody) === '') {
                $this->jsonErrorCode = null;
                $this->jsonErrorMessage = null;
                $this->parsedBody = null;
                return null;
            }

            $this->parsedBody = json_decode($rawBody, true);
            $errorCode = json_last_error();

            // Cas: JSON invalide -> null mais on enregistre l'erreur
            if ($this->parsedBody === null && $errorCode !== JSON_ERROR_NONE) {
                $this->jsonErrorCode = $errorCode;
                $this->jsonErrorMessage = json_last_error_msg();
                return null;
            }

            // JSON valide (y compris booléen/null/liste/objet)
            $this->jsonErrorCode = null;
            $this->jsonErrorMessage = null;
        }
        return $this->parsedBody;
    }

    /**
     * Définir les fichiers uploadés (utile pour les tests)
     */
    public function setUploadedFiles(?array $files): void
    {
        $this->uploadedFiles = $files;
    }

    /**
     * Récupérer les fichiers uploadés
     */
    public function getUploadedFiles(): array
    {
        if ($this->uploadedFiles !== null) {
            return $this->uploadedFiles;
        }

        return $_FILES ?? [];
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
     * Indique si une erreur JSON a été rencontrée lors du parsing.
     */
    public function hasJsonError(): bool
    {
        return $this->jsonErrorCode !== null;
    }

    /**
     * Retourne les détails de l'erreur JSON (code + message) ou null s'il n'y en a pas.
     */
    public function getJsonError(): ?array
    {
        if ($this->jsonErrorCode === null) {
            return null;
        }

        return [
            'code' => $this->jsonErrorCode,
            'message' => $this->jsonErrorMessage,
        ];
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
