<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Projection;

use App\Module\Order\Application\DTO\OrderCustomerSnapshot;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Workflow\OrderStatusWorkflow;
use App\Module\Rating\Domain\Entity\ProductRating;

final class OrderFormatter
{
    public function __construct(
        private readonly OrderStatusLabelFormatter $labels,
        private readonly OrderItemFormatter $items,
        private readonly OrderStatusWorkflow $statusWorkflow,
    ) {
    }

    public function formatStatusLabel(string $status): string
    {
        return $this->labels->status($status);
    }

    /** @return list<array{value: string, label: string}> */
    public function statusOptions(): array
    {
        $options = [];
        foreach ($this->statusWorkflow->statuses() as $status) {
            $options[] = ['value' => $status, 'label' => $this->formatStatusLabel($status)];
        }

        return $options;
    }

    public function formatDeliveryStatusLabel(string $deliveryStatus): string
    {
        return $this->labels->delivery($deliveryStatus);
    }

    public function formatInvoiceStatusLabel(string $invoiceStatus): string
    {
        return $this->labels->invoice($invoiceStatus);
    }

    /**
     * @param array<int, ProductRating> $ratingsByOrderItemId
     * @param array<string, mixed>      $extra
     *
     * @return array<string, mixed>
     */
    public function formatOrder(Order $order, array $ratingsByOrderItemId = [], array $extra = []): array
    {
        $formattedItems = $this->items->formatItems($order, $ratingsByOrderItemId);
        $items = $formattedItems['items'];
        $pendingReviews = $formattedItems['pendingReviews'];

        $status = $order->getStatus();
        $statusLabel = $this->formatStatusLabel($status);
        $allowedNextStatuses = $this->statusWorkflow->nextStatuses($status);

        $deliveryStatus = $order->getDeliveryStatus();
        $deliveryStatusLabel = $this->formatDeliveryStatusLabel($deliveryStatus);
        $customer = OrderCustomerSnapshot::fromOrder($order);
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
            'userId' => $customer->id,
            'customerDisplayName' => $customer->displayName(),
            'status' => $status,
            'statusLabel' => $statusLabel,
            'allowedNextStatuses' => array_map(static fn (string $nextStatus): string => $nextStatus, $allowedNextStatuses),
            'allowedNextStatusDetails' => array_map(fn (string $nextStatus): array => ['value' => $nextStatus, 'label' => $this->formatStatusLabel($nextStatus)], $allowedNextStatuses),
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
                'statusLabel' => $this->formatInvoiceStatusLabel($order->getInvoiceStatus()),
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
