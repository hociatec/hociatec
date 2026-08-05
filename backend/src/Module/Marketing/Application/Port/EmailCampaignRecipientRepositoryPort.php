<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Port;

use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailCampaignRecipient;
use App\Module\User\Domain\Entity\User;

interface EmailCampaignRecipientRepositoryPort
{
    public function findOneForCampaignAndUser(EmailCampaign $campaign, User $user): ?EmailCampaignRecipient;

    public function findOneForCampaignAndUserIds(int $campaignId, int $userId): ?EmailCampaignRecipient;
}
