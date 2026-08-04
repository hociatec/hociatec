<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Service;

use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\User\Domain\Entity\User;

final readonly class MarketingCampaignService
{
    public function __construct(
        private MarketingAudienceProvider $audiences,
        private MarketingCampaignSender $sender,
    ) {
    }

    /** @return array<string, array<string, mixed>> */
    public function getSegmentDefinitions(): array
    {
        return $this->audiences->getSegmentDefinitions();
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<string, mixed>
     */
    public function previewAudience(string $segmentKey, array $criteria): array
    {
        return $this->audiences->preview($segmentKey, $criteria);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return list<User>
     */
    public function resolveRecipients(string $segmentKey, array $criteria, ?int $limit = null): array
    {
        return $this->audiences->resolveRecipients($segmentKey, $criteria, $limit);
    }

    /** @param array<string, mixed> $criteria */
    public function sendCampaign(
        string $name,
        string $segmentKey,
        array $criteria,
        string $subject,
        string $htmlBody,
        ?string $textBody,
        ?EmailTemplate $template,
        ?string $createdByEmail,
    ): EmailCampaign {
        return $this->sender->send(
            $name,
            $segmentKey,
            $criteria,
            $subject,
            $htmlBody,
            $textBody,
            $template,
            $createdByEmail,
        );
    }
}
