<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\Repository;

use App\Module\Marketing\Domain\Entity\EmailCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailCampaign>
 */
class EmailCampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailCampaign::class);
    }
}
