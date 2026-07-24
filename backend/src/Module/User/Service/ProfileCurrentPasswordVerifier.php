<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\User\Entity\User;
use App\Module\User\Exception\InvalidCurrentPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ProfileCurrentPasswordVerifier
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function verify(User $user, ?string $currentPassword): void
    {
        if (null === $currentPassword || '' === trim($currentPassword)) {
            throw InvalidCurrentPasswordException::missing();
        }

        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw InvalidCurrentPasswordException::invalid();
        }
    }
}
