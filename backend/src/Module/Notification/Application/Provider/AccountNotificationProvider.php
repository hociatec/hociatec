<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Provider;

use App\Module\Notification\Application\Notification\ComputedAccountNotificationProviderInterface;
use App\Module\Notification\Application\Port\AccountNotificationEventRepositoryPort;
use App\Module\Notification\Application\Projection\AccountNotificationFormatter;
use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\User\Domain\Entity\User;
use Psr\Clock\ClockInterface;

final readonly class AccountNotificationProvider
{
    /**
     * @param iterable<ComputedAccountNotificationProviderInterface> $computedProviders
     */
    public function __construct(
        private AccountNotificationEventRepositoryPort $events,
        private AccountNotificationFormatter $formatter,
        private iterable $computedProviders,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<array{key: string, label: string, message: string, to: string, type: string, createdAt: string}>
     */
    public function provideForUser(User $user, int $limit = 30, int $offset = 0): array
    {
        if (!in_array(CommunicationPreferences::NOTIFICATION, $user->getCommunicationPreferences(), true)) {
            return [];
        }

        $computed = 0 === $offset ? $this->buildComputedNotifications($user) : [];

        return [
            ...$computed,
            ...array_map($this->formatEvent(...), $this->events->findRecentForUser($user, max(1, $limit - count($computed)), $offset)),
        ];
    }

    public function countForUser(User $user): int
    {
        if (!in_array(CommunicationPreferences::NOTIFICATION, $user->getCommunicationPreferences(), true)) {
            return 0;
        }

        return $this->events->countForUser($user) + count($this->buildComputedNotifications($user));
    }

    /**
     * @return array{key: string, label: string, message: string, to: string, type: string, createdAt: string}
     */
    private function formatEvent(AccountNotificationEvent $notification): array
    {
        return [
            'key' => $notification->getKey(),
            'label' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'to' => $this->formatter->safeInternalTarget($notification->getTargetUrl()),
            'type' => $notification->getType(),
            'createdAt' => $notification->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @return list<array{key: string, label: string, message: string, to: string, type: string, createdAt: string}>
     */
    private function buildComputedNotifications(User $user): array
    {
        $now = $this->clock->now();
        $notifications = [];

        foreach ($this->computedProviders as $provider) {
            $notifications = [
                ...$notifications,
                ...$provider->provide($user, $now),
            ];
        }

        return $notifications;
    }
}
