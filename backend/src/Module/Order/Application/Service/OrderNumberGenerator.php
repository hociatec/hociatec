<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Module\Order\Application\Port\OrderRepositoryPort;

class OrderNumberGenerator
{
    public function __construct(private readonly OrderRepositoryPort $orders)
    {
    }

    public function generate(): string
    {
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $count = $this->orders->countForYear($year) + 1;

        return sprintf('CMD-%d-%04d', $year, $count);
    }
}
