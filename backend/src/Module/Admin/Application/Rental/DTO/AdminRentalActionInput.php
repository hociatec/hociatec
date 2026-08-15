<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Rental\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AdminRentalActionInput
{
    public function __construct(
        #[Assert\Choice(choices: ['approve_extension', 'approve_end_early', 'reject_request', 'mark_returned'])]
        public string $action,
    ) {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['action'] ?? null) ? trim((string) $payload['action']) : '',
        );
    }
}
