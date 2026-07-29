<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Quote\Service;

use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Entity\QuoteItem;
use App\Module\Quote\Service\QuoteCalculator;
use PHPUnit\Framework\TestCase;

final class QuoteCalculatorTest extends TestCase
{
    public function testComputeItemTotalsUsesNormalizedQuantityDiscountAndVatRounding(): void
    {
        $item = (new QuoteItem('Audit', 1999))
            ->setQuantity(3)
            ->setDiscountCents(500)
            ->setVatRateBps(2000);

        $totals = (new QuoteCalculator())->computeItemTotals($item);

        self::assertSame(5497, $totals['ht']);
        self::assertSame(1099, $totals['vat']);
        self::assertSame(6596, $totals['ttc']);
        self::assertGreaterThan($totals['ht'], $totals['ttc']);
    }

    public function testComputeItemTotalsFloorsNegativeLineAmountsToZero(): void
    {
        $item = (new QuoteItem('Support', 1000))
            ->setQuantity(2)
            ->setDiscountCents(2500)
            ->setVatRateBps(550);

        $totals = (new QuoteCalculator())->computeItemTotals($item);

        self::assertSame(['ht' => 0, 'vat' => 0, 'ttc' => 0], $totals);
    }

    public function testComputeTotalsAggregatesItemsThenAppliesGlobalDiscountAndShipping(): void
    {
        $quote = (new Quote('Q-2026-001'))
            ->setGlobalDiscountCents(1000)
            ->setShippingCents(490);

        $first = (new QuoteItem('Audit', 10000))
            ->setQuantity(2)
            ->setDiscountCents(500)
            ->setVatRateBps(2000);
        $second = (new QuoteItem('Produit', 3333))
            ->setQuantity(3)
            ->setDiscountCents(0)
            ->setVatRateBps(550);

        $quote->addItem($first);
        $quote->addItem($second);

        $totals = (new QuoteCalculator())->computeTotals($quote);

        self::assertSame(28499, $totals['totalHt']);
        self::assertSame(4450, $totals['totalVat']);
        self::assertSame(33439, $totals['totalTtc']);
    }

    public function testComputeTotalsDoesNotLetGlobalDiscountCreateNegativeSubtotal(): void
    {
        $quote = (new Quote('Q-2026-002'))
            ->setGlobalDiscountCents(5000)
            ->setShippingCents(250);
        $item = (new QuoteItem('Mini audit', 1200))
            ->setQuantity(1)
            ->setVatRateBps(2000);

        $quote->addItem($item);

        $totals = (new QuoteCalculator())->computeTotals($quote);

        self::assertSame(0, $totals['totalHt']);
        self::assertSame(240, $totals['totalVat']);
        self::assertSame(490, $totals['totalTtc']);
    }
}
