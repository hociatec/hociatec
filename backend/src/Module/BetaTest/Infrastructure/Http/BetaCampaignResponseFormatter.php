<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Infrastructure\Http;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;

final readonly class BetaCampaignResponseFormatter
{
    /** @return array<string, mixed> */
    public function format(BetaCampaign $campaign, \DateTimeImmutable $now): array
    {
        return [
            'id' => $campaign->getId(),
            'name' => $campaign->getName(),
            'description' => $campaign->getDescription(),
            'status' => $campaign->getEffectiveStatus($now),
            'startsAt' => $campaign->getStartsAt()?->format(DATE_ATOM),
            'endsAt' => $campaign->getEndsAt()?->format(DATE_ATOM),
        ];
    }
}
