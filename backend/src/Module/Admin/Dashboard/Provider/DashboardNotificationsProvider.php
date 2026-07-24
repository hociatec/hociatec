<?php

declare(strict_types=1);

namespace App\Module\Admin\Dashboard\Provider;

use App\Module\Order\Repository\OrderEventRepository;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderFormatter;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteFormatter;

final readonly class DashboardNotificationsProvider
{
    public function __construct(
        private QuoteRepository $quotes,
        private QuoteCalculator $quoteCalculator,
        private OrderRepository $orders,
        private OrderEventRepository $events,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function provide(): array
    {
        $items = [
            ...$this->acceptedQuotes(),
            ...$this->refusedQuotes(),
            ...$this->emailedQuotes(),
            ...$this->pendingOrders(),
            ...$this->orderEvents(),
        ];

        usort($items, static fn (array $left, array $right): int => strcmp(
            (string) $right['createdAt'],
            (string) $left['createdAt'],
        ));

        return array_slice($items, 0, 12);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function acceptedQuotes(): array
    {
        return array_map(fn (Quote $quote): array => [
            ...$this->quoteNotification($quote, 'quote_accepted', 'action', 'Devis accepté à convertir'),
            'quote' => QuoteFormatter::formatQuote($quote, $this->quoteCalculator),
        ], $this->quotes->findAcceptedWaitingForConversion(8));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function refusedQuotes(): array
    {
        return array_map(
            fn (Quote $quote): array => $this->quoteNotification($quote, 'quote_refused', 'info', 'Devis refusé'),
            $this->quotes->findRecentByStatuses([Quote::STATUS_REFUSED], 4),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function emailedQuotes(): array
    {
        return array_map(fn (Quote $quote): array => [
            ...$this->quoteNotification($quote, 'quote_email_sent', 'info', 'Devis envoyé au client'),
            'createdAt' => ($quote->getCreatedEmailSentAt() ?? $quote->getUpdatedAt())->format(DATE_ATOM),
        ], $this->quotes->findRecentlyEmailed(4));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pendingOrders(): array
    {
        return array_map(static fn ($order): array => [
            'id' => 'order-pending-'.$order->getId(),
            'type' => 'order_pending_payment',
            'severity' => 'action',
            'title' => 'Commande en attente de règlement',
            'message' => sprintf('%s · %s', $order->getNumber(), $order->getUser()->getEmail()),
            'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
            'to' => '/admin/orders/'.$order->getId(),
            'resource' => [
                'type' => 'order',
                'id' => $order->getId(),
                'number' => $order->getNumber(),
            ],
            'order' => OrderFormatter::formatOrder($order),
        ], $this->orders->findPendingPaymentForAdmin(8));
    }

    /**
     * @return list<array<string, mixed>>
     */
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

    /**
     * @return array<string, mixed>
     */
    private function quoteNotification(Quote $quote, string $type, string $severity, string $title): array
    {
        $idPrefix = match ($type) {
            'quote_accepted' => 'quote-accepted',
            'quote_refused' => 'quote-refused',
            'quote_email_sent' => 'quote-emailed',
            default => str_replace('_', '-', $type),
        };

        return [
            'id' => $idPrefix.'-'.$quote->getId(),
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => sprintf('%s · %s', $quote->getNumber(), $quote->getCustomerEmail() ?? 'Client sans email'),
            'createdAt' => $quote->getUpdatedAt()->format(DATE_ATOM),
            'to' => '/admin/quotes/'.$quote->getId(),
            'resource' => [
                'type' => 'quote',
                'id' => $quote->getId(),
                'number' => $quote->getNumber(),
            ],
        ];
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
