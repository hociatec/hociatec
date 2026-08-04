<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Service;

use App\Infrastructure\Application\TransactionManager;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Enum\OrderStatus;
use App\Module\Order\Infrastructure\Repository\OrderRepository;

final readonly class BulkOrderStatusService
{
    public function __construct(
        private OrderRepository $orders,
        private OperationsPersistence $persistence,
        private TransactionManager $transactions,
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

        return $this->transactions->transactional(function () use ($orderIds, $status): int {
            $updated = 0;
            foreach ($orderIds as $orderId) {
                $order = $this->orders->findForUpdate($orderId);
                if ($order instanceof Order) {
                    $order->setStatus($status);
                    ++$updated;
                }
            }
            $this->persistence->flush();

            return $updated;
        });
    }
}
