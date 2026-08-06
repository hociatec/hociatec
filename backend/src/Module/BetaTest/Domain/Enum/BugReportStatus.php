<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Domain\Enum;

enum BugReportStatus: string
{
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case NEED_INFO = 'need_info';
    case PLANNED = 'planned';
    case RESOLVED = 'resolved';
    case DUPLICATE = 'duplicate';
    case REJECTED = 'rejected';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::SUBMITTED->value,
            self::UNDER_REVIEW->value,
            self::NEED_INFO->value,
            self::PLANNED->value,
            self::RESOLVED->value,
            self::DUPLICATE->value,
            self::REJECTED->value,
        ];
    }

    /** @return list<string> */
    public static function closedValues(): array
    {
        return [
            self::RESOLVED->value,
            self::DUPLICATE->value,
            self::REJECTED->value,
        ];
    }
}

