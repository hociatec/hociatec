<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Provider;

use App\Module\Appointment\Application\Workflow\AppointmentService;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Notification\Application\Notification\ComputedAccountNotificationProviderInterface;
use App\Module\Notification\Application\Projection\AccountNotificationFormatter;
use App\Module\User\Domain\Entity\User;

final readonly class AppointmentNotificationProvider implements ComputedAccountNotificationProviderInterface
{
    public function __construct(
        private AppointmentService $appointments,
        private AccountNotificationFormatter $formatter,
    ) {
    }

    public function provide(User $user, \DateTimeImmutable $now): array
    {
        $nextAppointment = $this->nextAppointment($user, $now);
        if (null === $nextAppointment) {
            return [];
        }

        return [
            $this->formatter->computedNotification(
                'appointment:'.$nextAppointment->getId().':'.$nextAppointment->getStartAt()->format(DATE_ATOM),
                'Prochain rendez-vous le '.$this->formatter->formatFrenchDateTime($nextAppointment->getStartAt()),
                'Un rendez-vous est planifié le '.$this->formatter->formatFrenchDateTime($nextAppointment->getStartAt()).'.',
                '/appointments/me',
                'appointment_reminder',
                $now,
            ),
        ];
    }

    private function nextAppointment(User $user, \DateTimeImmutable $now): ?Appointment
    {
        $upcoming = $this->appointments->getAppointmentsForUser($user, $now)['upcoming'];
        usort($upcoming, static fn (Appointment $left, Appointment $right): int => $left->getStartAt() <=> $right->getStartAt());

        foreach ($upcoming as $appointment) {
            if (!$appointment->isCancelled() && $appointment->getStartAt() >= $now) {
                return $appointment;
            }
        }

        return null;
    }
}
