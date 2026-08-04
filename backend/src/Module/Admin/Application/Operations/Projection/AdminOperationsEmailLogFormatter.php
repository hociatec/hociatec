<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Projection;

use App\Module\Order\Application\Port\OrderEventRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;

final readonly class AdminOperationsEmailLogFormatter
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private OrderEventRepositoryPort $orderEvents,
    ) {
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

    public function emailScenarioLabel(string $scenario): string
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
