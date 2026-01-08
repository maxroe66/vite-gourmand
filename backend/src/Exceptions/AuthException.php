<?php

namespace App\Exceptions;

class AuthException extends \Exception
{
    public const TOKEN_MISSING = 2001;
    public const TOKEN_INVALID = 2002;
    public const CONFIG_ERROR = 2003;

    public static function tokenMissing(): self
    {
        return new self("Token d'authentification manquant.", self::TOKEN_MISSING);
    }

    public static function tokenInvalid(): self
    {
        return new self("Token invalide ou expiré.", self::TOKEN_INVALID);
    }

    public static function configError(): self
    {
        return new self("Erreur de configuration du serveur.", self::CONFIG_ERROR);
    }
}
