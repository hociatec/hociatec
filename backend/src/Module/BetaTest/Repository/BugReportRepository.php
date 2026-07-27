<?php
declare(strict_types=1);
namespace App\Module\BetaTest\Repository;
use App\Module\BetaTest\Entity\BugReport;
use App\Module\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
final class BugReportRepository extends ServiceEntityRepository { public function __construct(ManagerRegistry $registry) { parent::__construct($registry, BugReport::class); } public function findForUser(User $user): array { return $this->findBy(['reporter'=>$user], ['createdAt'=>'DESC']); } }
