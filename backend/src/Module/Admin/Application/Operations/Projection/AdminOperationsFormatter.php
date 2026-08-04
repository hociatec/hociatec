<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Projection;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Entity\StockMovement;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Support\Domain\Entity\SupportRequest;

final readonly class AdminOperationsFormatter
{
    public function __construct(private AdminOperationsEmailLogFormatter $emailLogs)
    {
    }

    /** @return array<string, mixed> */
    public function supportRequest(SupportRequest $support): array
    {
        $customer = $support->getCustomer();
        $order = $support->getOrder();

        return [
            'id' => $support->getId(),
            'status' => $support->getStatus(),
            'statusLabel' => $this->supportStatusLabel($support->getStatus()),
            'reason' => $support->getReason(),
            'subject' => $support->getSubject(),
            'message' => $support->getMessage(),
            'internalNotes' => $support->getInternalNotes(),
            'customer' => ['id' => $customer->getId(), 'name' => $customer->getFullName(), 'email' => $customer->getEmail()],
            'order' => $order instanceof Order ? ['id' => $order->getId(), 'number' => $order->getNumber()] : null,
            'createdAt' => $support->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $support->getUpdatedAt()->format(DATE_ATOM),
            'resolvedAt' => $support->getResolvedAt()?->format(DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function refund(RefundRequest $refund): array
    {
        $order = $refund->getOrder();

        return [
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
        ];
    }

    /** @return array<string, mixed> */
    public function stockMovement(StockMovement $movement): array
    {
        $product = $movement->getProduct();

        return [
            'id' => $movement->getId(),
            'product' => ['id' => $product->getId(), 'name' => $product->getName(), 'sku' => $product->getSku()],
            'delta' => $movement->getDelta(),
            'stockBefore' => $movement->getStockBefore(),
            'stockAfter' => $movement->getStockAfter(),
            'reason' => $movement->getReason(),
            'note' => $movement->getNote(),
            'actor' => $movement->getActor()?->getFullName(),
            'createdAt' => $movement->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function lowStockProduct(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'stock' => $product->getStock(),
            'lowStockThreshold' => $product->getLowStockThreshold(),
            'category' => $product->getCategory()->getName(),
        ];
    }

    /** @return array<string, mixed> */
    public function fulfillmentOrder(Order $order): array
    {
        return [
            'id' => $order->getId(),
            'number' => $order->getNumber(),
            'status' => $order->getStatus(),
            'statusLabel' => OrderFormatter::formatStatusLabel($order->getStatus()),
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
                'statusLabel' => OrderFormatter::formatDeliveryStatusLabel($order->getDeliveryStatus()),
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
        ];
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
