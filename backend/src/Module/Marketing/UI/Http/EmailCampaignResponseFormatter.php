<?php

declare(strict_types=1);

namespace App\Module\Marketing\UI\Http;

use App\Module\Marketing\Domain\Entity\EmailCampaign;

final readonly class EmailCampaignResponseFormatter
{
    /** @return array<string, mixed> */
    public function format(EmailCampaign $campaign): array
    {
        return [
            'id' => $campaign->getId(),
            'name' => $campaign->getName(),
            'segmentKey' => $campaign->getSegmentKey(),
            'criteria' => $campaign->getCriteria(),
            'subjectSnapshot' => $campaign->getSubjectSnapshot(),
            'recipientsCount' => $campaign->getRecipientsCount(),
            'createdByEmail' => $campaign->getCreatedByEmail(),
            'sentAt' => $campaign->getSentAt()->format(DATE_ATOM),
            'template' => $campaign->getTemplate() ? [
                'id' => $campaign->getTemplate()->getId(),
                'name' => $campaign->getTemplate()->getName(),
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    public function summary(EmailCampaign $campaign): array
    {
        return [
            'id' => $campaign->getId(),
            'name' => $campaign->getName(),
            'recipientsCount' => $campaign->getRecipientsCount(),
            'sentAt' => $campaign->getSentAt()->format(DATE_ATOM),
        ];
    }
}
