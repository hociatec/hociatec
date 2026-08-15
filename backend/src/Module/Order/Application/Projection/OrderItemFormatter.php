<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Projection;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rating\Application\Projection\ProductReviewFormatter;
use App\Module\Rating\Domain\Entity\ProductRating;

final readonly class OrderItemFormatter
{
    public function __construct(private ProductReviewFormatter $productReviewFormatter)
    {
    }

    /**
     * @param array<int, ProductRating> $ratingsByOrderItemId
     *
     * @return array{items:list<array<string,mixed>>, pendingReviews:int}
     */
    public function formatItems(Order $order, array $ratingsByOrderItemId): array
    {
        $items = [];
        $pendingReviews = 0;

        /** @var OrderItem $item */
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            $rating = $ratingsByOrderItemId[$item->getId()] ?? null;
            $hasReview = $rating instanceof ProductRating;
            $canReview = null !== $product && Order::STATUS_DELIVERED === $order->getStatus() && !$hasReview;
            if ($canReview) {
                ++$pendingReviews;
            }

            $items[] = [
                'orderItemId' => $item->getId(),
                'productId' => $product?->getId(),
                'productName' => $item->getProductName(),
                'productSku' => $item->getProductSku(),
                'quantity' => $item->getQuantity(),
                'unitPriceCents' => $item->getUnitPriceCents(),
                'sellingType' => $item->getSellingType(),
                'rentalMonths' => $item->getRentalMonths(),
                'rentalStartDate' => $item->getRentalStartDateString(),
                'rentalEndDate' => $item->getRentalEndDateString(),
                'rentalRequest' => [
                    'status' => $item->getRentalRequestStatus(),
                    'type' => $item->getRentalRequestType(),
                    'requestedEndDate' => $item->getRentalRequestedEndDateString(),
                    'createdAt' => $item->getRentalRequestCreatedAt()?->format(DATE_ATOM),
                ],
                'rentalExtension' => [
                    'orderId' => $item->getRentalExtensionOrderId(),
                    'sourceOrderItemId' => $item->getRentalOriginOrderItemId(),
                ],
                'rentalReturn' => [
                    'status' => $item->getRentalReturnStatus(),
                    'mode' => $item->getRentalReturnMode(),
                    'requestedDate' => $item->getRentalReturnRequestedDateString(),
                    'requestedAt' => $item->getRentalReturnRequestedAt()?->format(DATE_ATOM),
                    'completedAt' => $item->getRentalReturnCompletedAt()?->format(DATE_ATOM),
                ],
                'vatRateBps' => $item->getVatRateBps(),
                'lineSubtotalCents' => $item->getLineSubtotalCents(),
                'lineVatCents' => $item->getLineVatCents(),
                'linePriceCents' => $item->getLinePriceCents(),
                'canReview' => $canReview,
                'review' => $hasReview ? $this->productReviewFormatter->formatRating($rating, true) : null,
            ];
        }

        return ['items' => $items, 'pendingReviews' => $pendingReviews];
    }
}
