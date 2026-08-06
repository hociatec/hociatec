<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Infrastructure\Repository;

use App\Module\BetaTest\Application\Port\BetaCampaignRepositoryPort;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BetaCampaign>
 */
final class BetaCampaignRepository extends ServiceEntityRepository implements BetaCampaignRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BetaCampaign::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?BetaCampaign
    {
        $campaign = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $campaign instanceof BetaCampaign ? $campaign : null;
    }

    /** @return list<BetaCampaign> */
    public function findOpenForReports(\DateTimeImmutable $now, int $limit = 20, int $offset = 0): array
    {
        return $this->createOpenForReportsQuery($now)
            ->orderBy('c.startsAt', 'ASC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countOpenForReports(\DateTimeImmutable $now): int
    {
        return (int) $this->createOpenForReportsQuery($now)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createOpenForReportsQuery(\DateTimeImmutable $now): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->andWhere('c.startsAt IS NULL OR c.startsAt <= :now')
            ->andWhere('c.endsAt IS NULL OR c.endsAt >= :now')
            ->setParameter('status', BetaCampaign::STATUS_ACTIVE)
            ->setParameter('now', $now);
    }
}
