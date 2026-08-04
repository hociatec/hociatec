<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Enum;

enum RefundStatus: string
{
    case REQUESTED = 'requested';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
}
