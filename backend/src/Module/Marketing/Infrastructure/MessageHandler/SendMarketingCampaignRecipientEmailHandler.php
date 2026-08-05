<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\MessageHandler;

use App\Module\Marketing\Application\Message\MarketingCampaignRecipientEmailMessage;
use App\Module\Marketing\Application\Port\EmailCampaignRecipientRepositoryPort;
use App\Module\Marketing\Application\Provider\MarketingRecipientContextProvider;
use App\Module\Marketing\Application\Workflow\MarketingTemplateRenderer;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsMessageHandler(handles: MarketingCampaignRecipientEmailMessage::class)]
final readonly class SendMarketingCampaignRecipientEmailHandler
{
    public function __construct(
        private EmailCampaignRecipientRepositoryPort $recipients,
        private UserRepositoryPort $users,
        private MarketingRecipientContextProvider $contexts,
        private MarketingTemplateRenderer $renderer,
        private UserCommunicationNotifier $userNotifications,
        private UnitOfWork $persistence,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $mailerFrom,
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
            $context = $this->contexts->provide($user);
            $email = (new Email())
                ->from(new Address($this->mailerFrom, 'Hociatec'))
                ->to(new Address($user->getEmail(), $user->getFullName()))
                ->subject($this->renderer->render($campaign->getSubjectSnapshot(), $context, false))
                ->html($this->renderer->render($campaign->getHtmlSnapshot(), $context, true))
                ->text($this->renderer->render($campaign->getTextSnapshot() ?: strip_tags($campaign->getHtmlSnapshot()), $context, false));

            $this->mailer->send($email);
            $recipient->markSent();
            $this->persistence->commit();
        } catch (TransportExceptionInterface|\RuntimeException $exception) {
            $recipient->markFailed($exception->getMessage());
            $this->persistence->commit();

            throw $exception;
        }
    }
}
