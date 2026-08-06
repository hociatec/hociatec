<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Application\Factory\OrderNumberGenerator;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class OrderNumberGeneratorTest extends TestCase
{
    public function testItGeneratesTheNextOrderNumberForTheCurrentYear(): void
    {
        $year = 2026;
        $repository = $this->createMock(OrderRepository::class);
        $repository
            ->expects(self::once())
            ->method('countForYear')
            ->with($year)
            ->willReturn(41);

        $number = (new OrderNumberGenerator($repository, new MockClock('2026-08-06')))->generate();

        self::assertSame(sprintf('CMD-%d-0042', $year), $number);
    }
}
