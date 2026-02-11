<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception levée quand un client dépasse la limite de requêtes (HTTP 429).
 */
class TooManyRequestsException extends Exception
{
    private int $retryAfter;

    public function __construct(int $retryAfter, string $message = 'Trop de requêtes. Veuillez réessayer plus tard.')
    {
        $this->retryAfter = $retryAfter;
        parent::__construct($message, 429);
    }

    /**
     * Nombre de secondes avant que le client puisse réessayer.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
