<?php

declare(strict_types=1);

namespace App\Module\User\Application\Exception;

class InvalidBirthDateException extends \DomainException
{
    public static function invalid(): self
    {
        return new self('La date de naissance est invalide.');
    }

    public static function inFuture(): self
    {
        return new self('La date de naissance ne peut pas etre dans le futur.');
    }
}
