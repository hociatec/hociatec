<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Port;

use App\Module\Marketing\Domain\Entity\EmailCampaignRecipient;

interface EmailCampaignRecipientRepositoryPort
{
    public function findOneForCampaignAndUserIds(int $campaignId, int $userId): ?EmailCampaignRecipient;

    /**
     * @param list<int> $userIds
     *
     * @return list<int>
     */
    public function findExistingUserIdsForCampaign(int $campaignId, array $userIds): array;
}
