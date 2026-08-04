<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Projection;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Entity\StockMovement;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Infrastructure\Repository\OrderEventRepository;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Support\Domain\Entity\SupportRequest;

final readonly class AdminOperationsFormatter
{
    public function __construct(
        private OrderRepository $orders,
        private OrderEventRepository $orderEvents,
    ) {
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
        $items = [];
        foreach ($this->orders->findBy([], ['createdAt' => 'DESC'], 80) as $order) {
            foreach ([
                'order_created' => $order->getOrderCreatedEmailSentAt(),
                'invoice' => $order->getInvoiceEmailSentAt(),
                'status_confirmed' => $order->getStatusConfirmedEmailSentAt(),
                'status_delivered' => $order->getStatusDeliveredEmailSentAt(),
                'status_cancelled' => $order->getStatusCancelledEmailSentAt(),
            ] as $scenario => $sentAt) {
                if (null === $sentAt) {
                    continue;
                }

                $items[] = [
                    'type' => 'transactional',
                    'scenario' => $scenario,
                    'scenarioLabel' => $this->emailScenarioLabel($scenario),
                    'status' => 'sent',
                    'statusLabel' => 'Envoyé',
                    'recipient' => $order->getBillingEmail() ?? $order->getUser()->getEmail(),
                    'subject' => 'Commande '.$order->getNumber(),
                    'related' => ['type' => 'order', 'id' => $order->getId(), 'label' => $order->getNumber()],
                    'createdAt' => $sentAt->format(DATE_ATOM),
                ];
            }
        }

        foreach ($this->orderEvents->findBy(['type' => 'email_failed'], ['createdAt' => 'DESC'], 80) as $event) {
            $items[] = [
                'type' => 'transactional',
                'scenario' => 'email_failed',
                'scenarioLabel' => 'Email non envoyé',
                'status' => 'failed',
                'statusLabel' => 'Échec',
                'recipient' => null,
                'subject' => $event->getMessage(),
                'related' => ['type' => 'order', 'id' => $event->getOrder()->getId(), 'label' => $event->getOrder()->getNumber()],
                'createdAt' => $event->getCreatedAt()->format(DATE_ATOM),
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) $b['createdAt'], (string) $a['createdAt']));

        return $items;
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

    private function emailScenarioLabel(string $scenario): string
    {
        return match ($scenario) {
            'order_created' => 'Confirmation de commande',
            'invoice' => 'Facture envoyée',
            'status_confirmed' => 'Commande confirmée',
            'status_delivered', 'order_status_delivered' => 'Commande livrée',
            'status_cancelled', 'order_status_cancelled' => 'Commande annulée',
            'customer_voucher_offer' => 'Bon de réduction client',
            'email_failed' => 'Email non envoyé',
            default => ucfirst(str_replace('_', ' ', $scenario)),
        };
    }
}
