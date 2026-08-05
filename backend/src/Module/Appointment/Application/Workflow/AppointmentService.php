<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Workflow;

use App\Module\Appointment\Application\Exception\AppointmentOperationException;
use App\Module\Appointment\Application\Exception\InvalidAppointmentSlotException;
use App\Module\Appointment\Application\Handler\ChangeAppointmentStatusHandler;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Application\Port\AppointmentRepositoryPort;
use App\Module\Appointment\Application\Port\WorkingDayConfigurationRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final class AppointmentService
{
    public function __construct(
        private readonly AppointmentRepositoryPort $appointmentRepository,
        private readonly WorkingDayConfigurationRepositoryPort $workingDayRepository,
        private readonly AvailabilityService $availabilityService,
        private readonly ChangeAppointmentStatusHandler $changeAppointmentStatus,
        private readonly DoctrineUnitOfWork $persistence,
        private readonly TransactionManager $transactions,
    ) {
    }

    public function book(User $user, Prestation $prestation, \DateTimeImmutable $startAt): Appointment
    {
        try {
            return $this->transactions->transactional(function () use ($user, $prestation, $startAt): Appointment {
                $dayOfWeek = (int) $startAt->format('N') - 1;
                $this->workingDayRepository->findOneByDayForUpdate($dayOfWeek);

                if (!$this->availabilityService->isSlotAvailable($startAt, $prestation)) {
                    throw new InvalidAppointmentSlotException('Ce creneau n\'est plus disponible.');
                }

                $appointment = new Appointment($user, $prestation, $startAt);

                $this->persistence->persist($appointment);
                $this->persistence->commit();

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
    public function getAppointmentsForUser(User $user, ?\DateTimeImmutable $now = null): array
    {
        $appointments = $this->appointmentRepository->findForUser($user);
        $now ??= new \DateTimeImmutable();

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
        try {
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw AppointmentOperationException::failed('Impossible d\'annuler ce rendez-vous.', $exception);
        }
    }

    public function changeStatus(Appointment $appointment, string $targetStatus): void
    {
        $this->changeAppointmentStatus->change($appointment, $targetStatus);
    }
}
