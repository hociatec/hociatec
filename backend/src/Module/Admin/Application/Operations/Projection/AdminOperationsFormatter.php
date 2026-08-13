<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Projection;

use App\Module\Admin\Application\Operations\DTO\FulfillmentOrderOutput;
use App\Module\Admin\Application\Operations\DTO\LowStockProductOutput;
use App\Module\Admin\Application\Operations\DTO\RefundOutput;
use App\Module\Admin\Application\Operations\DTO\StockMovementOutput;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Entity\StockMovement;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Support\Domain\Entity\SupportRequest;

final readonly class AdminOperationsFormatter
{
    public function __construct(
        private AdminOperationsEmailLogFormatter $emailLogs,
        private OrderFormatter $orderFormatter,
        private ?AdminSupportRequestFormatter $supportRequests = null,
    ) {
    }

    public function supportRequest(SupportRequest $support): \App\Module\Admin\Application\Operations\DTO\SupportRequestOutput
    {
        return $this->supportRequests()->supportRequest($support);
    }

    /** @return array<string, mixed> */
    public function customerSupportRequest(SupportRequest $support): array
    {
        return $this->supportRequests()->customerSupportRequest($support);
    }

    public function refund(RefundRequest $refund): RefundOutput
    {
        $order = $refund->getOrder();

        return new RefundOutput([
            'id' => $refund->getId(),
            'order' => ['id' => $order->getId(), 'number' => $order->getNumber()],
            'paymentId' => $refund->getPaymentId(),
            'amountCents' => $refund->getAmountCents(),
            'currencyCode' => $refund->getCurrencyCode(),
            'status' => $refund->getStatus(),
            'reason' => $refund->getReason(),
            'internalNotes' => $refund->getInternalNotes(),
            'stripeRefundId' => $refund->getStripeRefundId(),
            'createdAt' => $refund->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $refund->getUpdatedAt()->format(DATE_ATOM),
        ]);
    }

    public function stockMovement(StockMovement $movement): StockMovementOutput
    {
        $product = $movement->getProduct();

        return new StockMovementOutput([
            'id' => $movement->getId(),
            'product' => ['id' => $product->getId(), 'name' => $product->getName(), 'sku' => $product->getSku()],
            'delta' => $movement->getDelta(),
            'stockBefore' => $movement->getStockBefore(),
            'stockAfter' => $movement->getStockAfter(),
            'reason' => $movement->getReason(),
            'note' => $movement->getNote(),
            'actor' => $movement->getActor()?->getFullName(),
            'createdAt' => $movement->getCreatedAt()->format(DATE_ATOM),
        ]);
    }

    public function lowStockProduct(Product $product): LowStockProductOutput
    {
        return new LowStockProductOutput([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'stock' => $product->getStock(),
            'lowStockThreshold' => $product->getLowStockThreshold(),
            'category' => $product->getCategory()->getName(),
        ]);
    }

    public function fulfillmentOrder(Order $order): FulfillmentOrderOutput
    {
        $items = array_values(array_map(static fn (OrderItem $item): array => [
            'name' => $item->getProductName(),
            'sku' => $item->getProductSku(),
            'quantity' => $item->getQuantity(),
        ], $order->getItems()->toArray()));

        return new FulfillmentOrderOutput([
            'id' => $order->getId(),
            'number' => $order->getNumber(),
            'status' => $order->getStatus(),
            'statusLabel' => $this->orderFormatter->formatStatusLabel($order->getStatus()),
            'customer' => [
                'id' => $order->getUser()->getId(),
                'name' => $order->getUser()->getFullName(),
                'email' => $order->getUser()->getEmail(),
            ],
            'totalPriceCents' => $order->getTotalPriceCents(),
            'shipping' => [
                'name' => $order->getShippingName(),
                'address' => $order->getShippingAddress(),
                'postalCode' => $order->getShippingPostalCode(),
                'city' => $order->getShippingCity(),
            ],
            'delivery' => [
                'status' => $order->getDeliveryStatus(),
                'statusLabel' => $this->orderFormatter->formatDeliveryStatusLabel($order->getDeliveryStatus()),
                'carrier' => $order->getDeliveryCarrier(),
                'trackingNumber' => $order->getDeliveryTrackingNumber(),
                'trackingUrl' => $order->getDeliveryTrackingUrl(),
            ],
            'items' => $items,
            'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function emailLogs(): array
    {
        return $this->emailLogs->emailLogs();
    }

    public function failedCount(): int
    {
        return $this->emailLogs->failedCount();
    }

    public function emailScenarioLabel(string $scenario): string
    {
        return $this->emailLogs->emailScenarioLabel($scenario);
    }

    public function supportStatusLabel(string $status): string
    {
        return AdminSupportRequestFormatter::statusLabel($status);
    }

    private function supportRequests(): AdminSupportRequestFormatter
    {
        return $this->supportRequests
            ?? new AdminSupportRequestFormatter(
                new AdminSupportTimelineFormatter(new AdminSupportTimelineEntryFormatter()),
            );
    }
}
