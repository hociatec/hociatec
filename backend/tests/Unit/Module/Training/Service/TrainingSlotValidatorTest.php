<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Training\Service;

use App\Module\Training\Entity\Training;
use App\Module\Training\Entity\TrainingSession;
use App\Module\Training\Service\TrainingSlotValidator;
use PHPUnit\Framework\TestCase;

final class TrainingSlotValidatorTest extends TestCase
{
    public function testValidateAcceptsSlotWithinSessionWindowAndDailyBounds(): void
    {
        $session = $this->createSession();
        $validator = new TrainingSlotValidator();

        $validator->validate(
            $session,
            new \DateTimeImmutable('2026-08-03 09:00:00'),
            new \DateTimeImmutable('2026-08-03 16:30:00'),
        );

        self::assertTrue(true);
    }

    public function testValidateRejectsEndBeforeStart(): void
    {
        $validator = new TrainingSlotValidator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L heure de fin doit etre posterieure a l heure de debut.');

        $validator->validate(
            $this->createSession(),
            new \DateTimeImmutable('2026-08-03 14:00:00'),
            new \DateTimeImmutable('2026-08-03 12:00:00'),
        );
    }

    public function testValidateRejectsSlotOutsideReservationWindow(): void
    {
        $validator = new TrainingSlotValidator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le créneau doit être compris dans la période de réservation.');

        $validator->validate(
            $this->createSession(),
            new \DateTimeImmutable('2026-08-02 09:00:00'),
            new \DateTimeImmutable('2026-08-02 10:00:00'),
        );
    }

    public function testValidateRejectsMultiDaySlots(): void
    {
        $validator = new TrainingSlotValidator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le créneau doit tenir sur une seule journée.');

        $validator->validate(
            $this->createSession(),
            new \DateTimeImmutable('2026-08-03 18:00:00'),
            new \DateTimeImmutable('2026-08-04 09:00:00'),
        );
    }

    public function testValidateRejectsWeekendWhenSessionExcludesWeekends(): void
    {
        $session = $this->createSession();
        $session->setIncludeWeekends(false);
        $validator = new TrainingSlotValidator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cette formation n’est pas réservable le week-end.');

        $validator->validate(
            $session,
            new \DateTimeImmutable('2026-08-08 10:00:00'),
            new \DateTimeImmutable('2026-08-08 12:00:00'),
        );
    }

    public function testValidateRejectsHoursOutsideDailyBounds(): void
    {
        $validator = new TrainingSlotValidator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le créneau doit être compris entre 09:00 et 17:00.');

        $validator->validate(
            $this->createSession(),
            new \DateTimeImmutable('2026-08-04 08:59:00'),
            new \DateTimeImmutable('2026-08-04 10:00:00'),
        );
    }

    private function createSession(): TrainingSession
    {
        $training = new Training('Formation cybersécurité', 'formation-cybersecurite', 420, 90000);
        $session = new TrainingSession(
            $training,
            'remote',
            new \DateTimeImmutable('2026-08-03 09:00:00'),
            new \DateTimeImmutable('2026-08-09 17:00:00'),
            12,
        );

        $session->setDailyStartTime(new \DateTimeImmutable('09:00'));
        $session->setDailyEndTime(new \DateTimeImmutable('17:00'));

        return $session;
    }
}
