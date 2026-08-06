<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Domain\Enum;

enum BetaCampaignStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case CLOSED = 'closed';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::DRAFT->value,
            self::ACTIVE->value,
            self::CLOSED->value,
        ];
    }
}
