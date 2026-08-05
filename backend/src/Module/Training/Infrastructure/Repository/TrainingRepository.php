<?php

declare(strict_types=1);

namespace App\Module\Training\Infrastructure\Repository;

use App\Module\Training\Application\Port\TrainingRepositoryPort;

use App\Module\Training\Domain\Entity\Training;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
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
}
