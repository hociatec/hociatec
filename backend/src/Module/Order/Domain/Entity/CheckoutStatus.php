<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

enum CheckoutStatus: string
{
    case Open = 'open';
    case Paid = 'paid';
    case Expired = 'expired';
    case Failed = 'failed';
}
