<?php

declare(strict_types=1);

namespace App\Module\Training\Infrastructure\Repository;

use App\Module\Training\Application\Port\TrainingCategoryRepositoryPort;

use App\Module\Training\Domain\Entity\TrainingCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TrainingCategory> */
class TrainingCategoryRepository extends ServiceEntityRepository implements TrainingCategoryRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingCategory::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?TrainingCategory
    {
        $category = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $category instanceof TrainingCategory ? $category : null;
    }

    public function findOneBy(array $criteria, ?array $orderBy = null): ?TrainingCategory
    {
        $category = parent::findOneBy($criteria, $orderBy);

        return $category instanceof TrainingCategory ? $category : null;
    }

    /** @return list<TrainingCategory> */
    public function findOrdered(bool $activeOnly = false, int $limit = 50, int $offset = 0): array
    {
        return $this->orderedQuery($activeOnly)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countOrdered(bool $activeOnly = false): int
    {
        return (int) $this->orderedQuery($activeOnly)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function orderedQuery(bool $activeOnly = false): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        if ($activeOnly) {
            $qb->andWhere('c.isActive = true');
        }

        return $qb;
    }
}
