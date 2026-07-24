<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\User\Entity\User;
use App\Module\User\Exception\InvalidProfilePasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ChangeProfilePasswordService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private ProfileCurrentPasswordVerifier $currentPasswordVerifier,
    ) {
    }

    public function change(User $user, ?string $newPassword, ?string $currentPassword): void
    {
        if (null === $newPassword) {
            return;
        }

        if ('' === trim($newPassword)) {
            throw InvalidProfilePasswordException::empty();
        }

        $this->currentPasswordVerifier->verify($user, $currentPassword);

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
    }
}
