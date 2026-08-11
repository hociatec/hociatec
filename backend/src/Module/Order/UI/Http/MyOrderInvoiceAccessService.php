<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Http;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\User\Domain\Entity\User;

final readonly class MyOrderInvoiceAccessService
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private OrderAccessPolicy $accessPolicy,
    ) {
    }

    public function findAccessibleOrder(User $user, int $orderId): ?Order
    {
        $order = $this->orders->find($orderId);
        if (!$order instanceof Order) {
            return null;
        }

        return $this->accessPolicy->canDownloadInvoice($user, $order) ? $order : null;
    }
}
