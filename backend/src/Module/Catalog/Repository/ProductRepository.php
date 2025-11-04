<?php

declare(strict_types=1);

namespace App\Module\Catalog\Repository;

use App\Module\Catalog\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return list<Product>
     */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('c')
            ->leftJoin('p.category', 'c')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Product>
     */
    public function findPublished(
        ?string $categorySlug = null,
        ?string $search = null,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
    ): array
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('c')
            ->join('p.category', 'c')
            ->andWhere('p.isPublished = :published')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('published', true)
            ->setParameter('visible', true)
            ->orderBy('p.name', 'ASC');

        if ($onlyFeatured === true) {
            $qb
                ->andWhere('p.isFeaturedHome = :featured')
                ->setParameter('featured', true);
        }

        if ($categorySlug !== null && $categorySlug !== '') {
            $qb
                ->andWhere('c.slug = :slug')
                ->setParameter('slug', $categorySlug);
        }

        if ($search !== null && $search !== '') {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        'LOWER(p.name) LIKE LOWER(:search)',
                        'LOWER(p.description) LIKE LOWER(:search)',
                        'LOWER(p.sku) LIKE LOWER(:search)'
                    )
                )
                ->setParameter('search', sprintf('%%%s%%', $search));
        }

        if ($sellingType !== null && in_array($sellingType, ['sale', 'rental'], true)) {
            $qb
                ->andWhere('p.sellingType = :stype')
                ->setParameter('stype', $sellingType);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOnePublishedBySlug(string $slug): ?Product
    {
        return $this->createQueryBuilder('p')
            ->addSelect('c')
            ->join('p.category', 'c')
            ->andWhere('p.slug = :slug')
            ->andWhere('p.isPublished = :published')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('slug', $slug)
            ->setParameter('published', true)
            ->setParameter('visible', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsWithSku(string $sku, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('1')
            ->andWhere('LOWER(p.sku) = LOWER(:sku)')
            ->setParameter('sku', $sku)
            ->setMaxResults(1);

        if ($excludeId !== null) {
            $qb
                ->andWhere('p.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }

    public function existsWithSlug(string $slug, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('1')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1);

        if ($excludeId !== null) {
            $qb
                ->andWhere('p.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }
}
