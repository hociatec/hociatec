<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Workflow;

use App\Module\Appointment\Application\Port\AppointmentRepositoryPort;
use App\Module\Appointment\Application\Projection\AppointmentFormatter;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Security\AppointmentAccessPolicy;
use App\Module\User\Domain\Entity\User;

final readonly class CustomerAppointmentPortalService
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private AppointmentService $service,
        private AppointmentFormatter $formatter,
        private AppointmentAccessPolicy $accessPolicy,
    ) {
    }

    /**
     * @return array{
     *   upcoming:list<array<string,mixed>>,
     *   past:list<array<string,mixed>>,
     *   upcomingTotal:int,
     *   pastTotal:int
     * }
     */
    public function listForUser(User $user, int $limit, int $offset): array
    {
        $appointments = $this->service->getPaginatedAppointmentsForUser($user, limit: $limit, offset: $offset);
        $totals = $this->service->countAppointmentsForUser($user);

        return [
            'upcoming' => array_map(fn (Appointment $appointment): array => $this->formatter->format($appointment), $appointments['upcoming']),
            'past' => array_map(fn (Appointment $appointment): array => $this->formatter->format($appointment), $appointments['past']),
            'upcomingTotal' => $totals['upcoming'],
            'pastTotal' => $totals['past'],
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function changeStatusForUser(User $actor, int $appointmentId, string $targetStatus): ?array
    {
        $appointment = $this->appointments->find($appointmentId);
        if (!$appointment instanceof Appointment) {
            return null;
        }

        if (!$actor->isAdmin() && !$this->accessPolicy->canChangeStatus($actor, $appointment)) {
            throw new \DomainException('Vous n\'êtes pas autorisé à modifier ce rendez-vous.');
        }

        $this->service->changeStatus($appointment, $targetStatus);

        return $this->formatter->format($appointment);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function rescheduleForUser(User $actor, int $appointmentId, \DateTimeImmutable $startAt): ?array
    {
        $appointment = $this->appointments->find($appointmentId);
        if (!$appointment instanceof Appointment) {
            return null;
        }

        if (!$actor->isAdmin() && !$this->accessPolicy->canChangeStatus($actor, $appointment)) {
            throw new \DomainException('Vous n\'êtes pas autorisé à modifier ce rendez-vous.');
        }

        $this->service->reschedule($appointment, $startAt);

        return $this->formatter->format($appointment);
    }
}
