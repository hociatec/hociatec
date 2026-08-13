<?php

declare(strict_types=1);

namespace App\Module\Order\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CheckoutInput
{
    public function __construct(
        #[Assert\Positive]
        public ?int $addressId = null,
        #[Assert\Choice(choices: ['ios'])]
        public ?string $clientPlatform = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $clientPlatform = is_string($payload['clientPlatform'] ?? null)
            ? strtolower(trim((string) $payload['clientPlatform']))
            : null;

        return new self(
            is_numeric($payload['addressId'] ?? null) ? (int) $payload['addressId'] : null,
            '' !== (string) $clientPlatform ? $clientPlatform : null,
        );
    }
}
