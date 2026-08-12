<?php

declare(strict_types=1);

namespace App\Module\Training\Infrastructure\Repository;

use App\Module\Training\Application\Port\TrainingRepositoryPort;
use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingCategory;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use App\Shared\Infrastructure\Persistence\LikeSearchHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Training> */
class TrainingRepository extends ServiceEntityRepository implements TrainingRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Training::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Training
    {
        $training = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $training instanceof Training ? $training : null;
    }

    public function findOneBy(array $criteria, ?array $orderBy = null): ?Training
    {
        $training = parent::findOneBy($criteria, $orderBy);

        return $training instanceof Training ? $training : null;
    }

    /** @return list<Training> */
    public function findActive(?string $category = null): array
    {
        $criteria = ['isActive' => true];
        if (null !== $category && '' !== $category) {
            $criteria['category'] = $category;
        }

        return $this->findBy($criteria, ['title' => 'ASC']);
    }

    /** @return list<Training> */
    public function findActivePaginated(?string $category, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.title', 'ASC')
            ->setMaxResults(max(1, min(100, $limit)))
            ->setFirstResult(max(0, $offset));

        if (null !== $category && '' !== $category) {
            $qb->andWhere('t.category = :category')->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    public function countActive(?string $category = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true);

        if (null !== $category && '' !== $category) {
            $qb->andWhere('t.category = :category')->setParameter('category', $category);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @return list<Training> */
    public function findPublicCatalog(
        ?string $search,
        ?string $category,
        ?string $format,
        ?int $minPriceCents,
        ?int $maxPriceCents,
        ?int $minDurationMinutes,
        ?int $maxDurationMinutes,
        string $sort,
        int $limit,
        int $offset,
    ): array {
        $qb = $this->createPublicCatalogQuery(
            $search,
            $category,
            $format,
            $minPriceCents,
            $maxPriceCents,
            $minDurationMinutes,
            $maxDurationMinutes,
        );

        match ($sort) {
            'price_asc' => $qb->orderBy('t.priceCents', 'ASC')->addOrderBy('t.title', 'ASC'),
            'price_desc' => $qb->orderBy('t.priceCents', 'DESC')->addOrderBy('t.title', 'ASC'),
            'duration_asc' => $qb->orderBy('t.durationMinutes', 'ASC')->addOrderBy('t.title', 'ASC'),
            'duration_desc' => $qb->orderBy('t.durationMinutes', 'DESC')->addOrderBy('t.title', 'ASC'),
            default => $qb->orderBy('t.title', 'ASC'),
        };

        return $qb
            ->setMaxResults(max(1, min(100, $limit)))
            ->setFirstResult(max(0, $offset))
            ->getQuery()
            ->getResult();
    }

    public function countPublicCatalog(
        ?string $search,
        ?string $category,
        ?string $format,
        ?int $minPriceCents,
        ?int $maxPriceCents,
        ?int $minDurationMinutes,
        ?int $maxDurationMinutes,
    ): int {
        return (int) $this->createPublicCatalogQuery(
            $search,
            $category,
            $format,
            $minPriceCents,
            $maxPriceCents,
            $minDurationMinutes,
            $maxDurationMinutes,
        )
            ->select('COUNT(DISTINCT t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createPublicCatalogQuery(
        ?string $search,
        ?string $category,
        ?string $format,
        ?int $minPriceCents,
        ?int $maxPriceCents,
        ?int $minDurationMinutes,
        ?int $maxDurationMinutes,
    ): \Doctrine\ORM\QueryBuilder {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin(TrainingCategory::class, 'c', 'WITH', 'c.slug = t.category')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true);

        $searchPattern = LikeSearchHelper::containsPattern($search, true);
        if (null !== $searchPattern) {
            $qb
                ->andWhere('LOWER(t.title) LIKE :search OR LOWER(COALESCE(t.shortDescription, \'\')) LIKE :search OR LOWER(COALESCE(t.objective, \'\')) LIKE :search OR LOWER(COALESCE(t.audience, \'\')) LIKE :search OR LOWER(COALESCE(c.name, \'\')) LIKE :search')
                ->setParameter('search', $searchPattern);
        }

        if (null !== $category && '' !== $category) {
            $qb->andWhere('t.category = :category')->setParameter('category', $category);
        }

        if (null !== $format && '' !== $format) {
            $qb->andWhere('t.availableFormats LIKE :format')->setParameter('format', sprintf('%%"%s"%%', $format));
        }

        if (null !== $minPriceCents && $minPriceCents >= 0) {
            $qb->andWhere('t.priceCents >= :minPriceCents')->setParameter('minPriceCents', $minPriceCents);
        }

        if (null !== $maxPriceCents && $maxPriceCents >= 0) {
            $qb->andWhere('t.priceCents <= :maxPriceCents')->setParameter('maxPriceCents', $maxPriceCents);
        }

        if (null !== $minDurationMinutes && $minDurationMinutes >= 0) {
            $qb->andWhere('t.durationMinutes >= :minDurationMinutes')->setParameter('minDurationMinutes', $minDurationMinutes);
        }

        if (null !== $maxDurationMinutes && $maxDurationMinutes >= 0) {
            $qb->andWhere('t.durationMinutes <= :maxDurationMinutes')->setParameter('maxDurationMinutes', $maxDurationMinutes);
        }

        return $qb;
    }
}
