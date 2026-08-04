<?php

declare(strict_types=1);

namespace App\Module\Appointment\Security;

use App\Module\Appointment\Entity\Appointment;
use App\Module\User\Entity\User;

final readonly class AppointmentAccessPolicy
{
    public function canChangeStatus(User $user, Appointment $appointment): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || $appointment->getUser()->getId() === $user->getId();
    }
}
