<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\MessageHandler;

use App\Module\Marketing\Application\Message\MarketingCampaignRecipientEmailMessage;
use App\Module\Marketing\Application\Port\EmailCampaignRecipientRepositoryPort;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: MarketingCampaignRecipientEmailMessage::class)]
final readonly class SendMarketingCampaignRecipientEmailHandler
{
    public function __construct(
        private EmailCampaignRecipientRepositoryPort $recipients,
        private UserRepositoryPort $users,
        private MarketingCampaignEmailSender $sender,
        private UserCommunicationNotifier $userNotifications,
        private UnitOfWork $persistence,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(MarketingCampaignRecipientEmailMessage $message): void
    {
        $recipient = $this->recipients->findOneForCampaignAndUserIds($message->campaignId, $message->userId);
        if (null === $recipient) {
            $this->logger->warning('Marketing campaign email skipped: recipient tracking row not found.', [
                'campaignId' => $message->campaignId,
                'userId' => $message->userId,
            ]);

            return;
        }

        if (!$recipient->canAttemptDelivery()) {
            return;
        }

        $user = $this->users->find($message->userId);
        if (!$user instanceof User) {
            $recipient->markSkipped('User not found.');
            $this->persistence->commit();
            $this->logger->warning('Marketing campaign email skipped: user not found.', [
                'campaignId' => $message->campaignId,
                'userId' => $message->userId,
            ]);

            return;
        }

        if (!$this->userNotifications->shouldSendNewsEmail($user)) {
            $recipient->markSkipped('Communication preferences disabled marketing news email.');
            $this->persistence->commit();

            return;
        }

        $campaign = $recipient->getCampaign();
        try {
            $this->sender->send($campaign, $user);
            $recipient->markSent();
            $this->persistence->commit();
        } catch (TransportExceptionInterface|\RuntimeException $exception) {
            $recipient->markFailed($exception->getMessage());
            $this->persistence->commit();

            throw $exception;
        }
    }
}
