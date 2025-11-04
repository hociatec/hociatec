<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderItem;

final class OrderFormatter
{
    private function __construct() {}

    /**
     * @return array<string, mixed>
     */
    public static function formatOrder(Order $order): array
    {
        $items = [];
        $total = 0;

        /** @var OrderItem $item */
        foreach ($order->getItems() as $item) {
            $line = $item->getLinePriceCents();
            $items[] = [
                'productName' => $item->getProductName(),
                'productSku' => $item->getProductSku(),
                'quantity' => $item->getQuantity(),
                'unitPriceCents' => $item->getUnitPriceCents(),
                'linePriceCents' => $line,
            ];
            $total += $line;
        }

        $status = $order->getStatus();
        $statusLabel = match ($status) {
            Order::STATUS_PENDING => 'en attente',
            Order::STATUS_CONFIRMED => 'confirmée',
            Order::STATUS_DELIVERED => 'livrée',
            Order::STATUS_CANCELLED => 'annulée',
            default => $status,
        };

        return [
            'id' => $order->getId(),
            'number' => $order->getNumber(),
            'status' => $status,
            'statusLabel' => $statusLabel,
            'totalPriceCents' => $total,
            'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
            'shipping' => [
                'name' => $order->getShippingName(),
                'address' => $order->getShippingAddress(),
                'postalCode' => $order->getShippingPostalCode(),
                'city' => $order->getShippingCity(),
            ],
            'items' => $items,
        ];
    }
}
