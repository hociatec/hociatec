<?php

declare(strict_types=1);

namespace App\Module\User\Application\Workflow;

use App\Module\User\Application\Exception\InvalidProfilePasswordException;
use App\Module\User\Application\Mapper\ProfileCurrentPasswordVerifier;
use App\Module\User\Application\Port\UserPasswordHasher;
use App\Module\User\Domain\Entity\User;

final readonly class ChangeProfilePasswordService
{
    public function __construct(
        private UserPasswordHasher $passwordHasher,
        private ProfileCurrentPasswordVerifier $currentPasswordVerifier,
    ) {
    }

    public function change(User $user, ?string $newPassword, ?string $currentPassword): bool
    {
        if (null === $newPassword) {
            return false;
        }

        if ('' === trim($newPassword)) {
            throw InvalidProfilePasswordException::empty();
        }

        $this->currentPasswordVerifier->verify($user, $currentPassword);

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));

        return true;
    }
}
