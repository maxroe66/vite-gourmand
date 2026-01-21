<?php

namespace App\Exceptions;

use Exception;

class CommandeException extends Exception
{
    public static function notFound(int $id): self
    {
        return new self("La commande #$id est introuvable.", 404);
    }

    public static function menuNotFound(int $id): self
    {
        return new self("Le menu #$id est introuvable ou inactif.", 404);
    }

    public static function invalidQuantity(int $provided, int $min): self
    {
        return new self("Le nombre de personnes ($provided) est inférieur au minimum requis ($min).", 400);
    }

    public static function stockEmpty(): self
    {
        return new self("Ce menu n'est plus disponible en stock.", 409);
    }

    public static function unauthorizedModification(): self
    {
        return new self("Impossible de modifier une commande qui n'est plus 'EN_ATTENTE'.", 403);
    }

    public static function dateInvalid(): self
    {
        return new self("La date de prestation doit être au moins 24h après la commande.", 400);
    }
}
