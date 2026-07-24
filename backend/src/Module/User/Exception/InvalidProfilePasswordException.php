<?php

declare(strict_types=1);

namespace App\Module\User\Exception;

class InvalidProfilePasswordException extends \DomainException
{
    public static function empty(): self
    {
        return new self('Le nouveau mot de passe ne peut pas etre vide.');
    }
}
