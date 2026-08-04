<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Marketing\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MarketingAudienceInput
{
    /** @param array<string,mixed> $criteria */
    public function __construct(
        #[Assert\NotBlank] public string $segmentKey,
        public array $criteria,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['segmentKey'] ?? null) ? trim($payload['segmentKey']) : '',
            is_array($payload['criteria'] ?? null) ? $payload['criteria'] : [],
        );
    }
}
