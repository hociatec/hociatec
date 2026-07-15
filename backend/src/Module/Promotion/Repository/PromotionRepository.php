<?php

declare(strict_types=1);

namespace App\Module\Promotion\Repository;

use App\Module\Promotion\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
 */
class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    /**
     * @return list<Promotion>
     */
    public function findActiveForDate(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.startsAt IS NULL OR p.startsAt <= :now')
            ->andWhere('p.endsAt IS NULL OR p.endsAt >= :now')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
