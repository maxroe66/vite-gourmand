<?php

namespace App\Exceptions;

use Exception;

class ForbiddenException extends Exception
{
    public function __construct(string $message = "Accès interdit.")
    {
        parent::__construct($message, 403);
    }
}
