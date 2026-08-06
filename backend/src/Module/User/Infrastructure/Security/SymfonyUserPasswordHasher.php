<?php

declare(strict_types=1);

namespace App\Module\User\Infrastructure\Security;

use App\Module\Auth\Infrastructure\Security\SymfonySecurityUser;
use App\Module\User\Application\Port\UserPasswordHasher;
use App\Module\User\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class SymfonyUserPasswordHasher implements UserPasswordHasher
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function hashPassword(User $user, string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword(new SymfonySecurityUser($user), $plainPassword);
    }

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid(new SymfonySecurityUser($user), $plainPassword);
    }
}
