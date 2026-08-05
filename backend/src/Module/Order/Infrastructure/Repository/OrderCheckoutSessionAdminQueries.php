<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Domain\Entity\OrderCheckoutSession;

trait OrderCheckoutSessionAdminQueries
{
    /** @return list<OrderCheckoutSession> */
    public function findForAdminList(?string $status, string $query, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createPaymentAdminListQuery($status, $query)
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)));

        /** @var list<OrderCheckoutSession> $items */
        $items = $qb->getQuery()->getResult();

        return $items;
    }

    public function countForAdminList(?string $status, string $query): int
    {
        return (int) $this->createPaymentAdminListQuery($status, $query)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createPaymentAdminListQuery(?string $status, string $query): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u');

        if (null !== $status && '' !== $status && 'all' !== $status) {
            $qb->andWhere('p.lifecycle.status = :status')->setParameter('status', $status);
        }

        if ('' !== $query) {
            $qb->andWhere('p.customerEmail LIKE :q OR p.customerFullName LIKE :q OR p.payment.stripeSessionId LIKE :q OR p.payment.stripePaymentIntentId LIKE :q')
                ->setParameter('q', '%'.$query.'%');
        }

        return $qb;
    }
}
