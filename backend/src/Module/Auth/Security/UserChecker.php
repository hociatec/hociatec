<?php

declare(strict_types=1);

namespace App\Module\Auth\Security;

use App\Module\User\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('Votre compte n\'est pas encore activé. Veuillez vérifier vos emails.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // no-op
    }
}
