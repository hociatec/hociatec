<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Module\Order\Application\Port\OrderRepositoryPort;

final class InvoiceNumberGenerator
{
    public function __construct(private readonly OrderRepositoryPort $orders)
    {
    }

    public function generate(): string
    {
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $count = $this->orders->countInvoicedForYear($year) + 1;

        return sprintf('FAC-%d-%04d', $year, $count);
    }
}
