<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Notification;

use App\Module\Marketing\Application\Message\MarketingCampaignRecipientEmailMessage;
use App\Module\Marketing\Application\Provider\MarketingAudienceProvider;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Shared\Application\UnitOfWork;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MarketingCampaignSender
{
    private const BATCH_SIZE = 200;

    public function __construct(
        private MarketingAudienceProvider $audiences,
        private UserCommunicationNotifier $userNotifications,
        private UnitOfWork $persistence,
        private MessageBusInterface $messageBus,
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
            $subject,
            $htmlBody,
            $textBody,
            0,
            $createdByEmail,
            $template,
        );
        $this->persistence->persist($campaign);
        $this->persistence->commit();

        $queuedRecipients = 0;
        $offset = 0;

        do {
            $users = $this->audiences->resolveRecipients($segmentKey, $criteria, self::BATCH_SIZE, $offset);
            foreach ($users as $user) {
                if (!$this->userNotifications->shouldSendNewsEmail($user)) {
                    continue;
                }

                $this->messageBus->dispatch(new MarketingCampaignRecipientEmailMessage(
                    (int) $campaign->getId(),
                    (int) $user->getId(),
                    $subject,
                    $htmlBody,
                    $textBody,
                ));
                ++$queuedRecipients;
            }

            $offset += self::BATCH_SIZE;
        } while (count($users) === self::BATCH_SIZE);

        $campaign->updateRecipientsCount($queuedRecipients);
        $this->persistence->commit();

        return $campaign;
    }
}
