<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Service;

use App\Module\Notification\Domain\Exception\NotificationOperationException;
use App\Module\User\Application\Service\UserPersistence;
use App\Module\User\Domain\Entity\User;

final readonly class CommunicationPreferenceUpdater
{
    public function __construct(private UserPersistence $persistence)
    {
    }

    /** @param array<mixed> $rawPreferences */
    public function update(User $user, array $rawPreferences): void
    {
        $preferences = CommunicationPreferences::normalize($rawPreferences);
        if ([] === $preferences) {
            throw new \InvalidArgumentException('Sélectionnez au moins un moyen de communication.');
        }

        $user->setCommunicationPreferences($preferences);

        try {
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw NotificationOperationException::failed('Impossible d’enregistrer les préférences de communication.', $exception);
        }
    }
}
