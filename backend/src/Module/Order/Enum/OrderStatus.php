<?php

declare(strict_types=1);

namespace App\Module\Order\Enum;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
