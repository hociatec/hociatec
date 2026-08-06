<?php

declare(strict_types=1);

namespace App\Module\Rating\Application\Provider;

use App\Module\Order\Application\Port\OrderItemRepositoryPort;
use App\Module\User\Domain\Entity\User;

class PendingReviewResolver
{
    public function __construct(
        private readonly OrderItemRepositoryPort $orderItems,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resolve(User $user, int $limit = 20, int $offset = 0): array
    {
        $items = $this->orderItems->findPendingReviewItemsForUser($user, $limit, $offset);

        $pending = [];
        foreach ($items as $item) {
            $order = $item->getOrder();
            $product = $item->getProduct();
            if (null === $order || null === $product) {
                continue;
            }

            $pending[] = [
                'orderId' => $order->getId(),
                'orderNumber' => $order->getNumber(),
                'orderCreatedAt' => $order->getCreatedAt()->format(DATE_ATOM),
                'orderItemId' => $item->getId(),
                'product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'sku' => $product->getSku(),
                ],
            ];
        }

        return $pending;
    }

    public function count(User $user): int
    {
        return $this->orderItems->countPendingReviewItemsForUser($user);
    }
}
