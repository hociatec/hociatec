<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Projection;

use App\Module\Order\Domain\Entity\Order;

final class OrderStatusLabelFormatter
{
    public function status(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING => 'En attente',
            Order::STATUS_CONFIRMED => 'Confirmée',
            Order::STATUS_DELIVERED => 'Livrée',
            Order::STATUS_CANCELLED => 'Annulée',
            default => $status,
        };
    }

    public function delivery(string $deliveryStatus): string
    {
        return match ($deliveryStatus) {
            Order::DELIVERY_STATUS_PREPARING => 'Préparation en cours',
            Order::DELIVERY_STATUS_SHIPPED => 'Expédiée',
            Order::DELIVERY_STATUS_IN_TRANSIT => 'En transit',
            Order::DELIVERY_STATUS_OUT_FOR_DELIVERY => 'En cours de livraison',
            Order::DELIVERY_STATUS_DELIVERED => 'Livrée',
            Order::DELIVERY_STATUS_ISSUE => 'Incident de livraison',
            default => $deliveryStatus,
        };
    }

    public function invoice(string $invoiceStatus): string
    {
        return match ($invoiceStatus) {
            Order::INVOICE_STATUS_ISSUED => 'Émise',
            Order::INVOICE_STATUS_CANCELLED => 'Annulée',
            default => $invoiceStatus,
        };
    }
}
