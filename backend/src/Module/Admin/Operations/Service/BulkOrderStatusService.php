<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Service;

use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class BulkOrderStatusService
{
    private const ALLOWED_STATUSES = [
        Order::STATUS_PENDING,
        Order::STATUS_CONFIRMED,
        Order::STATUS_DELIVERED,
        Order::STATUS_CANCELLED,
    ];

    public function __construct(
        private OrderRepository $orders,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<int> $orderIds
     */
    public function update(array $orderIds, string $status): int
    {
        if ([] === $orderIds || !in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException('Sélection ou statut invalide.');
        }

        $updated = 0;
        foreach ($orderIds as $orderId) {
            $order = $this->orders->find($orderId);
            if ($order instanceof Order) {
                $order->setStatus($status);
                ++$updated;
            }
        }
        $this->entityManager->flush();

        return $updated;
    }
}
