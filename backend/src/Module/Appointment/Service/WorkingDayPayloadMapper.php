<?php

declare(strict_types=1);

namespace App\Module\Appointment\Service;

use App\Module\Appointment\DTO\WorkingDayData;

final class WorkingDayPayloadMapper
{
    /**
     * @param array<mixed> $payload
     *
     * @return list<WorkingDayData>
     */
    public function map(array $payload): array
    {
        $days = $payload['days'] ?? null;
        if (!is_array($days) || [] === $days) {
            throw new \InvalidArgumentException('La configuration doit contenir un tableau "days".');
        }

        $result = [];
        $seenDays = [];
        foreach ($days as $day) {
            if (!is_array($day) || !isset($day['dayOfWeek'], $day['isWorkingDay'])) {
                throw new \InvalidArgumentException('Chaque jour doit définir dayOfWeek et isWorkingDay.');
            }

            $startTime = $day['startTime'] ?? null;
            $endTime = $day['endTime'] ?? null;

            foreach (['startTime', 'endTime'] as $field) {
                $value = 'startTime' === $field ? $startTime : $endTime;
                if (null !== $value && !is_string($value)) {
                    throw new \InvalidArgumentException(sprintf('Le champ %s doit être une heure valide.', $field));
                }
            }

            $dayOfWeek = $day['dayOfWeek'];
            if (!is_int($dayOfWeek) && !(is_string($dayOfWeek) && ctype_digit($dayOfWeek))) {
                throw new \InvalidArgumentException('Le jour doit être un entier compris entre 0 et 6.');
            }
            $dayOfWeek = (int) $dayOfWeek;
            if ($dayOfWeek < 0 || $dayOfWeek > 6 || isset($seenDays[$dayOfWeek])) {
                throw new \InvalidArgumentException('Chaque jour doit être unique et compris entre 0 et 6.');
            }
            $seenDays[$dayOfWeek] = true;

            if (!is_bool($day['isWorkingDay'])) {
                throw new \InvalidArgumentException('isWorkingDay doit être un booléen.');
            }

            $result[] = new WorkingDayData(
                $dayOfWeek,
                $day['isWorkingDay'],
                $startTime,
                $endTime,
                $this->mapBreaks($day['breaks'] ?? []),
            );
        }

        return $result;
    }

    /**
     * @return list<array{start: string, end: string}>
     */
    private function mapBreaks(mixed $breaks): array
    {
        if (!is_array($breaks)) {
            throw new \InvalidArgumentException('Les pauses doivent être un tableau.');
        }

        $result = [];
        foreach ($breaks as $break) {
            if (!is_array($break) || !is_string($break['start'] ?? null) || !is_string($break['end'] ?? null)) {
                throw new \InvalidArgumentException('Chaque pause doit définir une heure de début et de fin.');
            }
            $result[] = ['start' => $break['start'], 'end' => $break['end']];
        }

        return $result;
    }
}
