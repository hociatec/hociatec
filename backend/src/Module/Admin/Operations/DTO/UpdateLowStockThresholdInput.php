<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateLowStockThresholdInput
{
    public function __construct(
        #[Assert\PositiveOrZero]
        public int $threshold,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_numeric($payload['threshold'] ?? null) ? (int) $payload['threshold'] : -1);
    }
}
