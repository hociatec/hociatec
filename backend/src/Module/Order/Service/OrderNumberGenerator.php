<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Repository\OrderRepository;

class OrderNumberGenerator
{
    public function __construct(private readonly OrderRepository $orders)
    {
    }

    public function generate(): string
    {
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $count = $this->orders->countForYear($year) + 1;
        return sprintf('CMD-%d-%04d', $year, $count);
    }
}

