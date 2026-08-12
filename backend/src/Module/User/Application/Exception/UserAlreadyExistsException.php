<?php

declare(strict_types=1);

namespace App\Module\User\Application\Exception;

use App\Shared\Application\Exception\AbstractApiProblemException;

class UserAlreadyExistsException extends AbstractApiProblemException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('Un utilisateur existe deja avec l\'adresse e-mail "%s".', $email));
    }

    public function __construct(string $message = 'Cet email est deja utilise par un autre compte.')
    {
        parent::__construct($message, 409, $message, 'USER_ALREADY_EXISTS');
    }
}
