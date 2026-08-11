<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Module\User\Domain\Entity\User;

trait AuthenticatedDomainUserTrait
{
    private function currentUser(): User
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
