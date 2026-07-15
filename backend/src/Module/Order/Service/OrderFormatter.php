<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderItem;
use App\Module\Rating\Entity\ProductRating;
use App\Module\Rating\Service\ProductReviewFormatter;

final class OrderFormatter
{
    private function __construct() {}

    /**
     * @return array<string, mixed>
     */
    /**
     * @param array<int, ProductRating> $ratingsByOrderItemId
     */
    public static function formatOrder(Order $order, array $ratingsByOrderItemId = []): array
    {
        $items = [];
        $pendingReviews = 0;

        /** @var OrderItem $item */
        foreach ($order->getItems() as $item) {
            $line = $item->getLinePriceCents();
            $product = $item->getProduct();
            $rating = $ratingsByOrderItemId[$item->getId()] ?? null;
            $hasReview = $rating instanceof ProductRating;
            $canReview = $product !== null && $order->getStatus() === Order::STATUS_DELIVERED && !$hasReview;

            if ($canReview) {
                $pendingReviews++;
            }

            $items[] = [
                'orderItemId' => $item->getId(),
                'productId' => $product?->getId(),
                'productName' => $item->getProductName(),
                'productSku' => $item->getProductSku(),
                'quantity' => $item->getQuantity(),
                'unitPriceCents' => $item->getUnitPriceCents(),
                'linePriceCents' => $line,
                'canReview' => $canReview,
                'review' => $hasReview ? ProductReviewFormatter::formatRating($rating, true) : null,
            ];
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
            'subtotalPriceCents' => $order->getSubtotalPriceCents(),
            'discountAmountCents' => $order->getDiscountAmountCents(),
            'totalPriceCents' => $order->getTotalPriceCents(),
            'appliedPromotion' => $order->getAppliedPromotionName() !== null ? [
                'name' => $order->getAppliedPromotionName(),
                'slug' => $order->getAppliedPromotionSlug(),
            ] : null,
            'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
            'pendingReviewsCount' => $pendingReviews,
            'hasPendingReviews' => $pendingReviews > 0,
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
