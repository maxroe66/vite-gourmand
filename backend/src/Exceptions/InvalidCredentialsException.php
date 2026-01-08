<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception levée lors d'une erreur d'authentification (identifiants invalides).
 * 
 * Cette exception est utilisée lorsque les credentials fournis (email/password)
 * ne correspondent pas à un utilisateur valide en base de données.
 * 
 * @package App\Exceptions
 */
class InvalidCredentialsException extends Exception
{
    /**
     * Code d'erreur pour credentials invalides.
     */
    public const INVALID_CREDENTIALS = 3001;

    /**
     * Type d'erreur pour le frontend.
     */
    private string $type;

    /**
     * Constructeur privé pour forcer l'utilisation des factory methods.
     *
     * @param string $message Message d'erreur
     * @param int $code Code d'erreur
     * @param string $type Type d'erreur pour le frontend
     */
    private function __construct(string $message, int $code, string $type)
    {
        parent::__construct($message, $code);
        $this->type = $type;
    }

    /**
     * Factory method pour credentials invalides.
     *
     * @return self
     */
    public static function invalidCredentials(): self
    {
        return new self(
            'Email ou mot de passe incorrect.',
            self::INVALID_CREDENTIALS,
            'INVALID_CREDENTIALS'
        );
    }

    /**
     * Retourne le type d'erreur pour le frontend.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
