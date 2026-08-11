<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Notification;

use App\Module\Notification\Application\Port\AccountNotificationEventRepositoryPort;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;
use Psr\Log\LoggerInterface;

final readonly class InternalAccountNotificationSender
{
    public function __construct(
        private AccountNotificationEventRepositoryPort $notifications,
        private UnitOfWork $persistence,
        private CommunicationPreferencePolicy $preferences,
        private LoggerInterface $logger,
    ) {
    }

    public function send(User $user, string $key, string $title, string $message, string $targetUrl, string $type): void
    {
        try {
            if (!$this->preferences->allowsInternal($user) || $this->notifications->existsForKey($key)) {
                return;
            }

            $this->persistence->persist(new AccountNotificationEvent($user, $key, $title, $message, $targetUrl, $type));
            $this->persistence->flush();
        } catch (\RuntimeException|\InvalidArgumentException $exception) {
            $this->logger->warning('Internal account notification failed.', [
                'userId' => $user->getId(),
                'key' => $key,
                'type' => $type,
                'exception' => $exception,
            ]);
        }
    }
}
