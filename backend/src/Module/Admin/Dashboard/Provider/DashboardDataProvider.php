<?php

declare(strict_types=1);

namespace App\Module\Admin\Dashboard\Provider;

final readonly class DashboardDataProvider
{
    public function __construct(
        private DashboardMetricsProvider $metrics,
        private DashboardNotificationsProvider $notifications,
        private DashboardActivityProvider $activity,
        private DashboardCustomersProvider $customers,
        private DashboardPaymentsProvider $payments,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function provide(): array
    {
        return [
            'metrics' => $this->metrics->provide(),
            'notifications' => $this->notifications->provide(),
            'recentOrders' => $this->activity->recentOrders(),
            'recentEvents' => $this->activity->recentEvents(),
            'topCustomers' => $this->customers->topCustomers(),
            'payments' => $this->payments->provide(),
        ];
    }
}
