<?php

declare(strict_types=1);

namespace App\Module\User\Exception;

use RuntimeException;

class UserAlreadyExistsException extends RuntimeException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('A user already exists with email "%s".', $email));
    }
}
