<?php

namespace App\Exceptions;

class UserServiceException extends \Exception
{
    public const EMAIL_EXISTS = 1001;
    public const DB_ERROR = 1002;

    public static function emailExists(): self
    {
        return new self("Cet email est déjà utilisé.", self::EMAIL_EXISTS);
    }

    public static function dbError($message = "Erreur technique lors de la création de l'utilisateur."): self
    {
        return new self($message, self::DB_ERROR);
    }
}
