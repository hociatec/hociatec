<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Domain\Entity\OrderCheckoutSession;

trait OrderCheckoutSessionAdminQueries
{
    /** @return list<OrderCheckoutSession> */
    public function findForAdminList(?string $status, string $query): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.createdAt', 'DESC');

        if (null !== $status && '' !== $status && 'all' !== $status) {
            $qb->andWhere('p.lifecycle.status = :status')->setParameter('status', $status);
        }

        if ('' !== $query) {
            $qb->andWhere('p.customerEmail LIKE :q OR p.customerFullName LIKE :q OR p.payment.stripeSessionId LIKE :q OR p.payment.stripePaymentIntentId LIKE :q')
                ->setParameter('q', '%'.$query.'%');
        }

        /** @var list<OrderCheckoutSession> $items */
        $items = $qb->getQuery()->getResult();

        return $items;
    }
}
