<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Module\User\Application\Exception\InvalidCurrentPasswordException;
use App\Module\User\Domain\Entity\User;
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
