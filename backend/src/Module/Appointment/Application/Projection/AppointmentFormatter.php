<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Projection;

use App\Module\Appointment\Application\Workflow\AppointmentStatusWorkflow;
use App\Module\Appointment\Domain\Entity\Appointment;

final class AppointmentFormatter
{
    public function __construct(private readonly AppointmentStatusWorkflow $statusWorkflow)
    {
    }

    /** @return array<string, mixed> */
    public function format(Appointment $appointment): array
    {
        $statusCode = $appointment->getStatus();

        return [
            'id' => $appointment->getId(),
            'startAt' => $appointment->getStartAt()->format(DATE_ATOM),
            'endAt' => $appointment->getEndAt()->format(DATE_ATOM),
            'status' => $this->statusWorkflow->label($statusCode),
            'statusCode' => $statusCode,
            'isCancelable' => $this->isCancelable($appointment),
            'isReschedulable' => $this->isReschedulable($appointment),
            'prestation' => [
                'id' => $appointment->getPrestation()->getId(),
                'name' => $appointment->getPrestation()->getName(),
                'durationMinutes' => $appointment->getPrestation()->getDurationMinutes(),
                'priceCents' => $appointment->getPrestation()->getPriceCents(),
            ],
        ];
    }

    private function isCancelable(Appointment $appointment): bool
    {
        return $this->statusWorkflow->canBeCancelled($appointment);
    }

    private function isReschedulable(Appointment $appointment): bool
    {
        return !$appointment->isCancelled() && $appointment->getStartAt() > new \DateTimeImmutable();
    }
}
