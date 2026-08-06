<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Dashboard\Provider;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Order\Application\Port\OrderEventRepositoryPort;

final class DashboardNotificationsProvider
{
    private readonly DashboardQuoteNotificationsProvider $quoteNotifications;
    private readonly DashboardOrderNotificationsProvider $orderNotifications;

    public function __construct(
        QuoteRepositoryPort $quotes,
        OrderRepositoryPort $orders,
        OrderEventRepositoryPort $events,
        OrderFormatter $orderFormatter,
        QuoteFormatter $quoteFormatter,
        ?DashboardQuoteNotificationsProvider $quoteNotifications = null,
        ?DashboardOrderNotificationsProvider $orderNotifications = null,
    ) {
        $this->quoteNotifications = $quoteNotifications ?? new DashboardQuoteNotificationsProvider($quotes, $quoteFormatter);
        $this->orderNotifications = $orderNotifications ?? new DashboardOrderNotificationsProvider($orders, $events, $orderFormatter);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function provide(): array
    {
        $items = [
            ...$this->quoteNotifications->provide(),
            ...$this->orderNotifications->provide(),
        ];

        usort($items, static function (array $left, array $right): int {
            $dateComparison = strcmp((string) $right['createdAt'], (string) $left['createdAt']);
            if (0 !== $dateComparison) {
                return $dateComparison;
            }

            return self::notificationPriority((string) $left['type']) <=> self::notificationPriority((string) $right['type']);
        });

        return array_slice($items, 0, 12);
    }

    private static function notificationPriority(string $type): int
    {
        return match ($type) {
            'quote_accepted' => 10,
            'order_pending_payment' => 20,
            'email_failed' => 30,
            default => 100,
        };
    }
}
