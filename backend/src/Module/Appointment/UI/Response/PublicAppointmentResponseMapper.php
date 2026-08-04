<?php

declare(strict_types=1);

namespace App\Module\Appointment\UI\Response;

use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;

final class PublicAppointmentResponseMapper
{
    /**
     * @param list<Prestation> $prestations
     *
     * @return array{items: list<array{id: int|null, name: string, durationMinutes: int, priceCents: int}>}
     */
    public function prestations(array $prestations): array
    {
        return [
            'items' => array_map(static fn (Prestation $prestation): array => [
                'id' => $prestation->getId(),
                'name' => $prestation->getName(),
                'durationMinutes' => $prestation->getDurationMinutes(),
                'priceCents' => $prestation->getPriceCents(),
            ], $prestations),
        ];
    }

    /**
     * @param list<WorkingDayConfiguration> $configurations
     *
     * @return array{days: list<array{dayOfWeek: int, isWorkingDay: bool, startTime: string|null, endTime: string|null, breaks: list<array<string, string>>}>}
     */
    public function workingDays(array $configurations): array
    {
        return [
            'days' => array_map(static fn (WorkingDayConfiguration $configuration): array => [
                'dayOfWeek' => $configuration->getDayOfWeek(),
                'isWorkingDay' => $configuration->isWorkingDay(),
                'startTime' => $configuration->getStartTime()?->format('H:i'),
                'endTime' => $configuration->getEndTime()?->format('H:i'),
                'breaks' => $configuration->getBreaks(),
            ], $configurations),
        ];
    }
}
