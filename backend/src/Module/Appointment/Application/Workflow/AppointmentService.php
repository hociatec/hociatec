<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Workflow;

use App\Module\Appointment\Application\Exception\AppointmentOperationException;
use App\Module\Appointment\Application\Exception\InvalidAppointmentSlotException;
use App\Module\Appointment\Application\Handler\ChangeAppointmentStatusHandler;
use App\Module\Appointment\Application\Port\AppointmentRepositoryPort;
use App\Module\Appointment\Application\Port\WorkingDayConfigurationRepositoryPort;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\User\Domain\Entity\User;
use Psr\Clock\ClockInterface;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final class AppointmentService
{
    public function __construct(
        private readonly AppointmentRepositoryPort $appointmentRepository,
        private readonly WorkingDayConfigurationRepositoryPort $workingDayRepository,
        private readonly AvailabilityService $availabilityService,
        private readonly ChangeAppointmentStatusHandler $changeAppointmentStatus,
        private readonly UnitOfWork $persistence,
        private readonly TransactionManager $transactions,
        private readonly ClockInterface $clock,
    ) {
    }

    public function book(User $user, Prestation $prestation, \DateTimeImmutable $startAt): Appointment
    {
        $now = $this->clock->now();

        if ($startAt < $now) {
            throw new InvalidAppointmentSlotException('Ce créneau n\'est plus disponible.');
        }

        try {
            return $this->transactions->transactional(function () use ($user, $prestation, $startAt): Appointment {
                $dayOfWeek = (int) $startAt->format('N') - 1;
                $this->workingDayRepository->findOneByDayForUpdate($dayOfWeek);

                if (!$this->availabilityService->isSlotAvailable($startAt, $prestation)) {
                    throw new InvalidAppointmentSlotException('Ce creneau n\'est plus disponible.');
                }

                $appointment = new Appointment($user, $prestation, $startAt);

                $this->persistence->persist($appointment);
                $this->persistence->flush();

                return $appointment;
            });
        } catch (InvalidAppointmentSlotException $exception) {
            throw $exception;
        } catch (\RuntimeException $exception) {
            throw AppointmentOperationException::failed('Impossible de reserver ce creneau.', $exception);
        }
    }

    /**
     * @return array{upcoming: list<Appointment>, past: list<Appointment>}
     */
    public function getAppointmentsForUser(User $user, ?\DateTimeImmutable $now = null, int $limit = 20, int $offset = 0): array
    {
        $appointments = $this->appointmentRepository->findForUser($user, limit: $limit, offset: $offset);
        $now ??= $this->clock->now();

        $future = [];
        $past = [];

        foreach ($appointments as $appointment) {
            if ($this->isUpcomingAppointment($appointment, $now)) {
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

    /**
     * @return array{upcoming: list<Appointment>, past: list<Appointment>}
     */
    public function getPaginatedAppointmentsForUser(User $user, ?\DateTimeImmutable $now = null, int $limit = 20, int $offset = 0): array
    {
        $now ??= $this->clock->now();

        return [
            'upcoming' => $this->appointmentRepository->findUpcomingForUser($user, $now, $limit, $offset),
            'past' => $this->appointmentRepository->findPastForUser($user, $now, $limit, $offset),
        ];
    }

    /** @return array{upcoming:int,past:int} */
    public function countAppointmentsForUser(User $user, ?\DateTimeImmutable $now = null): array
    {
        $now ??= $this->clock->now();

        return [
            'upcoming' => $this->appointmentRepository->countUpcomingForUser($user, $now),
            'past' => $this->appointmentRepository->countPastForUser($user, $now),
        ];
    }

    public function cancel(Appointment $appointment): void
    {
        if ($appointment->isCancelled()) {
            throw new \RuntimeException('Ce rendez-vous est déjà annulé.');
        }

        $appointment->cancel();
        try {
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw AppointmentOperationException::failed('Impossible d\'annuler ce rendez-vous.', $exception);
        }
    }

    public function changeStatus(Appointment $appointment, string $targetStatus): void
    {
        $this->changeAppointmentStatus->change($appointment, $targetStatus);
    }

    private function isUpcomingAppointment(Appointment $appointment, \DateTimeImmutable $now): bool
    {
        return $appointment->getStartAt() >= $now && !$appointment->isCancelled();
    }
}
