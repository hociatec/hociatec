<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Port;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use Doctrine\DBAL\LockMode;

interface BetaCampaignRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?BetaCampaign;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<BetaCampaign>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
}
