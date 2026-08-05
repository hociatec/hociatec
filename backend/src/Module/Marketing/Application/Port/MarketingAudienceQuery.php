<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Port;

use App\Module\User\Domain\Entity\User;

interface MarketingAudienceQuery
{
    /**
     * @param array<string, mixed> $criteria
     *
     * @return list<User>
     */
    public function resolveRecipients(string $segmentKey, array $criteria, ?int $limit = null, int $offset = 0): array;
}
