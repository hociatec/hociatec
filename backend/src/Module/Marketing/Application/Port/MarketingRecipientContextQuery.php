<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Port;

use App\Module\User\Domain\Entity\User;

interface MarketingRecipientContextQuery
{
    /** @return array{ordersCount:int|string, totalSpentCents:int|string, lastOrderAt?:?\DateTimeInterface} */
    public function orderStats(User $user): array;

    public function lastOrder(User $user): ?object;

    public function pendingReviewsCount(User $user): int;
}
