<?php

declare(strict_types=1);

namespace App\Module\User\Outbox;

use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use App\Module\User\Service\AccountActivationEmailService;
use App\Shared\Outbox\Entity\OutboxEvent;
use App\Shared\Outbox\OutboxEventHandler;

final readonly class SendActivationEmailHandler implements OutboxEventHandler
{
    public function __construct(
        private UserRepository $users,
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

        $this->activationEmails->sendActivationEmail($user, $token);
    }
}
