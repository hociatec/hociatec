<?php

declare(strict_types=1);

namespace App\Module\User\Exception;

class InvalidCurrentPasswordException extends \DomainException
{
    public static function missing(): self
    {
        return new self('Le mot de passe actuel est obligatoire pour cette modification.');
    }

    public static function invalid(): self
    {
        return new self('Le mot de passe actuel est incorrect.');
    }
}
