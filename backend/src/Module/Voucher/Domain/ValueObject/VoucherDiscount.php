<?php

declare(strict_types=1);

namespace App\Module\Voucher\Domain\ValueObject;

final readonly class VoucherDiscount
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED_CENTS = 'fixed_cents';
    private const MAX_PERCENT_DISCOUNT = 100;

    public function __construct(
        public string $type,
        public int $value,
    ) {
        $this->assertValidType($type);
        $this->assertValidValue($type, $value);
    }

    /** @return list<string> */
    public static function types(): array
    {
        return [self::TYPE_PERCENT, self::TYPE_FIXED_CENTS];
    }

    private function assertValidType(string $type): void
    {
        if (!in_array($type, self::types(), true)) {
            throw new \InvalidArgumentException('Type de remise invalide.');
        }
    }

    private function assertValidValue(string $type, int $value): void
    {
        if (0 === $value) {
            return;
        }

        if ($value <= 0) {
            throw new \InvalidArgumentException('La valeur de remise doit être supérieure à zéro.');
        }

        if (self::TYPE_PERCENT === $type && $value > self::MAX_PERCENT_DISCOUNT) {
            throw new \InvalidArgumentException('La remise en pourcentage ne peut pas dépasser 100 %.');
        }
    }
}
