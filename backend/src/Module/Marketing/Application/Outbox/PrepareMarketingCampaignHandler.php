<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Outbox;

use App\Module\Marketing\Application\Port\EmailCampaignRecipientRepositoryPort;
use App\Module\Marketing\Application\Port\EmailCampaignRepositoryPort;
use App\Module\Marketing\Application\Provider\MarketingAudienceProvider;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailCampaignRecipient;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Outbox\Application\Outbox;
use App\Module\Outbox\Application\OutboxEventHandler;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class PrepareMarketingCampaignHandler implements OutboxEventHandler
{
    public const TYPE = 'marketing.campaign.prepare_requested';
    private const BATCH_SIZE = 200;

    public function __construct(
        private EmailCampaignRepositoryPort $campaigns,
        private MarketingAudienceProvider $audiences,
        private UserCommunicationNotifier $userNotifications,
        private EmailCampaignRecipientRepositoryPort $recipients,
        private Outbox $outbox,
        private UnitOfWork $persistence,
    ) {
    }

    public function supports(OutboxEvent $event): bool
    {
        return self::TYPE === $event->getType();
    }

    public function handle(OutboxEvent $event): void
    {
        $payload = $event->getPayload();
        $campaignId = $payload['campaignId'] ?? null;
        $lastUserId = $payload['lastUserId'] ?? 0;
        if (!is_int($campaignId) || !is_int($lastUserId)) {
            throw new \RuntimeException('Marketing campaign preparation outbox payload is invalid.');
        }

        $campaign = $this->campaigns->find($campaignId);
        if (!$campaign instanceof EmailCampaign) {
            return;
        }

        $users = $this->audiences->resolveRecipientsAfterId($campaign->getSegmentKey(), $campaign->getCriteria(), $lastUserId, self::BATCH_SIZE);
        if ([] === $users) {
            return;
        }

        $userIds = array_values(array_filter(
            array_map(static fn (User $user): ?int => $user->getId(), $users),
            static fn (?int $id): bool => null !== $id,
        ));
        $existingUserIds = array_flip($this->recipients->findExistingUserIdsForCampaign($campaignId, $userIds));
        $nextLastUserId = $lastUserId;

        foreach ($users as $user) {
            $userId = $user->getId();
            if (!is_int($userId)) {
                continue;
            }
            $nextLastUserId = max($nextLastUserId, $userId);
            if (isset($existingUserIds[$userId])) {
                continue;
            }

            if (!$this->userNotifications->shouldSendNewsEmail($user)) {
                $this->persistence->persist(EmailCampaignRecipient::skipped($campaign, $user, 'Communication preferences disabled marketing news email.'));
                continue;
            }

            $this->persistence->persist(EmailCampaignRecipient::pending($campaign, $user));
            $this->outbox->record(self::recipientEmailKey($campaignId, $userId), DispatchMarketingCampaignRecipientEmailHandler::TYPE, [
                'campaignId' => $campaignId,
                'userId' => $userId,
            ]);
        }

        if (count($users) === self::BATCH_SIZE) {
            $this->outbox->record(self::prepareKey($campaignId, $nextLastUserId), self::TYPE, [
                'campaignId' => $campaignId,
                'lastUserId' => $nextLastUserId,
            ]);
        }
    }

    public static function prepareKey(int $campaignId, int $lastUserId = 0): string
    {
        return sprintf('marketing.campaign.prepare.%d.%d', $campaignId, $lastUserId);
    }

    public static function recipientEmailKey(int $campaignId, int $userId): string
    {
        return sprintf('marketing.campaign.recipient_email.%d.%d', $campaignId, $userId);
    }
}
