<?php

declare(strict_types=1);

namespace App\Module\Promotion\DTO;

use App\Module\Promotion\Entity\Promotion;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PromotionInput
{
    /** @param array<string,mixed> $criteria */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 140)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Length(max: 140)]
        #[Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public string $slug,
        #[Assert\Choice(choices: [Promotion::TYPE_PERCENT, Promotion::TYPE_FIXED_CENTS])]
        public string $discountType,
        #[Assert\Positive]
        public int $discountValue,
        #[Assert\NotBlank]
        #[Assert\Length(max: 60)]
        public string $audienceKey,
        public array $criteria = [],
        #[Assert\Length(max: 100)]
        public ?string $description = null,
        public bool $isActive = true,
        public ?\DateTimeImmutable $startsAt = null,
        public ?\DateTimeImmutable $endsAt = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $criteria = $payload['criteria'] ?? [];

        return new self(
            trim(is_string($payload['name'] ?? null) ? $payload['name'] : ''),
            trim(is_string($payload['slug'] ?? null) ? $payload['slug'] : ''),
            trim(is_string($payload['discountType'] ?? null) ? $payload['discountType'] : ''),
            is_numeric($payload['discountValue'] ?? null) ? (int) $payload['discountValue'] : 0,
            trim(is_string($payload['audienceKey'] ?? null) ? $payload['audienceKey'] : ''),
            is_array($criteria) ? $criteria : [],
            isset($payload['description']) && is_string($payload['description']) ? trim($payload['description']) : null,
            (bool) ($payload['isActive'] ?? true),
            self::date($payload['startsAt'] ?? null),
            self::date($payload['endsAt'] ?? null),
        );
    }

    private static function date(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\DateMalformedStringException) {
            return null;
        }
    }
}
