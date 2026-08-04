<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Response;

use App\Module\User\Application\Projection\UserProfileFormatter;
use App\Module\User\Domain\Entity\User;

final readonly class AuthProfileResponseMapper
{
    public function __construct(private UserProfileFormatter $profiles)
    {
    }

    /** @return array<string, mixed> */
    public function anonymous(): array
    {
        return ['authenticated' => false];
    }

    /** @return array<string, mixed> */
    public function authenticated(User $user): array
    {
        return ['authenticated' => true]
            + $this->profiles->format($user)
            + ['communicationPreferences' => $user->getCommunicationPreferences()];
    }
}
