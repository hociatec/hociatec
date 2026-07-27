<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Repository;

use App\Module\BetaTest\Entity\BetaCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class BetaCampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BetaCampaign::class);
    }
}
