<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Repository;

use App\Module\BetaTest\Entity\BugReportComment;
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
}
