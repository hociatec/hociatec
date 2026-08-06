<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Factory;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use Psr\Clock\ClockInterface;

class OrderNumberGenerator
{
    public function __construct(private readonly OrderRepositoryPort $orders, private readonly ClockInterface $clock)
    {
    }

    public function generate(): string
    {
        $year = (int) $this->clock->now()->format('Y');
        $count = $this->orders->countForYear($year) + 1;

        return sprintf('CMD-%d-%04d', $year, $count);
    }
}
