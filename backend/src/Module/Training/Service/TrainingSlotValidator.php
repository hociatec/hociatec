<?php

declare(strict_types=1);

namespace App\Module\Training\Service;

use App\Module\Training\Entity\TrainingSession;

final class TrainingSlotValidator
{
    public function validate(TrainingSession $session, \DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt): void
    {
        if ($startsAt < $session->getStartsAt() || $endsAt > $session->getEndsAt()) {
            throw new \InvalidArgumentException('Le créneau doit être compris dans la période de réservation.');
        }
        if ($startsAt->format('Y-m-d') !== $endsAt->format('Y-m-d')) {
            throw new \InvalidArgumentException('Le créneau doit tenir sur une seule journée.');
        }
        if (!$session->includesWeekends() && in_array((int) $startsAt->format('N'), [6, 7], true)) {
            throw new \InvalidArgumentException('Cette formation n’est pas réservable le week-end.');
        }

        $dailyStart = $session->getDailyStartTime()->format('H:i');
        $dailyEnd = $session->getDailyEndTime()->format('H:i');
        if ($startsAt->format('H:i') < $dailyStart || $endsAt->format('H:i') > $dailyEnd) {
            throw new \InvalidArgumentException(sprintf('Le créneau doit être compris entre %s et %s.', $dailyStart, $dailyEnd));
        }
    }
}
