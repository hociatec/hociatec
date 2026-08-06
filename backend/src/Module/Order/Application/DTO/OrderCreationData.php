<?php

declare(strict_types=1);

namespace App\Module\Order\Application\DTO;

use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;

final readonly class OrderCreationData
{
    public function __construct(
        public User $user,
        public ShippingAddress $address,
        public CartOrderSummary $summary,
        public \DateTimeImmutable $invoicedAt,
    ) {
    }
}
