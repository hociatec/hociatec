<?php

declare(strict_types=1);

namespace App\Module\User\Application\Outbox;

use App\Module\Outbox\Application\OutboxEventHandler;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Application\Workflow\AccountActivationEmailService;
use App\Module\User\Application\Workflow\VerificationTokenHasher;
use App\Module\User\Domain\Entity\User;

final readonly class SendActivationEmailHandler implements OutboxEventHandler
{
    public function __construct(
        private UserRepositoryPort $users,
        private AccountActivationEmailService $activationEmails,
    ) {
    }

    public function supports(OutboxEvent $event): bool
    {
        return 'user.activation_email_requested' === $event->getType();
    }

    public function handle(OutboxEvent $event): void
    {
        $payload = $event->getPayload();
        $userId = $payload['userId'] ?? null;
        $token = $payload['token'] ?? null;
        if (!is_int($userId) || !is_string($token) || '' === $token) {
            throw new \RuntimeException('Activation email outbox payload is invalid.');
        }

        $user = $this->users->find($userId);
        if (!$user instanceof User || $user->isVerified()) {
            return;
        }

        $storedToken = $user->getVerificationToken();
        $expiresAt = $user->getVerificationTokenExpiresAt();
        if (!is_string($storedToken) || '' === $storedToken || null === $expiresAt || $expiresAt < new \DateTimeImmutable()) {
            return;
        }

        if (!hash_equals($storedToken, VerificationTokenHasher::hash($token))) {
            return;
        }

        if ($event->getKey() !== 'user.activation.'.$userId.'.'.$storedToken) {
            return;
        }

        $this->activationEmails->sendActivationEmail($user, $token, $event->getKey());
    }
}
