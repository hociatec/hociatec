<?php

declare(strict_types=1);

namespace App\Module\Rental\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateRentalRequestInput
{
    public function __construct(
        #[Assert\Choice(choices: ['extend', 'end_early'])] public string $action,
        public ?string $requestedEndDate = null,
        #[Assert\Choice(choices: ['ios', 'web', null])] public ?string $clientPlatform = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['action'] ?? null) ? trim((string) $payload['action']) : '',
            is_string($payload['requestedEndDate'] ?? null) ? trim((string) $payload['requestedEndDate']) : null,
            is_string($payload['clientPlatform'] ?? null) ? trim((string) $payload['clientPlatform']) : null,
        );
    }
}
