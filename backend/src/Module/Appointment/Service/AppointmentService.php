<?php

declare(strict_types=1);

namespace App\Module\Appointment\Service;

use App\Module\Appointment\Entity\Appointment;
use App\Module\Appointment\Entity\Prestation;
use App\Module\Appointment\Exception\InvalidAppointmentSlotException;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\User\Entity\User;
use App\Shared\Persistence\DoctrinePersistence;

final class AppointmentService
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly AvailabilityService $availabilityService,
        private readonly AppointmentStatusManager $appointmentStatusManager,
        private readonly DoctrinePersistence $persistence,
    ) {
    }

    public function book(User $user, Prestation $prestation, \DateTimeImmutable $startAt): Appointment
    {
        $endAt = $startAt->add($prestation->getDurationInterval());

        if (!$this->availabilityService->isSlotAvailable($startAt, $prestation)) {
            throw new InvalidAppointmentSlotException('Ce creneau n\'est plus disponible.');
        }

        $appointment = new Appointment($user, $prestation, $startAt);

        $this->persistence->persist($appointment);
        $this->persistence->flush();

        return $appointment;
    }

    /**
     * @return array{upcoming: list<Appointment>, past: list<Appointment>}
     */
    public function getAppointmentsForUser(User $user): array
    {
        $appointments = $this->appointmentRepository->findForUser($user);
        $now = new \DateTimeImmutable();

        $future = [];
        $past = [];

        foreach ($appointments as $appointment) {
            if ($appointment->getStartAt() >= $now) {
                $future[] = $appointment;
            } else {
                $past[] = $appointment;
            }
        }

        return [
            'upcoming' => $future,
            'past' => $past,
        ];
    }

    public function cancel(Appointment $appointment): void
    {
        if ($appointment->isCancelled()) {
            throw new \RuntimeException('Ce rendez-vous est déjà annulé.');
        }

        $appointment->cancel();
        $this->persistence->flush();
    }

    public function changeStatus(Appointment $appointment, string $targetStatus): void
    {
        $this->appointmentStatusManager->changeStatus($appointment, $targetStatus);
    }
}
