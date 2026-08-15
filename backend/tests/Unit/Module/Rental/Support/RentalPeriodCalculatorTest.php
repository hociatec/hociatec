<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Rental\Support;

use App\Module\Order\Domain\Support\RentalPeriodCalculator;
use PHPUnit\Framework\TestCase;

final class RentalPeriodCalculatorTest extends TestCase
{
    public function testFindMinimumMonthsCoveringEndDateAcceptsExactAlignedDate(): void
    {
        $startDate = new \DateTimeImmutable('2026-08-14');
        $requestedEndDate = new \DateTimeImmutable('2026-10-13');

        self::assertSame(2, RentalPeriodCalculator::findMinimumMonthsCoveringEndDate($startDate, $requestedEndDate));
    }

    public function testFindMinimumMonthsCoveringEndDateRoundsUpToNextCoveredMonth(): void
    {
        $startDate = new \DateTimeImmutable('2026-08-14');
        $requestedEndDate = new \DateTimeImmutable('2026-10-20');

        self::assertSame(3, RentalPeriodCalculator::findMinimumMonthsCoveringEndDate($startDate, $requestedEndDate));
    }

    public function testFindMinimumMonthsCoveringEndDateRejectsDateBeforeStart(): void
    {
        $startDate = new \DateTimeImmutable('2026-08-14');
        $requestedEndDate = new \DateTimeImmutable('2026-08-13');

        self::assertNull(RentalPeriodCalculator::findMinimumMonthsCoveringEndDate($startDate, $requestedEndDate));
    }
}
