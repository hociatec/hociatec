<?php

declare(strict_types=1);

namespace App\Module\Rating\Application\Provider;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Application\Port\OrderItemRepositoryPort;
use App\Module\Rating\Domain\Entity\ProductRating;
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
    public function resolve(User $user): array
    {
        $qb = $this->orderItems->createQueryBuilder('oi')
            ->addSelect('o', 'p')
            ->join('oi.order', 'o')
            ->leftJoin('oi.product', 'p')
            ->leftJoin(ProductRating::class, 'r', 'WITH', 'r.orderItem = oi')
            ->andWhere('o.user = :user')
            ->andWhere('o.state.status = :status')
            ->andWhere('r.id IS NULL')
            ->andWhere('p IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('status', Order::STATUS_DELIVERED)
            ->orderBy('o.createdAt', 'DESC');

        /** @var list<OrderItem> $items */
        $items = $qb->getQuery()->getResult();

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
}
