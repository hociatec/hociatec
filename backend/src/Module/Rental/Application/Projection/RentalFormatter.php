<?php

declare(strict_types=1);

namespace App\Module\Rental\Application\Projection;

use App\Module\Order\Domain\Entity\OrderItem;

final class RentalFormatter
{
    /**
     * @return array<string,mixed>
     */
    public function format(OrderItem $item, ?\DateTimeImmutable $today = null): array
    {
        $today ??= new \DateTimeImmutable('today');
        $startDate = $item->getRentalStartDate();
        $endDate = $item->getRentalEndDate();

        $timelineStatus = 'upcoming';
        if (null !== $endDate && $endDate < $today) {
            $timelineStatus = 'past';
        } elseif (null !== $startDate && $startDate <= $today) {
            $timelineStatus = 'active';
        }

        $order = $item->getOrder();
        $product = $item->getProduct();

        return [
            'orderItemId' => $item->getId(),
            'orderId' => $order?->getId(),
            'orderNumber' => $order?->getNumber(),
            'productId' => $product?->getId(),
            'productName' => $item->getProductName(),
            'productSku' => $item->getProductSku(),
            'quantity' => $item->getQuantity(),
            'unitPriceCents' => $item->getUnitPriceCents(),
            'linePriceCents' => $item->getLinePriceCents(),
            'rentalMonths' => $item->getRentalMonths(),
            'startDate' => $item->getRentalStartDateString(),
            'endDate' => $item->getRentalEndDateString(),
            'timelineStatus' => $timelineStatus,
            'timelineStatusLabel' => match ($timelineStatus) {
                'active' => 'En cours',
                'past' => 'Terminée',
                default => 'À venir',
            },
            'request' => [
                'status' => $item->getRentalRequestStatus(),
                'type' => $item->getRentalRequestType(),
                'requestedEndDate' => $item->getRentalRequestedEndDateString(),
                'createdAt' => $item->getRentalRequestCreatedAt()?->format(DATE_ATOM),
            ],
            'extension' => [
                'orderId' => $item->getRentalExtensionOrderId(),
                'sourceOrderItemId' => $item->getRentalOriginOrderItemId(),
            ],
            'returnPlan' => [
                'status' => $item->getRentalReturnStatus(),
                'mode' => $item->getRentalReturnMode(),
                'requestedDate' => $item->getRentalReturnRequestedDateString(),
                'requestedAt' => $item->getRentalReturnRequestedAt()?->format(DATE_ATOM),
                'completedAt' => $item->getRentalReturnCompletedAt()?->format(DATE_ATOM),
            ],
        ];
    }
}
