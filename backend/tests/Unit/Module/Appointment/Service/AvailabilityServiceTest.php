<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Appointment\Service;

use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Infrastructure\Repository\AppointmentRepository;
use App\Module\Appointment\Infrastructure\Repository\WorkingDayConfigurationRepository;
use App\Module\Appointment\Application\Workflow\AvailabilityService;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class AvailabilityServiceTest extends TestCase
{
    public function testGetAvailableSlotsSkipsBreaksAppointmentsAndDaysWithoutWorkingHours(): void
    {
        $monday = new WorkingDayConfiguration(
            0,
            true,
            new \DateTimeImmutable('09:00'),
            new \DateTimeImmutable('12:00'),
            [['start' => '10:00', 'end' => '11:00']],
        );
        $tuesday = new WorkingDayConfiguration(1, true, null, null);
        $repositories = $this->repositories([$monday, $tuesday], [
            $this->appointment('2026-08-03T11:00:00+00:00', 60),
        ]);

        $service = new AvailabilityService($repositories['workingDays'], $repositories['appointments']);
        $prestation = new Prestation('Diagnostic', 60, 9000);

        $slots = $service->getAvailableSlots(
            new \DateTimeImmutable('2026-08-03T00:00:00+00:00'),
            new \DateTimeImmutable('2026-08-05T00:00:00+00:00'),
            $prestation,
        );

        self::assertSame([
            [
                'start' => '2026-08-03T09:00:00+00:00',
                'end' => '2026-08-03T10:00:00+00:00',
            ],
        ], $slots);
    }

    public function testIsSlotAvailableChecksExactSlotPresence(): void
    {
        $monday = new WorkingDayConfiguration(
            0,
            true,
            new \DateTimeImmutable('09:00'),
            new \DateTimeImmutable('12:00'),
        );
        $repositories = $this->repositories([$monday], []);

        $service = new AvailabilityService($repositories['workingDays'], $repositories['appointments']);
        $prestation = new Prestation('Diagnostic', 60, 9000);

        self::assertTrue($service->isSlotAvailable(new \DateTimeImmutable('2026-08-03T09:00:00+00:00'), $prestation));
        self::assertFalse($service->isSlotAvailable(new \DateTimeImmutable('2026-08-03T12:00:00+00:00'), $prestation));
    }

    public function testGetAvailableSlotsReturnsEmptyArrayWhenWorkingDayHasMissingHours(): void
    {
        $repositories = $this->repositories([
            new WorkingDayConfiguration(0, true, null, new \DateTimeImmutable('12:00')),
        ], []);

        $service = new AvailabilityService($repositories['workingDays'], $repositories['appointments']);
        $slots = $service->getAvailableSlots(
            new \DateTimeImmutable('2026-08-03T00:00:00+00:00'),
            new \DateTimeImmutable('2026-08-04T00:00:00+00:00'),
            new Prestation('Diagnostic', 60, 9000),
        );

        self::assertSame([], $slots);
    }

    /**
     * @param list<WorkingDayConfiguration> $workingDays
     * @param list<Appointment> $appointments
     *
     * @return array{workingDays: WorkingDayConfigurationRepository, appointments: AppointmentRepository}
     */
    private function repositories(array $workingDays, array $appointments): array
    {
        $workingDayRepository = $this->createMock(WorkingDayConfigurationRepository::class);
        $workingDayRepository->method('findAllOrdered')->willReturn($workingDays);

        $appointmentRepository = $this->createMock(AppointmentRepository::class);
        $appointmentRepository->method('findBetween')->willReturn($appointments);

        return [
            'workingDays' => $workingDayRepository,
            'appointments' => $appointmentRepository,
        ];
    }

    private function appointment(string $startAt, int $durationMinutes): Appointment
    {
        return new Appointment(
            new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female'),
            new Prestation('Diagnostic', $durationMinutes, 9000),
            new \DateTimeImmutable($startAt),
        );
    }
}
