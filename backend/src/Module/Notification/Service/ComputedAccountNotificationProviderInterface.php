<?php

declare(strict_types=1);

namespace App\Module\Notification\Service;

use App\Module\User\Entity\User;

interface ComputedAccountNotificationProviderInterface
{
    /**
     * @return list<array{key: string, label: string, message: string, to: string, type: string, createdAt: string}>
     */
    public function provide(User $user, \DateTimeImmutable $now): array;
}
