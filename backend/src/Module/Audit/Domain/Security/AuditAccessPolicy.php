<?php

declare(strict_types=1);

namespace App\Module\Audit\Domain\Security;

use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\User\Domain\Entity\User;

final readonly class AuditAccessPolicy
{
    public function canView(User $user, AuditRequest $audit): bool
    {
        return $audit->getClient()->getId() === $user->getId();
    }
}
