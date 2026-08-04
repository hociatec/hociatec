<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use App\Module\Catalog\Domain\Entity\Product;

trait ProductStockQueries
{
    public function countLowStock(int $threshold = 3): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.stock <= COALESCE(p.lowStockThreshold, :threshold)')
            ->andWhere('p.isPublished = :published')
            ->setParameter('threshold', max(0, $threshold))
            ->setParameter('published', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Product>
     */
    public function findLowStock(int $threshold = 3, int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('c', 'b')
            ->join('p.category', 'c')
            ->leftJoin('p.brandReference', 'b')
            ->andWhere('p.stock <= COALESCE(p.lowStockThreshold, :threshold)')
            ->andWhere('p.isPublished = :published')
            ->setParameter('threshold', max(0, $threshold))
            ->setParameter('published', true)
            ->orderBy('p.stock', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }
}
