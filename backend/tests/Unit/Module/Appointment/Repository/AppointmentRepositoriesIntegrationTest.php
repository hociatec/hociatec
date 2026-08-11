<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Appointment\Repository;

use App\Module\Appointment\Domain\Entity\Appointment;
use App\Tests\Unit\Module\Appointment\AppointmentIntegrationTestCase;

final class AppointmentRepositoriesIntegrationTest extends AppointmentIntegrationTestCase
{
    public function testRepositoriesQueryWorkingDaysPrestationsAndAppointments(): void
    {
        [$user, $prestation] = $this->seedSchedule();
        $old = new Appointment($user, $prestation, new \DateTimeImmutable('2026-08-03T09:00:00+00:00'));
        $future = new Appointment($user, $prestation, new \DateTimeImmutable('2026-08-17T09:00:00+00:00'));
        $cancelled = (new Appointment($user, $prestation, new \DateTimeImmutable('2026-08-17T10:00:00+00:00')))->cancel();
        foreach ([$old, $future, $cancelled] as $appointment) {
            $this->entityManager()->persist($appointment);
        }
        $this->entityManager()->flush();

        self::assertSame('Diagnostic', $this->prestations()->findAllOrderedByName()[0]->getName());
        self::assertSame(0, $this->workingDays()->findOneByDay(0)?->getDayOfWeek());
        $this->entityManager()->beginTransaction();
        try {
            self::assertSame(0, $this->workingDays()->findOneByDayForUpdate(0)?->getDayOfWeek());
        } finally {
            $this->entityManager()->commit();
        }
        self::assertCount(7, $this->workingDays()->findAllOrdered());
        self::assertSame([$future], $this->appointments()->findBetween(
            new \DateTimeImmutable('2026-08-17T08:00:00+00:00'),
            new \DateTimeImmutable('2026-08-17T11:00:00+00:00'),
        ));
        self::assertSame([$cancelled, $future, $old], $this->appointments()->findForUser($user));
        self::assertSame([$cancelled], $this->appointments()->findForUser($user, Appointment::STATUS_CANCELLED));
        self::assertSame([$future], $this->appointments()->findUpcomingForUser($user, new \DateTimeImmutable('2026-08-17T08:30:00+00:00')));
        self::assertSame([$cancelled, $old], $this->appointments()->findPastForUser($user, new \DateTimeImmutable('2026-08-17T08:30:00+00:00')));
        self::assertSame(1, $this->appointments()->countUpcomingForUser($user, new \DateTimeImmutable('2026-08-17T08:30:00+00:00')));
        self::assertSame(2, $this->appointments()->countPastForUser($user, new \DateTimeImmutable('2026-08-17T08:30:00+00:00')));

        $this->prestations()->remove($prestation);
        $this->entityManager()->flush();
        self::assertSame([], $this->prestations()->findAllOrderedByName());
    }
}
