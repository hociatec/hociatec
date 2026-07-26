<?php

declare(strict_types=1);

namespace App\Module\Support\Enum;

enum SupportStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case WAITING_CUSTOMER = 'waiting_customer';
    case RESOLVED = 'resolved';
    case REFUSED = 'refused';
}
