<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Outbox;

use App\Module\Auth\Application\Service\PasswordResetEmailService;
use App\Module\Outbox\Application\OutboxEventHandler;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;

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
        if (!is_string($email) || '' === trim($email)) {
            throw new \RuntimeException('Password reset email outbox payload is invalid.');
        }

        $user = $this->users->findOneByEmailInsensitive($email);
        if (!$user instanceof User) {
            return;
        }

        $token = $user->getPasswordResetToken();
        $expiresAt = $user->getPasswordResetTokenExpiresAt();
        if (!is_string($token) || '' === $token || null === $expiresAt || $expiresAt < new \DateTimeImmutable()) {
            return;
        }
        if ($event->getKey() !== 'auth.password_reset.'.hash('sha256', $token)) {
            return;
        }

        $this->emails->send($user, $token);
    }
}
