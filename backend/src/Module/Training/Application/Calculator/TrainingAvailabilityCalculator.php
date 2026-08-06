<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Calculator;

use App\Module\Training\Domain\Entity\TrainingSession;

final class TrainingAvailabilityCalculator
{
    public function remainingSeats(TrainingSession $session, int $enrolledCount): int
    {
        return max(0, $session->getCapacity() - $enrolledCount);
    }
}
