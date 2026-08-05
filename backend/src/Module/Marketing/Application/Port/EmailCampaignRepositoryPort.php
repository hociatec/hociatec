<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Port;

use App\Module\Marketing\Domain\Entity\EmailCampaign;

interface EmailCampaignRepositoryPort
{
    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<EmailCampaign>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;
}
