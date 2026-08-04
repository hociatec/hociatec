<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Dashboard\Provider;

use App\Module\Catalog\Application\Service\GroupedLowStockCounter;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Order\Infrastructure\Repository\RefundRequestRepository;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\Support\Infrastructure\Repository\SupportRequestRepository;
use App\Module\User\Infrastructure\Repository\UserRepository;

final readonly class DashboardMetricsProvider
{
    public function __construct(
        private OrderRepository $orders,
        private UserRepository $users,
        private SupportRequestRepository $supportRequests,
        private RefundRequestRepository $refunds,
        private GroupedLowStockCounter $lowStock,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function provide(): array
    {
        $now = new \DateTimeImmutable();
        $todayStart = $now->setTime(0, 0);

        return [
            'today' => $this->orders->getSummaryBetween($todayStart, $now),
            'week' => $this->orders->getSummaryBetween($todayStart->modify('monday this week'), $now),
            'month' => $this->orders->getSummaryBetween($todayStart->modify('first day of this month'), $now),
            'statusCounts' => $this->orders->getStatusCounts(),
            'issuesCount' => $this->orders->countWithOperationalIssues(),
            'lowStockCount' => $this->lowStock->countPublished(3),
            'customersCount' => $this->users->count([]),
            'supportOpenCount' => $this->supportRequests->count(['status' => [
                SupportRequest::STATUS_NEW,
                SupportRequest::STATUS_IN_PROGRESS,
                SupportRequest::STATUS_WAITING_CUSTOMER,
            ]]),
            'refundsPendingCount' => $this->refunds->count(['status' => RefundRequest::STATUS_REQUESTED]),
        ];
    }
}
