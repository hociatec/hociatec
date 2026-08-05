<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Port;

use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\User\Domain\Entity\User;

interface OrderItemRepositoryPort
{
    /** @return list<OrderItem> */
    public function findPendingReviewItemsForUser(User $user): array;
}
