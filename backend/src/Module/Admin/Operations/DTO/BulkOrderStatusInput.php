<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\DTO;

use App\Module\Order\Entity\Order;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class BulkOrderStatusInput
{
    /** @param list<int> $orderIds */
    public function __construct(
        #[Assert\Count(min: 1, max: 100)]
        #[Assert\All([new Assert\Positive()])]
        public array $orderIds,
        #[Assert\Choice(choices: [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ])]
        public string $status,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $ids = is_array($payload['orderIds'] ?? null) ? $payload['orderIds'] : [];

        return new self(
            array_values(array_filter(
                array_map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $ids),
                static fn (int $id): bool => $id > 0,
            )),
            is_string($payload['status'] ?? null) ? trim($payload['status']) : '',
        );
    }
}
