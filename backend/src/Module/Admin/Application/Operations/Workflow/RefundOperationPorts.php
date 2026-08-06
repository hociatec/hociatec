<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Workflow;

use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Port\RefundRequestRepositoryPort;
use App\Module\Order\Application\Port\StripeRefundClient;

final readonly class RefundOperationPorts
{
    public function __construct(
        public RefundRequestRepositoryPort $refunds,
        public OrderRepositoryPort $orders,
        public OrderCheckoutSessionRepositoryPort $payments,
        public StripeRefundClient $stripe,
    ) {
    }
}
