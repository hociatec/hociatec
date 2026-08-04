<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Order\Application\Factory\InvoiceNumberGenerator;
use PHPUnit\Framework\TestCase;

final class InvoiceNumberGeneratorTest extends TestCase
{
    public function testItGeneratesTheNextInvoiceNumberForTheCurrentYear(): void
    {
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $repository = $this->createMock(OrderRepository::class);
        $repository
            ->expects(self::once())
            ->method('countInvoicedForYear')
            ->with($year)
            ->willReturn(8);

        $number = (new InvoiceNumberGenerator($repository))->generate();

        self::assertSame(sprintf('FAC-%d-0009', $year), $number);
    }
}
