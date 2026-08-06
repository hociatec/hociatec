<?php

declare(strict_types=1);

namespace App\Module\User\Application\Mapper;

use App\Module\User\Application\Exception\InvalidCurrentPasswordException;
use App\Module\User\Application\Port\UserPasswordHasher;
use App\Module\User\Domain\Entity\User;

final readonly class ProfileCurrentPasswordVerifier
{
    public function __construct(
        private UserPasswordHasher $passwordHasher,
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
