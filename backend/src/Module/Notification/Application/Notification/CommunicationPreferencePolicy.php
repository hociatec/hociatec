<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Notification;

use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\User\Domain\Entity\User;

final readonly class CommunicationPreferencePolicy
{
    public function allowsInternal(User $user): bool
    {
        return in_array(CommunicationPreferences::NOTIFICATION, $user->getCommunicationPreferences(), true);
    }

    public function allowsEmail(User $user): bool
    {
        return in_array(CommunicationPreferences::EMAIL, $user->getCommunicationPreferences(), true);
    }

    public function allowsNewsEmail(User $user): bool
    {
        return in_array(CommunicationPreferences::NEWS_EMAIL, $user->getCommunicationPreferences(), true);
    }
}
