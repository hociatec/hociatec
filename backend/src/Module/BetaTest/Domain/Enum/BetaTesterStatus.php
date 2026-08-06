<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Domain\Enum;

enum BetaTesterStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case PAUSED = 'paused';
    case REJECTED = 'rejected';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::PENDING->value,
            self::ACCEPTED->value,
            self::PAUSED->value,
            self::REJECTED->value,
        ];
    }
}
