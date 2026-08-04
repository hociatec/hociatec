<?php

declare(strict_types=1);

namespace App\Module\News\Infrastructure\MessageHandler;

use App\Module\News\Application\Message\NewsArticlePublishedEmailMessage;
use App\Module\Notification\Application\Service\UserCommunicationNotifier;
use App\Module\User\Infrastructure\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: NewsArticlePublishedEmailMessage::class)]
final readonly class SendNewsArticlePublishedEmailHandler
{
    public function __construct(
        private UserRepository $users,
        private UserCommunicationNotifier $notifier,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(NewsArticlePublishedEmailMessage $message): void
    {
        $user = $this->users->find($message->userId);
        if (null === $user) {
            $this->logger->warning('News email skipped: user not found.', [
                'userId' => $message->userId,
                'slug' => $message->slug,
            ]);

            return;
        }

        if (!$this->notifier->shouldSendNewsEmail($user)) {
            return;
        }

        $this->notifier->sendEmailNow(
            $user,
            'Nouvelle actualité Hociatec : '.$message->title,
            $message->excerpt,
            '/actualites/'.$message->slug,
            'news_article',
        );
    }
}
