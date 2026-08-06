<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Security;

use App\Module\User\Domain\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SymfonySecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private User $user)
    {
    }

    public static function domainUser(mixed $user): ?User
    {
        if ($user instanceof User) {
            return $user;
        }

        return $user instanceof self ? $user->user : null;
    }

    public function domainIdentity(): User
    {
        return $this->user;
    }

    public function getUserIdentifier(): string
    {
        $identifier = $this->user->getUserIdentifier();
        if ('' === $identifier) {
            throw new \LogicException('A security user must have an identifier.');
        }

        return $identifier;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return $this->user->getRoles();
    }

    public function getPassword(): string
    {
        return $this->user->getPassword();
    }

    public function eraseCredentials(): void
    {
        $this->user->eraseCredentials();
    }
}
