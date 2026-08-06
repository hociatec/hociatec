<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Enum;

enum InvoiceStatus: string
{
    case ISSUED = 'issued';
    case CANCELLED = 'cancelled';
}
