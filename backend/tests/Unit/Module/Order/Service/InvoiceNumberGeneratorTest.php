<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Application\Factory\InvoiceNumberGenerator;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class InvoiceNumberGeneratorTest extends TestCase
{
    public function testItGeneratesTheNextInvoiceNumberForTheCurrentYear(): void
    {
        $year = 2026;
        $repository = $this->createMock(OrderRepository::class);
        $repository
            ->expects(self::once())
            ->method('countInvoicedForYear')
            ->with($year)
            ->willReturn(8);

        $number = (new InvoiceNumberGenerator($repository, new MockClock('2026-08-06')))->generate();

        self::assertSame(sprintf('FAC-%d-0009', $year), $number);
    }
}
