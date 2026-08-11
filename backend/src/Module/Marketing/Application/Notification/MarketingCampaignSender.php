<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Notification;

use App\Module\Marketing\Application\Outbox\PrepareMarketingCampaignHandler;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailCampaignContentSnapshot;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Outbox\Application\Outbox;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final readonly class MarketingCampaignSender
{
    public function __construct(
        private UnitOfWork $persistence,
        private TransactionManager $transactions,
        private Outbox $outbox,
    ) {
    }

    /** @param array<string, mixed> $criteria */
    public function send(
        string $name,
        string $segmentKey,
        array $criteria,
        string $subject,
        string $htmlBody,
        ?string $textBody,
        ?EmailTemplate $template,
        ?string $createdByEmail,
    ): EmailCampaign {
        $campaign = new EmailCampaign(
            $name,
            $segmentKey,
            $criteria,
            new EmailCampaignContentSnapshot($subject, $htmlBody, $textBody),
            0,
            $createdByEmail,
            $template,
        );

        $this->transactions->transactional(function () use ($campaign): void {
            $this->persistence->persist($campaign);
            $this->persistence->flush();
            $campaignId = $campaign->getId();
            if (!is_int($campaignId)) {
                throw new \RuntimeException('Impossible de préparer la campagne marketing sans identifiant.');
            }

            $this->outbox->record(PrepareMarketingCampaignHandler::prepareKey($campaignId), PrepareMarketingCampaignHandler::TYPE, [
                'campaignId' => $campaignId,
                'lastUserId' => 0,
            ]);
        });

        return $campaign;
    }
}
