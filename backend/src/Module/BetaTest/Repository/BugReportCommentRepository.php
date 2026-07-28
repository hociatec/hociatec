<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Repository;

use App\Module\BetaTest\Entity\BugReportComment;
use App\Module\BetaTest\Entity\BugReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BugReportComment>
 */
final class BugReportCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BugReportComment::class);
    }

    /** @return list<BugReportComment> */
    public function findForReportPaginated(BugReport $report, int $limit, int $offset): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.author', 'a')->addSelect('a')
            ->andWhere('c.bugReport = :report')
            ->setParameter('report', $report)
            ->orderBy('c.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countForReport(BugReport $report): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.bugReport = :report')
            ->setParameter('report', $report)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
