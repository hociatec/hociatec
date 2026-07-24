<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Entity\QuoteItem;

class QuoteCalculator
{
    /** @return array{totalHt: int, totalVat: int, totalTtc: int} */
    public function computeTotals(Quote $quote): array
    {
        $totalHt = 0;
        $totalVat = 0;

        foreach ($quote->getItems() as $item) {
            $lineTotals = $this->computeItemTotals($item);
            $totalHt += $lineTotals['ht'];
            $totalVat += $lineTotals['vat'];
        }

        $totalHt = max(0, $totalHt - $quote->getGlobalDiscountCents());
        $totalTtc = $totalHt + $totalVat + $quote->getShippingCents();

        return [
            'totalHt' => $totalHt,
            'totalVat' => $totalVat,
            'totalTtc' => $totalTtc,
        ];
    }

    /**
     * @return array{ht:int, vat:int, ttc:int}
     */
    public function computeItemTotals(QuoteItem $item): array
    {
        $qty = max(1, $item->getQuantity());
        $line = max(0, ($item->getUnitPriceCents() * $qty) - $item->getDiscountCents());
        $vat = (int) round($line * ($item->getVatRateBps() / 10000));

        return [
            'ht' => $line,
            'vat' => $vat,
            'ttc' => $line + $vat,
        ];
    }
}
