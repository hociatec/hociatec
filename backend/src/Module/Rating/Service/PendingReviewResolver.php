<?php

declare(strict_types=1);

namespace App\Module\Rating\Service;

use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderItem;
use App\Module\Order\Repository\OrderItemRepository;
use App\Module\Rating\Entity\ProductRating;
use App\Module\User\Entity\User;

class PendingReviewResolver
{
    public function __construct(
        private readonly OrderItemRepository $orderItems,
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
            ->andWhere('o.status = :status')
            ->andWhere('r.id IS NULL')
            ->andWhere('p IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('status', Order::STATUS_DELIVERED)
            ->orderBy('o.createdAt', 'DESC');

        /** @var list<OrderItem> $items */
        $items = $qb->getQuery()->getResult();

        return array_map(static function (OrderItem $item) {
            $order = $item->getOrder();
            $product = $item->getProduct();

            return [
                'orderId' => $order->getId(),
                'orderNumber' => $order->getNumber(),
                'orderCreatedAt' => $order->getCreatedAt()->format(DATE_ATOM),
                'orderItemId' => $item->getId(),
                'product' => $product ? [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'sku' => $product->getSku(),
                ] : null,
            ];
        }, $items);
    }
}
