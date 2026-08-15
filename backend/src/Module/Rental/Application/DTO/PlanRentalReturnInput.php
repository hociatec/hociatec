<?php

declare(strict_types=1);

namespace App\Module\Rental\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PlanRentalReturnInput
{
    public function __construct(
        #[Assert\Choice(choices: ['pickup_home', 'dropoff_store'])] public string $mode,
        public ?string $requestedDate = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['mode'] ?? null) ? trim((string) $payload['mode']) : '',
            is_string($payload['requestedDate'] ?? null) ? trim((string) $payload['requestedDate']) : null,
        );
    }
}
