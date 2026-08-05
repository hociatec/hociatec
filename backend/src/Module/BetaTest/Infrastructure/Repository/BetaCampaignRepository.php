<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Infrastructure\Repository;

use App\Module\BetaTest\Application\Port\BetaCampaignRepositoryPort;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
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

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?BetaCampaign
    {
        $campaign = parent::find($id, $lockMode, $lockVersion);

        return $campaign instanceof BetaCampaign ? $campaign : null;
    }
}
