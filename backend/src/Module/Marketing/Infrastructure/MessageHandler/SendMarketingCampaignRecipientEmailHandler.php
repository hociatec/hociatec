<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\MessageHandler;

use App\Module\Marketing\Application\Message\MarketingCampaignRecipientEmailMessage;
use App\Module\Marketing\Application\Provider\MarketingRecipientContextProvider;
use App\Module\Marketing\Application\Workflow\MarketingTemplateRenderer;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsMessageHandler(handles: MarketingCampaignRecipientEmailMessage::class)]
final readonly class SendMarketingCampaignRecipientEmailHandler
{
    public function __construct(
        private UserRepositoryPort $users,
        private MarketingRecipientContextProvider $contexts,
        private MarketingTemplateRenderer $renderer,
        private UserCommunicationNotifier $userNotifications,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $mailerFrom,
    ) {
    }

    public function __invoke(MarketingCampaignRecipientEmailMessage $message): void
    {
        $user = $this->users->find($message->userId);
        if (!$user instanceof User) {
            $this->logger->warning('Marketing campaign email skipped: user not found.', [
                'campaignId' => $message->campaignId,
                'userId' => $message->userId,
            ]);

            return;
        }

        if (!$this->userNotifications->shouldSendNewsEmail($user)) {
            return;
        }

        $context = $this->contexts->provide($user);
        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject($this->renderer->render($message->subject, $context, false))
            ->html($this->renderer->render($message->htmlBody, $context, true))
            ->text($this->renderer->render($message->textBody ?: strip_tags($message->htmlBody), $context, false));

        $this->mailer->send($email);
    }
}
