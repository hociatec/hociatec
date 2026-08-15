<?php

declare(strict_types=1);

namespace App\Module\Service\Domain\Enum;

enum ServiceBillingMode: string
{
    case FIXED_PRICE = 'prix fixe';
    case HOUR = 'horaire';
    case DAY = 'jour';
    case INTERVENTION = 'intervention';
    case AUDIT = 'audit';
    case INSTALLATION = 'installation';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::FIXED_PRICE => 'Prix fixe',
            self::HOUR => 'Horaire',
            self::DAY => 'À la journée',
            self::INTERVENTION => 'Par intervention',
            self::AUDIT => 'Audit',
            self::INSTALLATION => 'Installation',
            self::MAINTENANCE => 'Maintenance',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $mode): array => ['value' => $mode->value, 'label' => $mode->label()],
            self::cases(),
        );
    }

    public static function normalize(mixed $value): ?self
    {
        if (!is_string($value) || '' === trim($value)) {
            return self::FIXED_PRICE;
        }

        $normalized = mb_strtolower(trim($value));

        if ('heure' === $normalized) {
            return self::HOUR;
        }

        return self::tryFrom($normalized);
    }
}
