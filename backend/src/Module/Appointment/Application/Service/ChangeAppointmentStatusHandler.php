<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Appointment\Domain\Entity\Appointment;

final readonly class ChangeAppointmentStatusHandler
{
    public function __construct(
        private AppointmentStatusWorkflow $statusWorkflow,
        private DoctrineUnitOfWork $persistence,
    ) {
    }

    public function change(Appointment $appointment, string $targetStatus): void
    {
        $targetStatus = strtolower($targetStatus);

        if (!$this->statusWorkflow->isKnownStatus($targetStatus)) {
            throw new \DomainException('Statut de rendez-vous inconnu.');
        }

        if ($appointment->getStatus() === $targetStatus) {
            return;
        }

        if (!$this->statusWorkflow->canTransition($appointment, $targetStatus)) {
            throw new \DomainException('Transition de statut impossible pour ce rendez-vous.');
        }

        $appointment->setStatus($targetStatus);
        $this->persistence->flush();
    }
}
