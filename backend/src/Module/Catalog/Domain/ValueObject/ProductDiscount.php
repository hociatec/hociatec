<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\ValueObject;

final readonly class ProductDiscount
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED_CENTS = 'fixed_cents';

    public function __construct(
        public bool $enabled,
        public ?string $type,
        public ?int $value,
        public ?\DateTimeImmutable $startsAt,
        public ?\DateTimeImmutable $endsAt,
    ) {
        if (null !== $type && !in_array($type, [self::TYPE_PERCENT, self::TYPE_FIXED_CENTS], true)) {
            throw new \InvalidArgumentException('Type de remise invalide.');
        }

        if (null !== $value && $value < 0) {
            throw new \InvalidArgumentException('Valeur de remise invalide.');
        }
    }

    public function effectivePriceCents(int $basePriceCents, ?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();
        $basePriceCents = max(0, $basePriceCents);

        if (!$this->enabled || !$this->isActiveAt($now)) {
            return $basePriceCents;
        }

        if (self::TYPE_PERCENT === $this->type && null !== $this->value) {
            $percent = max(0, min(100, $this->value));
            $discount = (int) round($basePriceCents * ($percent / 100));

            return max(0, $basePriceCents - $discount);
        }

        if (self::TYPE_FIXED_CENTS === $this->type && null !== $this->value) {
            return max(0, $basePriceCents - $this->value);
        }

        return $basePriceCents;
    }

    private function isActiveAt(\DateTimeImmutable $now): bool
    {
        if (null !== $this->startsAt && $now < $this->startsAt) {
            return false;
        }

        return null === $this->endsAt || $now <= $this->endsAt;
    }
}
