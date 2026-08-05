<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Infrastructure\Repository;

use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BugReport>
 */
final class BugReportRepository extends ServiceEntityRepository implements BugReportRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BugReport::class);
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?BugReport
    {
        $report = parent::find($id, $lockMode, $lockVersion);

        return $report instanceof BugReport ? $report : null;
    }

    /**
     * @return list<BugReport>
     */
    public function findForUser(User $user): array
    {
        return $this->findBy(['reporter' => $user], ['createdAt' => 'DESC']);
    }

    /**
     * @return list<BugReport>
     */
    public function findForUserPaginated(User $user, int $limit, int $offset): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.campaign', 'c')->addSelect('c')
            ->leftJoin('r.assignedTo', 'a')->addSelect('a')
            ->leftJoin('r.duplicateOf', 'd')->addSelect('d')
            ->andWhere('r.reporter = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.reporter = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<BugReport>
     */
    public function findForAdmin(array $filters, int $limit, int $offset): array
    {
        $qb = $this->adminQueryBuilder($filters)
            ->leftJoin('r.campaign', 'c')->addSelect('c')
            ->leftJoin('r.reporter', 'reporter')->addSelect('reporter')
            ->leftJoin('r.assignedTo', 'a')->addSelect('a')
            ->leftJoin('r.duplicateOf', 'd')->addSelect('d')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countForAdmin(array $filters): int
    {
        return (int) $this->adminQueryBuilder($filters)
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<BugReport>
     */
    public function findExportRows(array $filters): array
    {
        return $this->adminQueryBuilder($filters)
            ->leftJoin('r.campaign', 'c')->addSelect('c')
            ->leftJoin('r.reporter', 'reporter')->addSelect('reporter')
            ->leftJoin('r.assignedTo', 'a')->addSelect('a')
            ->leftJoin('r.duplicateOf', 'd')->addSelect('d')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(5000)
            ->getQuery()
            ->getResult();
    }

    /** @return array<string, int> */
    public function dashboardStats(): array
    {
        $open = (int) $this->createQueryBuilder('r')->select('COUNT(r.id)')->andWhere('r.status NOT IN (:closed)')->setParameter('closed', BugReport::CLOSED_STATUSES)->getQuery()->getSingleScalarResult();
        $criticalHigh = (int) $this->createQueryBuilder('r')->select('COUNT(r.id)')->andWhere('r.status NOT IN (:closed)')->andWhere('r.severity IN (:severity)')->setParameter('closed', BugReport::CLOSED_STATUSES)->setParameter('severity', ['critical', 'high'])->getQuery()->getSingleScalarResult();
        $awaitingAdmin = (int) $this->createQueryBuilder('r')->select('COUNT(r.id)')->andWhere('r.status NOT IN (:closed)')->andWhere('r.lastReporterReplyAt IS NOT NULL')->andWhere('r.lastAdminReplyAt IS NULL OR r.lastReporterReplyAt > r.lastAdminReplyAt')->setParameter('closed', BugReport::CLOSED_STATUSES)->getQuery()->getSingleScalarResult();
        $awaitingUser = (int) $this->createQueryBuilder('r')->select('COUNT(r.id)')->andWhere('r.status = :status OR (r.lastAdminReplyAt IS NOT NULL AND (r.lastReporterReplyAt IS NULL OR r.lastAdminReplyAt > r.lastReporterReplyAt) AND r.status NOT IN (:closed))')->setParameter('status', BugReport::STATUS_NEED_INFO)->setParameter('closed', BugReport::CLOSED_STATUSES)->getQuery()->getSingleScalarResult();
        $recentFixed = (int) $this->createQueryBuilder('r')->select('COUNT(r.id)')->andWhere('r.status = :status')->andWhere('r.updatedAt >= :since')->setParameter('status', BugReport::STATUS_RESOLVED)->setParameter('since', new \DateTimeImmutable('-14 days'))->getQuery()->getSingleScalarResult();

        return [
            'openReports' => $open,
            'criticalOrHigh' => $criticalHigh,
            'awaitingAdminReply' => $awaitingAdmin,
            'awaitingUserReply' => $awaitingUser,
            'recentFixed' => $recentFixed,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function adminQueryBuilder(array $filters)
    {
        $qb = $this->createQueryBuilder('r');

        if (isset($filters['status']) && '' !== $filters['status']) {
            $qb->andWhere('r.status = :status')->setParameter('status', $filters['status']);
        }
        if (isset($filters['severity']) && '' !== $filters['severity']) {
            $qb->andWhere('r.severity = :severity')->setParameter('severity', $filters['severity']);
        }
        if (isset($filters['campaignId'])) {
            $qb->andWhere('r.campaign = :campaignId')->setParameter('campaignId', $filters['campaignId']);
        }
        if (isset($filters['assignedTo'])) {
            $qb->andWhere('r.assignedTo = :assignedTo')->setParameter('assignedTo', $filters['assignedTo']);
        }
        if (isset($filters['search']) && '' !== $filters['search']) {
            $qb->leftJoin('r.reporter', 'searchReporter')
                ->andWhere('LOWER(r.title) LIKE :search OR LOWER(r.description) LIKE :search OR LOWER(searchReporter.identity.email) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower((string) $filters['search']).'%');
        }

        return $qb;
    }

    public function countOpenForCampaign(BetaCampaign $campaign): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.campaign = :campaign')
            ->andWhere('r.status NOT IN (:closed)')
            ->setParameter('campaign', $campaign)
            ->setParameter('closed', BugReport::CLOSED_STATUSES)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
