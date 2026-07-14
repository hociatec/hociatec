<?php

declare(strict_types=1);

namespace App\Module\User\Exception;

use RuntimeException;

class UserAlreadyExistsException extends RuntimeException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('Un utilisateur existe deja avec l\'adresse e-mail "%s".', $email));
    }
}
