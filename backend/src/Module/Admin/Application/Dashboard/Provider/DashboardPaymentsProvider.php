<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Dashboard\Provider;

use App\Module\Admin\Application\Payment\Service\AdminPaymentFormatter;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Infrastructure\Repository\OrderCheckoutSessionRepository;

final readonly class DashboardPaymentsProvider
{
    public function __construct(
        private OrderCheckoutSessionRepository $payments,
        private AdminPaymentFormatter $formatter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function provide(): array
    {
        return [
            'statusCounts' => $this->payments->getStatusCounts(),
            'paidWithoutOrderCount' => $this->payments->countPaidWithoutOrder(),
            'recent' => array_map(
                fn (OrderCheckoutSession $payment): array => $this->formatter->summary($payment),
                $this->payments->findRecentForDashboard(6),
            ),
            'attention' => array_map(
                fn (OrderCheckoutSession $payment): array => $this->formatter->summary($payment),
                $this->payments->findAttentionItemsForDashboard(6),
            ),
        ];
    }
}
