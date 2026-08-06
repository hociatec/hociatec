<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Outbox;

use App\Module\Marketing\Application\Message\MarketingCampaignRecipientEmailMessage;
use App\Module\Outbox\Application\OutboxEventHandler;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Shared\Application\Messaging\AsyncMessageDispatcher;

final readonly class DispatchMarketingCampaignRecipientEmailHandler implements OutboxEventHandler
{
    public const TYPE = 'marketing.campaign.recipient_email_requested';

    public function __construct(private AsyncMessageDispatcher $messageBus)
    {
    }

    public function supports(OutboxEvent $event): bool
    {
        return self::TYPE === $event->getType();
    }

    public function handle(OutboxEvent $event): void
    {
        $payload = $event->getPayload();
        $campaignId = $payload['campaignId'] ?? null;
        $userId = $payload['userId'] ?? null;
        if (!is_int($campaignId) || !is_int($userId)) {
            throw new \RuntimeException('Marketing recipient email outbox payload is invalid.');
        }

        $this->messageBus->dispatch(new MarketingCampaignRecipientEmailMessage($campaignId, $userId));
    }
}
