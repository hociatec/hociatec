<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Outbox;

use App\Module\Auth\Application\Workflow\PasswordResetEmailService;
use App\Module\Outbox\Application\OutboxEventHandler;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;

final readonly class SendPasswordResetEmailHandler implements OutboxEventHandler
{
    public function __construct(
        private UserRepositoryPort $users,
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
        if (!$user instanceof User) {
            return;
        }

        $storedToken = $user->getPasswordResetToken();
        $expiresAt = $user->getPasswordResetTokenExpiresAt();
        if (!is_string($storedToken) || '' === $storedToken || null === $expiresAt || $expiresAt < new \DateTimeImmutable()) {
            return;
        }
        if (!hash_equals($storedToken, \App\Module\Auth\Application\Workflow\PasswordResetTokenHasher::hash($token))) {
            return;
        }
        if ($event->getKey() !== 'auth.password_reset.'.hash('sha256', $token)) {
            return;
        }

        $this->emails->send($user, $token, $event->getKey());
    }
}
