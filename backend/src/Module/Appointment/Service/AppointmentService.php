<?php

declare(strict_types=1);

namespace App\Module\Appointment\Service;

use App\Module\Appointment\Entity\Appointment;
use App\Module\Appointment\Entity\Prestation;
use App\Module\Appointment\Exception\InvalidAppointmentSlotException;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\User\Entity\User;
use DateTimeImmutable;
use DomainException;
use Doctrine\ORM\EntityManagerInterface;

final class AppointmentService
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly AvailabilityService $availabilityService,
        private readonly AppointmentStatusManager $statusManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function book(User $user, Prestation $prestation, DateTimeImmutable $startAt): Appointment
    {
        $endAt = $startAt->add($prestation->getDurationInterval());

        if (!$this->availabilityService->isSlotAvailable($startAt, $prestation)) {
            throw new InvalidAppointmentSlotException('Ce creneau n\'est plus disponible.');
        }

        $appointment = new Appointment($user, $prestation, $startAt);

        $this->entityManager->persist($appointment);
        $this->entityManager->flush();

        return $appointment;
    }

    /**
     * @return array{upcoming: list<Appointment>, past: list<Appointment>}
     */
    public function getAppointmentsForUser(User $user, ?string $status = null): array
    {
        $status = $this->normalizeStatusFilter($status);
        $appointments = $this->appointmentRepository->findForUser($user, $status);
        $now = new DateTimeImmutable();

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
        $this->statusManager->changeStatus($appointment, Appointment::STATUS_CANCELLED);
    }

    public function changeStatus(Appointment $appointment, string $status): void
    {
        $this->statusManager->changeStatus($appointment, $status);
    }

    private function normalizeStatusFilter(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $status = strtolower(trim($status));

        if ($status === '' || $status === 'all') {
            return null;
        }

        if (!$this->statusManager->isKnownStatus($status)) {
            throw new DomainException('Statut de rendez-vous inconnu.');
        }

        return $status;
    }
}
