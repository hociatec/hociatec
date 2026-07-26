<?php

declare(strict_types=1);

namespace App\Module\Admin\Order\DTO;

use App\Module\Order\Entity\Order;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrderStatusInput
{
    public function __construct(
        #[Assert\Choice(choices: [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])]
        public string $status,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['status'] ?? null) ? trim($payload['status']) : '');
    }
}
