<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\Repository;

use App\Module\Marketing\Application\Port\EmailCampaignRepositoryPort;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailCampaign>
 */
class EmailCampaignRepository extends ServiceEntityRepository implements EmailCampaignRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailCampaign::class);
    }

    public function find(mixed $id, mixed $lockMode = null, ?int $lockVersion = null): ?EmailCampaign
    {
        $campaign = parent::find($id, $lockMode, $lockVersion);

        return $campaign instanceof EmailCampaign ? $campaign : null;
    }
}
