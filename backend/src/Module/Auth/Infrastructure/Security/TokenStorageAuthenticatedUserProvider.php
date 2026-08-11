<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Security;

use App\Module\User\Domain\Entity\User;
use App\Shared\Application\Security\AuthenticatedUserProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class TokenStorageAuthenticatedUserProvider implements AuthenticatedUserProvider
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function currentUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        return SymfonySecurityUser::domainUser($token->getUser());
    }
}
