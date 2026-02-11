<?php

namespace App\Core;

/**
 * Classe Response pour encapsuler et construire une réponse HTTP.
 *
 * Cette classe permet de construire une réponse de manière orientée objet,
 * en définissant le statut, les en-têtes et le contenu, puis de l'envoyer.
 * Elle fournit également des méthodes statiques pour les réponses JSON courantes.
 */
final class Response
{
    // Codes de statut HTTP pour la lisibilité
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_CONFLICT = 409;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    private array $headers = [];
    private ?string $content;
    private int $statusCode;

    public function __construct(?string $content = null, int $statusCode = self::HTTP_OK, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Méthode statique pour envoyer une réponse JSON.
     * DÉPRÉCIÉ : L'appel à exit() est conservé pour la compatibilité avec l'ancien code.
     * L'approche moderne est de retourner un objet Response et de l'envoyer depuis l'index.
     *
     * @deprecated since 1.0.0 Use Response::setJsonContent() and Response::send() instead.
     */
    public static function json($data, int $status = 200): self
    {
        return (new self())
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Définit le contenu de la réponse.
     */
    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Définit le contenu en tant que JSON.
     */
    public function setJsonContent(mixed $data): self
    {
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $this;
    }

    /**
     * Définit le code de statut HTTP.
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Ajoute ou modifie un en-tête HTTP.
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Envoie la réponse HTTP au client.
     * Cette méthode doit être appelée à la toute fin du script.
     */
    public function send(): void
    {
        // 1. Envoyer le code de statut
        http_response_code($this->statusCode);

        // 2. Envoyer les en-têtes
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // 3. Envoyer le contenu
        if ($this->content !== null) {
            echo $this->content;
        }
    }
}
