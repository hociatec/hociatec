<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Workflow;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Writer\OrderStatusUpdater;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Enum\OrderStatus;

final readonly class BulkOrderStatusService
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private OrderStatusUpdater $statusUpdater,
    ) {
    }

    /**
     * @param list<int> $orderIds
     */
    public function update(array $orderIds, string $status): int
    {
        if ([] === $orderIds || null === OrderStatus::tryFrom($status)) {
            throw new \InvalidArgumentException('Sélection ou statut invalide.');
        }

        $updated = 0;
        foreach ($orderIds as $orderId) {
            $order = $this->orders->findForUpdate($orderId);
            if (!$order instanceof Order) {
                continue;
            }
            try {
                $this->statusUpdater->update($order, $status, null);
            } catch (\DomainException|\InvalidArgumentException) {
                continue;
            }
            ++$updated;
        }

        return $updated;
    }
}
