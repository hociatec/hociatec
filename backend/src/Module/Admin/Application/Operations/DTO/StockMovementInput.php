<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class StockMovementInput
{
    public function __construct(
        #[Assert\Positive]
        public int $productId,
        public int $delta,
        #[Assert\Length(max: 100)]
        public string $reason = 'adjustment',
        #[Assert\Length(max: 1000)]
        public ?string $note = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_numeric($payload['productId'] ?? null) ? (int) $payload['productId'] : 0,
            is_numeric($payload['delta'] ?? null) ? (int) $payload['delta'] : 0,
            is_string($payload['reason'] ?? null) && '' !== trim($payload['reason']) ? trim($payload['reason']) : 'adjustment',
            is_string($payload['note'] ?? null) ? trim($payload['note']) : null,
        );
    }
}
