<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Appointment\Entity;

use App\Module\Appointment\Entity\Appointment;
use App\Module\Appointment\Entity\Prestation;
use App\Module\Appointment\Entity\WorkingDayConfiguration;
use App\Module\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class AppointmentEntitiesTest extends TestCase
{
    public function testPrestationMutatorsAndLifecycleHooks(): void
    {
        $prestation = new Prestation('Diagnostic', 45, 9000);
        $initialUpdatedAt = $prestation->getUpdatedAt();

        self::assertNull($prestation->getId());
        self::assertSame('Diagnostic', $prestation->getName());
        self::assertSame(45, $prestation->getDurationMinutes());
        self::assertSame(9000, $prestation->getPriceCents());
        self::assertSame('PT45M', $prestation->getDurationInterval()->format('PT%IM'));

        $prestation
            ->setName('Audit')
            ->setDurationMinutes(60)
            ->setPriceCents(12000);

        self::assertSame('Audit', $prestation->getName());
        self::assertSame(60, $prestation->getDurationMinutes());
        self::assertSame(12000, $prestation->getPriceCents());

        usleep(1000);
        $prestation->onPrePersist();
        self::assertInstanceOf(\DateTimeImmutable::class, $prestation->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $prestation->getUpdatedAt());

        usleep(1000);
        $prestation->onPreUpdate();
        self::assertGreaterThanOrEqual($initialUpdatedAt, $prestation->getUpdatedAt());
    }

    public function testAppointmentMutatorsLabelsAndOverlapChecks(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $prestation = new Prestation('Diagnostic', 45, 9000);
        $startAt = new \DateTimeImmutable('2026-08-10T09:00:00+00:00');
        $appointment = new Appointment($user, $prestation, $startAt);

        self::assertNull($appointment->getId());
        self::assertSame($user, $appointment->getUser());
        self::assertSame($prestation, $appointment->getPrestation());
        self::assertSame($startAt, $appointment->getStartAt());
        self::assertSame('2026-08-10T09:45:00+00:00', $appointment->getEndAt()->format(DATE_ATOM));
        self::assertSame(Appointment::STATUS_CONFIRMED, $appointment->getStatus());
        self::assertSame('Confirmé', $appointment->getStatusLabel());
        self::assertFalse($appointment->isCancelled());
        self::assertTrue($appointment->overlaps(new \DateTimeImmutable('2026-08-10T08:50:00+00:00'), new \DateTimeImmutable('2026-08-10T09:05:00+00:00')));
        self::assertFalse($appointment->overlaps(new \DateTimeImmutable('2026-08-10T10:00:00+00:00'), new \DateTimeImmutable('2026-08-10T11:00:00+00:00')));
        self::assertInstanceOf(\DateTimeImmutable::class, $appointment->getCreatedAt());

        $appointment->cancel();
        self::assertSame(Appointment::STATUS_CANCELLED, $appointment->getStatus());
        self::assertSame('Annulé', $appointment->getStatusLabel());
        self::assertTrue($appointment->isCancelled());

        $appointment->setStatus('custom');
        self::assertSame('custom', $appointment->getStatusLabel());

        $appointment->setStartAt(new \DateTimeImmutable('2026-08-10T11:00:00+00:00'));
        self::assertSame('2026-08-10T11:45:00+00:00', $appointment->getEndAt()->format(DATE_ATOM));

        $appointment->setEndAt(new \DateTimeImmutable('2026-08-10T12:30:00+00:00'));
        self::assertSame('2026-08-10T12:30:00+00:00', $appointment->getEndAt()->format(DATE_ATOM));
    }

    public function testWorkingDayConfigurationRulesAndIntervals(): void
    {
        $configuration = new WorkingDayConfiguration(
            1,
            true,
            new \DateTimeImmutable('09:00'),
            new \DateTimeImmutable('18:00'),
            [['start' => '12:00', 'end' => '13:00']],
        );
        $initialUpdatedAt = $configuration->getUpdatedAt();

        self::assertNull($configuration->getId());
        self::assertSame(1, $configuration->getDayOfWeek());
        self::assertTrue($configuration->isWorkingDay());
        self::assertSame('09:00', $configuration->getStartTime()?->format('H:i'));
        self::assertSame('18:00', $configuration->getEndTime()?->format('H:i'));
        self::assertTrue($configuration->hasWorkingHours());
        self::assertSame([['start' => '12:00', 'end' => '13:00']], $configuration->getBreaks());

        $intervals = $configuration->getBreakIntervalsForDate(new \DateTimeImmutable('2026-08-11'));
        self::assertCount(1, $intervals);
        self::assertSame('2026-08-11T12:00:00+00:00', $intervals[0]['start']->format(DATE_ATOM));
        self::assertSame('2026-08-11T13:00:00+00:00', $intervals[0]['end']->format(DATE_ATOM));

        $configuration
            ->setStartTime(new \DateTimeImmutable('08:30'))
            ->setEndTime(new \DateTimeImmutable('17:30'))
            ->setBreaks([['start' => '10:00', 'end' => '10:15']]);

        self::assertSame('08:30', $configuration->getStartTime()?->format('H:i'));
        self::assertSame('17:30', $configuration->getEndTime()?->format('H:i'));
        self::assertSame([['start' => '10:00', 'end' => '10:15']], $configuration->getBreaks());

        $configuration->setWorkingDay(false);
        self::assertFalse($configuration->isWorkingDay());
        self::assertFalse($configuration->hasWorkingHours());
        self::assertNull($configuration->getStartTime());
        self::assertNull($configuration->getEndTime());
        self::assertSame([], $configuration->getBreaks());
        self::assertSame([], $configuration->getBreakIntervalsForDate(new \DateTimeImmutable('2026-08-11')));

        usleep(1000);
        $configuration->onPrePersist();
        self::assertInstanceOf(\DateTimeImmutable::class, $configuration->getCreatedAt());

        usleep(1000);
        $configuration->onPreUpdate();
        self::assertGreaterThanOrEqual($initialUpdatedAt, $configuration->getUpdatedAt());
    }

    public function testWorkingDayConfigurationSkipsMalformedBreakIntervals(): void
    {
        $configuration = new WorkingDayConfiguration(
            1,
            true,
            new \DateTimeImmutable('09:00'),
            new \DateTimeImmutable('18:00'),
            [['start' => '12:00:30', 'end' => '13:00:30']],
        );

        self::assertSame([], $configuration->getBreakIntervalsForDate(new \DateTimeImmutable('2026-08-11')));
    }

    public function testWorkingDayConfigurationRejectsInvalidValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Day of week must be between 0 (Monday) and 6 (Sunday).');

        new WorkingDayConfiguration(7, true);
    }

    public function testWorkingDayConfigurationRejectsInvalidBreakRange(): void
    {
        $configuration = new WorkingDayConfiguration(1, true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Break end time must be greater than start time.');

        $configuration->setBreaks([['start' => '13:00', 'end' => '13:00']]);
    }

    public function testWorkingDayConfigurationRejectsInvalidBreakTime(): void
    {
        $configuration = new WorkingDayConfiguration(1, true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Time must be within 00:00 and 24:00.');

        $configuration->setBreaks([['start' => '25:00', 'end' => '26:00']]);
    }
}
