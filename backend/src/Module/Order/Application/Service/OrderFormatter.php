<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Domain\Workflow\OrderStatusWorkflow;
use App\Module\Rating\Application\Service\ProductReviewFormatter;
use App\Module\Rating\Domain\Entity\ProductRating;

final class OrderFormatter
{
    private function __construct()
    {
    }

    public static function formatStatusLabel(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING => 'En attente',
            Order::STATUS_CONFIRMED => 'Confirmée',
            Order::STATUS_DELIVERED => 'Livrée',
            Order::STATUS_CANCELLED => 'Annulée',
            default => $status,
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function statusOptions(): array
    {
        $options = [];
        foreach ((new OrderStatusWorkflow())->statuses() as $status) {
            $options[] = ['value' => $status, 'label' => self::formatStatusLabel($status)];
        }

        return $options;
    }

    public static function formatDeliveryStatusLabel(string $deliveryStatus): string
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

    public static function formatInvoiceStatusLabel(string $invoiceStatus): string
    {
        return match ($invoiceStatus) {
            Order::INVOICE_STATUS_ISSUED => 'Émise',
            Order::INVOICE_STATUS_CANCELLED => 'Annulée',
            default => $invoiceStatus,
        };
    }

    /**
     * @param array<int, ProductRating> $ratingsByOrderItemId
     * @param array<string, mixed>      $extra
     *
     * @return array<string, mixed>
     */
    public static function formatOrder(Order $order, array $ratingsByOrderItemId = [], array $extra = []): array
    {
        $items = [];
        $pendingReviews = 0;

        /** @var OrderItem $item */
        foreach ($order->getItems() as $item) {
            $line = $item->getLinePriceCents();
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
                'vatRateBps' => $item->getVatRateBps(),
                'lineSubtotalCents' => $item->getLineSubtotalCents(),
                'lineVatCents' => $item->getLineVatCents(),
                'linePriceCents' => $line,
                'canReview' => $canReview,
                'review' => $hasReview ? ProductReviewFormatter::formatRating($rating, true) : null,
            ];
        }

        $status = $order->getStatus();
        $statusLabel = self::formatStatusLabel($status);
        $allowedNextStatuses = (new OrderStatusWorkflow())->nextStatuses($status);

        $deliveryStatus = $order->getDeliveryStatus();
        $deliveryStatusLabel = self::formatDeliveryStatusLabel($deliveryStatus);
        $appliedPromotionName = $order->getAppliedPromotionName();
        $appliedPromotion = null !== $appliedPromotionName && !str_starts_with($appliedPromotionName, 'Conversion devis ')
            ? [
                'name' => $appliedPromotionName,
                'slug' => $order->getAppliedPromotionSlug(),
            ]
            : null;

        return [
            'id' => $order->getId(),
            'number' => $order->getNumber(),
            'userId' => $order->getUser()->getId(),
            'customerDisplayName' => trim($order->getUser()->getFirstName().' '.$order->getUser()->getLastName()),
            'status' => $status,
            'statusLabel' => $statusLabel,
            'allowedNextStatuses' => array_map(static fn (string $nextStatus): string => $nextStatus, $allowedNextStatuses),
            'allowedNextStatusDetails' => array_map(static fn (string $nextStatus): array => ['value' => $nextStatus, 'label' => self::formatStatusLabel($nextStatus)], $allowedNextStatuses),
            'subtotalPriceCents' => $order->getSubtotalPriceCents(),
            'discountAmountCents' => $order->getDiscountAmountCents(),
            'totalPriceCents' => $order->getTotalPriceCents(),
            'appliedPromotion' => $appliedPromotion,
            'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
            'pendingReviewsCount' => $pendingReviews,
            'hasPendingReviews' => $pendingReviews > 0,
            'shipping' => [
                'name' => $order->getShippingName(),
                'address' => $order->getShippingAddress(),
                'postalCode' => $order->getShippingPostalCode(),
                'city' => $order->getShippingCity(),
            ],
            'delivery' => [
                'status' => $deliveryStatus,
                'statusLabel' => $deliveryStatusLabel,
                'carrier' => $order->getDeliveryCarrier(),
                'trackingNumber' => $order->getDeliveryTrackingNumber(),
                'trackingUrl' => $order->getDeliveryTrackingUrl(),
                'estimatedAt' => $order->getDeliveryEstimatedAt()?->format(DATE_ATOM),
                'shippedAt' => $order->getDeliveryShippedAt()?->format(DATE_ATOM),
                'deliveredAt' => $order->getDeliveryDeliveredAt()?->format(DATE_ATOM),
            ],
            'invoice' => [
                'number' => $order->getInvoiceNumber(),
                'status' => $order->getInvoiceStatus(),
                'statusLabel' => self::formatInvoiceStatusLabel($order->getInvoiceStatus()),
                'issuedAt' => $order->getInvoicedAt()?->format(DATE_ATOM),
                'billingName' => $order->getBillingName(),
                'billingCompany' => $order->getBillingCompany(),
                'billingCompanySiren' => $order->getBillingCompanySiren(),
                'billingCompanyVatNumber' => $order->getBillingCompanyVatNumber(),
                'purchaseOrderNumber' => $order->getPurchaseOrderNumber(),
                'billingEmail' => $order->getBillingEmail(),
                'billingAddress' => $order->getBillingAddress(),
                'billingPostalCode' => $order->getBillingPostalCode(),
                'billingCity' => $order->getBillingCity(),
                'currencyCode' => $order->getCurrencyCode(),
                'electronicFormat' => $order->getElectronicFormat(),
            ],
            'items' => $items,
            ...$extra,
        ];
    }
}
