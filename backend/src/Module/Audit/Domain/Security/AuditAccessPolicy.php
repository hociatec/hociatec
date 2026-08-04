<?php

declare(strict_types=1);

namespace App\Module\Audit\Domain\Security;

use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\User\Domain\Entity\User;

final readonly class AuditAccessPolicy
{
    public function canView(User $user, AuditRequest $audit): bool
    {
        $userId = $user->getId();
        $clientId = $audit->getClient()->getId();

        if (null !== $userId && null !== $clientId) {
            return $clientId === $userId;
        }

        return $audit->getClient() === $user;
    }
}
