<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Projection;

use App\Module\Admin\Application\Operations\DTO\FulfillmentOrderOutput;
use App\Module\Admin\Application\Operations\DTO\LowStockProductOutput;
use App\Module\Admin\Application\Operations\DTO\RefundOutput;
use App\Module\Admin\Application\Operations\DTO\SupportRequestOutput;
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
    ) {
    }

    public function supportRequest(SupportRequest $support): SupportRequestOutput
    {
        $customer = $support->getCustomer();
        $orderId = $support->getOrderId();
        $orderNumber = $support->getOrderNumber();

        return new SupportRequestOutput(
            [
                'id' => $support->getId(),
                'status' => $support->getStatus(),
                'statusLabel' => $this->supportStatusLabel($support->getStatus()),
                'reason' => $support->getReason(),
                'subject' => $support->getSubject(),
                'message' => $support->getMessage(),
                'internalNotes' => $support->getInternalNotes(),
                'customer' => ['id' => $customer->getId(), 'name' => $customer->getFullName(), 'email' => $customer->getEmail()],
                'order' => null !== $orderId || null !== $orderNumber ? ['id' => $orderId, 'number' => $orderNumber] : null,
                'createdAt' => $support->getCreatedAt()->format(DATE_ATOM),
                'updatedAt' => $support->getUpdatedAt()->format(DATE_ATOM),
                'resolvedAt' => $support->getResolvedAt()?->format(DATE_ATOM),
            ],
        );
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
            'items' => array_map(static fn (OrderItem $item): array => [
                'name' => $item->getProductName(),
                'sku' => $item->getProductSku(),
                'quantity' => $item->getQuantity(),
            ], $order->getItems()->toArray()),
            'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function emailLogs(): array
    {
        return $this->emailLogs->emailLogs();
    }

    public function supportStatusLabel(string $status): string
    {
        return match ($status) {
            SupportRequest::STATUS_NEW => 'Nouveau',
            SupportRequest::STATUS_IN_PROGRESS => 'En cours',
            SupportRequest::STATUS_WAITING_CUSTOMER => 'En attente client',
            SupportRequest::STATUS_RESOLVED => 'Résolu',
            SupportRequest::STATUS_REFUSED => 'Refusé',
            default => $status,
        };
    }

    public function emailScenarioLabel(string $scenario): string
    {
        return $this->emailLogs->emailScenarioLabel($scenario);
    }
}
