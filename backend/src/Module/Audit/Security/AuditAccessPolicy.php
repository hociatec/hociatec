<?php

declare(strict_types=1);

namespace App\Module\Audit\Security;

use App\Module\Audit\Entity\AuditRequest;
use App\Module\User\Entity\User;

final readonly class AuditAccessPolicy
{
    public function canView(User $user, AuditRequest $audit): bool
    {
        return $audit->getClient()->getId() === $user->getId();
    }
}
