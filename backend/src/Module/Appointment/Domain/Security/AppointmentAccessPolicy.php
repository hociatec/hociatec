<?php

declare(strict_types=1);

namespace App\Module\Appointment\Domain\Security;

use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\User\Domain\Entity\User;

final readonly class AppointmentAccessPolicy
{
    public function canChangeStatus(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin()
            || $appointment->getUser()->getId() === $user->getId();
    }
}
