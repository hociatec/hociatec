<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Domain\Entity\Order;

final readonly class OrderEmailScenarioResender
{
    public function __construct(private OrderNotificationEmailService $notifications)
    {
    }

    public function resend(Order $order, string $scenario): bool
    {
        return match ($scenario) {
            'order_created' => $this->notifications->resendOrderCreated($order),
            'invoice_issued' => $this->notifications->resendInvoiceIssued($order),
            'current_status' => $this->resendCurrentStatus($order),
            default => throw new \InvalidArgumentException('Scénario email invalide.'),
        };
    }

    private function resendCurrentStatus(Order $order): bool
    {
        return match ($order->getStatus()) {
            Order::STATUS_DELIVERED, Order::STATUS_CANCELLED => $this->notifications->resendStatusChanged($order, $order->getStatus(), $order->getStatus()),
            default => false,
        };
    }
}
