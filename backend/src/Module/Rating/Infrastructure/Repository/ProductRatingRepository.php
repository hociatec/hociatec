<?php

declare(strict_types=1);

namespace App\Module\Rating\Infrastructure\Repository;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rating\Domain\Entity\ProductRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductRating>
 */
class ProductRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductRating::class);
    }

    /**
     * @param list<int> $orderItemIds
     *
     * @return array<int, ProductRating>
     */
    public function findByOrderItemIds(array $orderItemIds): array
    {
        if ([] === $orderItemIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('r')
            ->addSelect('c')
            ->leftJoin('r.comment', 'c')
            ->andWhere('r.orderItem IN (:ids)')
            ->setParameter('ids', $orderItemIds);

        $results = $qb->getQuery()->getResult();
        $map = [];
        foreach ($results as $rating) {
            if ($rating instanceof ProductRating && null !== $rating->getOrderItem()->getId()) {
                $map[$rating->getOrderItem()->getId()] = $rating;
            }
        }

        return $map;
    }

    /**
     * @return list<ProductRating>
     */
    public function findPublishedByProduct(Product $product, int $limit, int $offset = 0): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('c', 'u')
            ->leftJoin('r.comment', 'c')
            ->join('r.user', 'u')
            ->andWhere('r.product = :product')
            ->andWhere('r.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', ProductRating::STATUS_PUBLISHED)
            ->orderBy('r.publishedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{count:int, average:float}
     */
    public function getStatsForProduct(Product $product): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) AS reviewsCount')
            ->addSelect('COALESCE(AVG(r.score), 0) AS averageScore')
            ->andWhere('r.product = :product')
            ->andWhere('r.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', ProductRating::STATUS_PUBLISHED);

        $row = $qb->getQuery()->getSingleResult();

        return [
            'count' => (int) ($row['reviewsCount'] ?? 0),
            'average' => (float) ($row['averageScore'] ?? 0),
        ];
    }

    public function existsForOrderItem(OrderItem $orderItem): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('1')
            ->andWhere('r.orderItem = :item')
            ->setParameter('item', $orderItem)
            ->setMaxResults(1);

        return null !== $qb->getQuery()->getOneOrNullResult();
    }
}
