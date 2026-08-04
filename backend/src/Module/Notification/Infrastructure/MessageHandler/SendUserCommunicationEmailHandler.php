<?php

declare(strict_types=1);

namespace App\Module\Notification\Infrastructure\MessageHandler;

use App\Module\Notification\Application\Message\UserCommunicationEmailMessage;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\User\Infrastructure\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: UserCommunicationEmailMessage::class)]
final readonly class SendUserCommunicationEmailHandler
{
    public function __construct(
        private UserRepository $users,
        private UserCommunicationNotifier $notifier,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UserCommunicationEmailMessage $message): void
    {
        $user = $this->users->find($message->userId);
        if (null === $user) {
            $this->logger->warning('Communication email skipped: user not found.', [
                'userId' => $message->userId,
                'type' => $message->type,
            ]);

            return;
        }

        if (!$this->notifier->shouldSendEmail($user)) {
            return;
        }

        $this->notifier->sendEmailNow($user, $message->title, $message->message, $message->targetUrl, $message->type);
    }
}
