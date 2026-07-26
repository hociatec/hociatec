<?php

declare(strict_types=1);

namespace App\Module\Cart\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ApplyCartVoucherInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)] public string $voucherCode,
        #[Assert\Length(max: 64)] public ?string $cartToken = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['voucherCode'] ?? null) ? trim($payload['voucherCode']) : '',
            is_string($payload['cartToken'] ?? null) ? trim($payload['cartToken']) : null,
        );
    }
}
