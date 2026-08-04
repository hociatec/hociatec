<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Order\Application\Factory\OrderNumberGenerator;
use PHPUnit\Framework\TestCase;

final class OrderNumberGeneratorTest extends TestCase
{
    public function testItGeneratesTheNextOrderNumberForTheCurrentYear(): void
    {
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $repository = $this->createMock(OrderRepository::class);
        $repository
            ->expects(self::once())
            ->method('countForYear')
            ->with($year)
            ->willReturn(41);

        $number = (new OrderNumberGenerator($repository))->generate();

        self::assertSame(sprintf('CMD-%d-0042', $year), $number);
    }
}
