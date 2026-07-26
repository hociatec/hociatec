<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Enum;

enum TradeInStatus: string
{
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case OFFER_SENT = 'offer_sent';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case RECEIVED = 'received';
    case INSPECTED = 'inspected';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
}
