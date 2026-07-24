<?php

declare(strict_types=1);

namespace App\Module\Appointment\Service;

use App\Module\Appointment\Entity\Appointment;

final class AppointmentFormatter
{
    public function __construct(private readonly AppointmentStatusManager $statusManager)
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
            'status' => $this->statusManager->getLabel($statusCode),
            'statusCode' => $statusCode,
            'isCancelable' => $this->isCancelable($appointment),
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
        return $this->statusManager->canBeCancelled($appointment);
    }
}
