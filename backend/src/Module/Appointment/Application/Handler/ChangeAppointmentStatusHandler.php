<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Handler;

use App\Module\Appointment\Application\Workflow\AppointmentStatusWorkflow;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Shared\Application\UnitOfWork;

final readonly class ChangeAppointmentStatusHandler
{
    public function __construct(
        private AppointmentStatusWorkflow $statusWorkflow,
        private UnitOfWork $persistence,
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
        $this->persistence->commit();
    }
}
