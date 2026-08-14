<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Port;

use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\User\Domain\Entity\User;

interface AccountNotificationEventRepositoryPort
{
    /** @return list<AccountNotificationEvent> */
    public function findRecentForUser(User $user, int $limit = 30, int $offset = 0): array;

    public function countForUser(User $user): int;

    public function existsForUserAndKey(User $user, string $key): bool;
}
