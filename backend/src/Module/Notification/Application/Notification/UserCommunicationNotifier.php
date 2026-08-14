<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Notification;

use App\Module\Notification\Application\Port\AccountNotificationEventRepositoryPort;
use App\Module\User\Domain\Entity\User;

final readonly class UserCommunicationNotifier
{
    public function __construct(
        private AccountNotificationEventRepositoryPort $notifications,
        private CommunicationPreferencePolicy $preferences,
        private InternalAccountNotificationSender $internalSender,
        private UserCommunicationEmailSender $emailSender,
    ) {
    }

    public function notify(User $user, string $key, string $title, string $message, string $targetUrl, string $type): void
    {
        if ($this->notifications->existsForUserAndKey($user, $key)) {
            return;
        }

        $this->notifyInternal($user, $key, $title, $message, $targetUrl, $type);

        if ($this->shouldSendEmail($user)) {
            $this->emailSender->dispatch($user, $title, $message, $targetUrl, $type);
        }
    }

    public function notifyInternal(User $user, string $key, string $title, string $message, string $targetUrl, string $type): void
    {
        $this->internalSender->send($user, $key, $title, $message, $targetUrl, $type);
    }

    public function shouldSendEmail(User $user): bool
    {
        return $this->preferences->allowsEmail($user);
    }

    public function shouldSendNewsEmail(User $user): bool
    {
        return $this->preferences->allowsNewsEmail($user);
    }

    public function sendEmailNow(User $user, string $title, string $message, string $targetUrl, string $type): void
    {
        $this->emailSender->sendNow($user, $title, $message, $targetUrl, $type);
    }
}
