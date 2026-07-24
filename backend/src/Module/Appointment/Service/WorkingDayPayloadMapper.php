<?php

declare(strict_types=1);

namespace App\Module\Appointment\Service;

final class WorkingDayPayloadMapper
{
    /**
     * @param array<mixed> $payload
     *
     * @return list<array{
     *     dayOfWeek: int,
     *     isWorkingDay: bool,
     *     startTime?: string|null,
     *     endTime?: string|null,
     *     breaks?: list<array{start: string, end: string}>
     * }>
     */
    public function map(array $payload): array
    {
        $days = $payload['days'] ?? null;
        if (!is_array($days) || [] === $days) {
            throw new \InvalidArgumentException('La configuration doit contenir un tableau "days".');
        }

        $result = [];
        foreach ($days as $day) {
            if (!is_array($day) || !isset($day['dayOfWeek'], $day['isWorkingDay'])) {
                throw new \InvalidArgumentException('Chaque jour doit définir dayOfWeek et isWorkingDay.');
            }

            $item = [
                'dayOfWeek' => (int) $day['dayOfWeek'],
                'isWorkingDay' => (bool) $day['isWorkingDay'],
            ];

            foreach (['startTime', 'endTime'] as $field) {
                $value = $day[$field] ?? null;
                if (null !== $value && !is_string($value)) {
                    throw new \InvalidArgumentException(sprintf('Le champ %s doit être une heure valide.', $field));
                }
                $item[$field] = $value;
            }

            $item['breaks'] = $this->mapBreaks($day['breaks'] ?? []);
            $result[] = $item;
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
