<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\Service;

use App\Module\TradeIn\Application\DTO\TradeInInput;
use App\Module\TradeIn\Application\Calculator\TradeInEstimator;
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

    public function testItFallsBackToCatalogPriceThenCategoryBase(): void
    {
        $fromCatalog = $this->input(purchasePriceCents: 0, purchaseYear: (int) date('Y') - 1, conditionGrade: 'tres_bon');
        $catalogEstimate = (new TradeInEstimator())->estimate($fromCatalog, 65000);

        self::assertSame(65000, $catalogEstimate['baseCents']);
        self::assertSame(35913, $catalogEstimate['minCents']);
        self::assertSame(48588, $catalogEstimate['maxCents']);

        $fromCategory = new TradeInInput('Ada', 'Lovelace', 'ada@example.com', '0102030405', 'mystere', 'Objet', 0, (int) date('Y') - 20, null, null, null, 'inconnu', true, false, false, 'Description', null, true);
        $categoryEstimate = (new TradeInEstimator())->estimate($fromCategory);

        self::assertSame(20000, $categoryEstimate['baseCents']);
        self::assertSame(1326, $categoryEstimate['minCents']);
        self::assertSame(1794, $categoryEstimate['maxCents']);
        self::assertSame(0.12 * 0.65, $categoryEstimate['coefficient']);
    }

    private function input(int $purchasePriceCents, int $purchaseYear, string $conditionGrade, bool $functional = true, bool $hasAccessories = true, bool $hasProofOfPurchase = true): TradeInInput
    {
        return new TradeInInput('Ada', 'Lovelace', 'ada@example.com', '0102030405', 'ordinateur', 'Ordinateur de test', $purchasePriceCents, $purchaseYear, null, null, null, $conditionGrade, $functional, $hasAccessories, $hasProofOfPurchase, 'Description de test', null, true);
    }
}
