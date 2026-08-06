<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Workflow;

use App\Module\Appointment\Application\Port\AppointmentRepositoryPort;
use App\Module\Appointment\Application\Port\WorkingDayConfigurationRepositoryPort;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;

final class AvailabilityService
{
    public function __construct(
        private readonly WorkingDayConfigurationRepositoryPort $workingDayRepository,
        private readonly AppointmentRepositoryPort $appointmentRepository,
    ) {
    }

    /**
     * @return list<array{start: string, end: string}>
     */
    public function getAvailableSlots(\DateTimeImmutable $rangeStart, \DateTimeImmutable $rangeEnd, Prestation $prestation): array
    {
        $workingDays = $this->workingDayRepository->findAllOrdered();
        $workingDayByIndex = [];

        foreach ($workingDays as $workingDay) {
            $workingDayByIndex[$workingDay->getDayOfWeek()] = $workingDay;
        }

        $appointments = $this->appointmentRepository->findBetween($rangeStart, $rangeEnd);

        $slots = [];
        $cursor = $rangeStart->setTime(0, 0);
        $oneDay = new \DateInterval('P1D');

        while ($cursor < $rangeEnd) {
            $dayOfWeek = (int) $cursor->format('N') - 1; // 0 (Monday) -> 6 (Sunday)
            $configuration = $workingDayByIndex[$dayOfWeek] ?? null;

            if (null !== $configuration && $configuration->hasWorkingHours()) {
                foreach ($this->computeSlotsForDay($cursor, $configuration, $appointments, $prestation) as $slot) {
                    $slots[] = $slot;
                }
            }

            $cursor = $cursor->add($oneDay);
        }

        return $slots;
    }

    public function isSlotAvailable(\DateTimeImmutable $startAt, Prestation $prestation): bool
    {
        $endAt = $startAt->add($prestation->getDurationInterval());

        $slots = $this->getAvailableSlots(
            $startAt->sub(new \DateInterval('P1D')),
            $endAt->add(new \DateInterval('P1D')),
            $prestation
        );

        foreach ($slots as $slot) {
            if ($slot['start'] === $startAt->format(DATE_ATOM) && $slot['end'] === $endAt->format(DATE_ATOM)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<\App\Module\Appointment\Domain\Entity\Appointment> $existingAppointments
     *
     * @return list<array{start: string, end: string}>
     */
    private function computeSlotsForDay(
        \DateTimeImmutable $date,
        WorkingDayConfiguration $configuration,
        array $existingAppointments,
        Prestation $prestation,
    ): array {
        $startTime = $this->combineDateWithTime($date, $configuration->getStartTime());
        $endTime = $this->combineDateWithTime($date, $configuration->getEndTime());

        if (null === $startTime || null === $endTime) {
            return [];
        }

        $breaks = $configuration->getBreakIntervalsForDate($date);
        $duration = $prestation->getDurationInterval();

        $period = new \DatePeriod($startTime, $duration, $endTime);
        $availableSlots = [];

        foreach ($period as $slotStart) {
            /** @var \DateTimeImmutable $slotStart */
            $slotEnd = $slotStart->add($duration);

            if ($slotEnd > $endTime) {
                break;
            }

            if ($this->overlapsBreaks($slotStart, $slotEnd, $breaks)) {
                continue;
            }

            if ($this->overlapsAppointments($slotStart, $slotEnd, $existingAppointments)) {
                continue;
            }

            $availableSlots[] = [
                'start' => $slotStart->format(DATE_ATOM),
                'end' => $slotEnd->format(DATE_ATOM),
            ];
        }

        return $availableSlots;
    }

    /**
     * @param list<array{start: \DateTimeImmutable, end: \DateTimeImmutable}> $breaks
     */
    private function overlapsBreaks(\DateTimeImmutable $slotStart, \DateTimeImmutable $slotEnd, array $breaks): bool
    {
        foreach ($breaks as $break) {
            if ($slotStart < $break['end'] && $slotEnd > $break['start']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<\App\Module\Appointment\Domain\Entity\Appointment> $appointments
     */
    private function overlapsAppointments(\DateTimeImmutable $slotStart, \DateTimeImmutable $slotEnd, array $appointments): bool
    {
        foreach ($appointments as $appointment) {
            if ($appointment->overlaps($slotStart, $slotEnd)) {
                return true;
            }
        }

        return false;
    }

    private function combineDateWithTime(\DateTimeImmutable $date, ?\DateTimeImmutable $time): ?\DateTimeImmutable
    {
        if (null === $time) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            sprintf('%s %s', $date->format('Y-m-d'), $time->format('H:i:s'))
        ) ?: null;
    }
}
