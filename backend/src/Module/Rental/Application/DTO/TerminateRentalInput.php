<?php

declare(strict_types=1);

namespace App\Module\Rental\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TerminateRentalInput
{
    public function __construct(
        public ?string $requestedEndDate = null,
        #[Assert\Choice(choices: ['pickup_home', 'dropoff_store'])] public string $returnMode = '',
        public ?string $returnRequestedDate = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['requestedEndDate'] ?? null) ? trim((string) $payload['requestedEndDate']) : null,
            is_string($payload['returnMode'] ?? null) ? trim((string) $payload['returnMode']) : '',
            is_string($payload['returnRequestedDate'] ?? null) ? trim((string) $payload['returnRequestedDate']) : null,
        );
    }
}
