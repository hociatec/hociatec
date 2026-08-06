<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Infrastructure\Repository;

use App\Module\BetaTest\Application\Port\BugReportActivityRepositoryPort;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BugReportActivity>
 */
final class BugReportActivityRepository extends ServiceEntityRepository implements BugReportActivityRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BugReportActivity::class);
    }

    /** @return list<BugReportActivity> */
    public function findForReport(BugReport $report): array
    {
        return $this->findBy(['bugReport' => $report], ['createdAt' => 'DESC']);
    }
}
