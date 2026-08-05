<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Message;

final readonly class MarketingCampaignRecipientEmailMessage
{
    public function __construct(
        public int $campaignId,
        public int $userId,
    ) {
    }
}
