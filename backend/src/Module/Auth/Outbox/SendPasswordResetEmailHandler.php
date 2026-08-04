<?php

declare(strict_types=1);

namespace App\Module\Auth\Outbox;

use App\Module\Auth\Service\PasswordResetEmailService;
use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use App\Shared\Outbox\Entity\OutboxEvent;
use App\Shared\Outbox\OutboxEventHandler;

final readonly class SendPasswordResetEmailHandler implements OutboxEventHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordResetEmailService $emails,
    ) {
    }

    public function supports(OutboxEvent $event): bool
    {
        return 'auth.password_reset_email_requested' === $event->getType();
    }

    public function handle(OutboxEvent $event): void
    {
        $payload = $event->getPayload();
        $email = $payload['email'] ?? null;
        $token = $payload['token'] ?? null;
        if (!is_string($email) || '' === trim($email) || !is_string($token) || '' === $token) {
            throw new \RuntimeException('Password reset email outbox payload is invalid.');
        }

        $user = $this->users->findOneByEmailInsensitive($email);
        if (!$user instanceof User || $user->getPasswordResetToken() !== $token) {
            return;
        }

        $expiresAt = $user->getPasswordResetTokenExpiresAt();
        if (null === $expiresAt || $expiresAt < new \DateTimeImmutable()) {
            return;
        }

        $this->emails->send($user, $token);
    }
}
