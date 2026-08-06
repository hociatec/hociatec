<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Dashboard\Provider;

use App\Module\Order\Application\DTO\OrderCustomerSnapshot;
use App\Module\Order\Application\Port\OrderEventRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;

final readonly class DashboardOrderNotificationsProvider
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private OrderEventRepositoryPort $events,
        private OrderFormatter $orderFormatter,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function provide(): array
    {
        return [
            ...$this->pendingOrders(),
            ...$this->orderEvents(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function pendingOrders(): array
    {
        return array_map(function ($order): array {
            $customer = OrderCustomerSnapshot::fromOrder($order);

            return [
                'id' => 'order-pending-'.$order->getId(),
                'type' => 'order_pending_payment',
                'severity' => 'action',
                'title' => 'Commande en attente de règlement',
                'message' => sprintf('%s · %s', $order->getNumber(), $customer->email),
                'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
                'to' => '/admin/orders/'.$order->getId(),
                'resource' => [
                    'type' => 'order',
                    'id' => $order->getId(),
                    'number' => $order->getNumber(),
                ],
                'order' => $this->orderFormatter->formatOrder($order),
            ];
        }, $this->orders->findPendingPaymentForAdmin(8));
    }

    /** @return list<array<string, mixed>> */
    private function orderEvents(): array
    {
        $items = [];
        foreach ($this->events->findBy([], ['createdAt' => 'DESC'], 12) as $event) {
            if (!in_array($event->getType(), ['email_sent', 'email_resent', 'email_failed', 'payment_confirmed', 'order_created'], true)) {
                continue;
            }

            $items[] = [
                'id' => 'order-event-'.$event->getId(),
                'type' => $event->getType(),
                'severity' => 'email_failed' === $event->getType() ? 'danger' : 'info',
                'title' => $this->eventTitle($event->getType()),
                'message' => sprintf('%s · %s', $event->getOrder()->getNumber(), $event->getMessage() ?? $event->getType()),
                'createdAt' => $event->getCreatedAt()->format(DATE_ATOM),
                'to' => '/admin/orders/'.$event->getOrder()->getId(),
                'resource' => [
                    'type' => 'order',
                    'id' => $event->getOrder()->getId(),
                    'number' => $event->getOrder()->getNumber(),
                ],
            ];
        }

        return $items;
    }

    private function eventTitle(string $type): string
    {
        return match ($type) {
            'email_sent' => 'Email client envoyé',
            'email_resent' => 'Email client renvoyé',
            'email_failed' => 'Email client non envoyé',
            'payment_confirmed' => 'Paiement confirmé',
            'order_created' => 'Commande créée',
            default => $type,
        };
    }
}
