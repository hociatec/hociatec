<?php

declare(strict_types=1);

namespace App\Module\Order\Enum;

enum DeliveryStatus: string
{
    case PREPARING = 'preparing';
    case SHIPPED = 'shipped';
    case IN_TRANSIT = 'in_transit';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case ISSUE = 'issue';
}
