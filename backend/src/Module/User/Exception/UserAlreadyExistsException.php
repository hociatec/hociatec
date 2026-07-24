<?php

declare(strict_types=1);

namespace App\Module\User\Exception;

use App\Shared\Http\ApiProblemException;

class UserAlreadyExistsException extends \RuntimeException implements ApiProblemException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('Un utilisateur existe deja avec l\'adresse e-mail "%s".', $email));
    }

    public function getStatusCode(): int
    {
        return 409;
    }
}
