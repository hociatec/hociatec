<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Port;

use App\Module\Quote\Domain\Entity\Quote;

interface QuotePdfRenderer
{
    /** @param array{totalHt: int, totalVat: int, totalTtc: int} $totals */
    public function render(Quote $quote, array $totals): string;
}
