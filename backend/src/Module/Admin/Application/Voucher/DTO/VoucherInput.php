<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Voucher\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class VoucherInput
{
    public function __construct(
        #[Assert\NotBlank] public string $name,
        #[Assert\NotBlank] #[Assert\Length(max: 64)] public string $code,
        #[Assert\Length(max: 2000)] public ?string $description,
        #[Assert\NotBlank] public string $discountType,
        #[Assert\Positive] public int $discountValue,
        public bool $isActive,
        public ?string $startsAt,
        public ?string $endsAt,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['name'] ?? null) ? trim($payload['name']) : '', is_string($payload['code'] ?? null) ? mb_strtoupper(trim($payload['code'])) : '', is_string($payload['description'] ?? null) ? trim($payload['description']) : null, is_string($payload['discountType'] ?? null) ? trim($payload['discountType']) : '', is_numeric($payload['discountValue'] ?? null) ? (int) $payload['discountValue'] : 0, is_bool($payload['isActive'] ?? null) ? $payload['isActive'] : true, is_string($payload['startsAt'] ?? null) ? trim($payload['startsAt']) : null, is_string($payload['endsAt'] ?? null) ? trim($payload['endsAt']) : null);
    }
}
