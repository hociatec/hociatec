<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Quote\Repository\QuoteRepository;

class QuoteNumberGenerator
{
    public function __construct(private readonly QuoteRepository $quoteRepository)
    {
    }

    public function generate(): string
    {
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $count = $this->quoteRepository->countForYear($year) + 1;

        return sprintf('DEV-%d-%04d', $year, $count);
    }
}
