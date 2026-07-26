<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\Service;

use App\Module\TradeIn\DTO\TradeInInput;
use App\Module\TradeIn\Service\TradeInEstimator;
use PHPUnit\Framework\TestCase;

final class TradeInEstimatorTest extends TestCase
{
    public function testItUsesThePurchasePriceAndReturnsABoundedRange(): void
    {
        $input = $this->input(purchasePriceCents: 100000, purchaseYear: (int) date('Y'), conditionGrade: 'bon', hasAccessories: false, hasProofOfPurchase: false);

        $estimate = (new TradeInEstimator())->estimate($input);

        self::assertSame(100000, $estimate['baseCents']);
        self::assertSame(50575, $estimate['minCents']);
        self::assertSame(68425, $estimate['maxCents']);
        self::assertGreaterThanOrEqual(0, $estimate['minCents']);
        self::assertGreaterThan($estimate['minCents'], $estimate['maxCents']);
    }

    public function testItAppliesConditionAndFunctionalityAdjustments(): void
    {
        $input = $this->input(purchasePriceCents: 100000, purchaseYear: (int) date('Y') - 2, conditionGrade: 'hors_service', functional: false, hasAccessories: false, hasProofOfPurchase: false);

        $estimate = (new TradeInEstimator())->estimate($input);

        self::assertSame(4781, $estimate['minCents']);
        self::assertSame(6469, $estimate['maxCents']);
    }

    private function input(int $purchasePriceCents, int $purchaseYear, string $conditionGrade, bool $functional = true, bool $hasAccessories = true, bool $hasProofOfPurchase = true): TradeInInput
    {
        return new TradeInInput('Ada', 'Lovelace', 'ada@example.com', '0102030405', 'ordinateur', 'Ordinateur de test', $purchasePriceCents, $purchaseYear, null, null, null, $conditionGrade, $functional, $hasAccessories, $hasProofOfPurchase, 'Description de test', null, true);
    }
}
