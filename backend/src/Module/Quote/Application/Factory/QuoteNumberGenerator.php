<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Factory;

use App\Module\Quote\Application\Port\QuoteRepositoryPort;

class QuoteNumberGenerator
{
    public function __construct(private readonly QuoteRepositoryPort $quoteRepository)
    {
    }

    public function generate(): string
    {
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $count = $this->quoteRepository->countForYear($year) + 1;

        return sprintf('DEV-%d-%04d', $year, $count);
    }
}
