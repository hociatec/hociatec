<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\TradeIn\Domain\ValueObject\TradeInCatalogReference;
use App\Module\TradeIn\Domain\ValueObject\TradeInEstimate;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductCondition;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductIdentity;
use App\Module\TradeIn\Domain\ValueObject\TradeInPurchase;
use App\Module\TradeIn\Domain\ValueObject\TradeInTechnicalIdentity;
use App\Shared\Application\Text\Slugifier;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\DecimalNumber;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class AdditionalValueObjectClosureTest extends TestCase
{
    public function testTradeInValueObjectsAcceptValidInputsAndExposeDerivedFields(): void
    {
        $catalogReference = new TradeInCatalogReference(42, 'iPhone 15');
        self::assertSame(42, $catalogReference->productId);
        self::assertSame('iPhone 15', $catalogReference->productName);

        $estimate = new TradeInEstimate(10000, 15000, 12500, new \DateTimeImmutable('2026-08-20'));
        self::assertSame(10000, $estimate->minCents);
        self::assertSame(12500, $estimate->offerCents);

        $condition = new TradeInProductCondition('A', true, true, false, 'Très bon état');
        self::assertSame('A', $condition->conditionGrade);
        self::assertSame('Très bon état', $condition->description);

        $technical = new TradeInTechnicalIdentity('Apple', '15 Pro', 'SN-15');
        $identity = new TradeInProductIdentity('smartphone', 'iPhone 15 Pro', $technical, $catalogReference);
        self::assertSame('Apple', $identity->brand);
        self::assertSame('15 Pro', $identity->model);
        self::assertSame('SN-15', $identity->serialNumber);
        self::assertSame(42, $identity->catalogProductId);
        self::assertSame('iPhone 15', $identity->catalogProductName);

        $purchase = new TradeInPurchase(89900, 2024);
        self::assertSame(89900, $purchase->priceCents);
        self::assertSame(2024, $purchase->year);
    }

    public function testTradeInValueObjectsRejectInvalidInputs(): void
    {
        $cases = [
            [static fn (): object => new TradeInCatalogReference(0, 'x'), 'La référence catalogue est invalide.'],
            [static fn (): object => new TradeInEstimate(-1, 10, null, null), 'Les estimations de reprise ne peuvent pas être négatives.'],
            [static fn (): object => new TradeInEstimate(10, 5, null, null), 'L’estimation maximale doit être supérieure ou égale à l’estimation minimale.'],
            [static fn (): object => new TradeInEstimate(10, 20, -1, null), 'Le montant de l’offre ne peut pas être négatif.'],
            [static fn (): object => new TradeInProductCondition('', true, true, true, 'desc'), 'L’état du produit repris est obligatoire.'],
            [static fn (): object => new TradeInProductCondition('A', true, true, true, '   '), 'La description de reprise est obligatoire.'],
            [static fn (): object => new TradeInProductIdentity('', 'Produit'), 'La catégorie de reprise est obligatoire.'],
            [static fn (): object => new TradeInProductIdentity('smartphone', ''), 'Le nom du produit repris est obligatoire.'],
            [static fn (): object => new TradeInPurchase(-1, 2024), 'Le prix d’achat ne peut pas être négatif.'],
            [static fn (): object => new TradeInPurchase(100, 1979), 'L’année d’achat est invalide.'],
        ];

        foreach ($cases as [$factory, $message]) {
            try {
                $factory();
                self::fail('Expected invalid argument exception.');
            } catch (\InvalidArgumentException $exception) {
                self::assertSame($message, $exception->getMessage());
            }
        }
    }

    public function testMoneyCoversCurrencyHelpersAndMismatchedOperations(): void
    {
        $eur = Money::fromCents(1000);
        $usd = Money::fromCents(500, 'usd');

        self::assertSame(Currency::EUR, $eur->currency());
        self::assertSame('EUR', $eur->currencyCode());
        self::assertSame(2, Currency::EUR->minorUnitExponent());
        self::assertSame(Currency::USD, $usd->currency());
        self::assertSame('USD', $usd->currencyCode());
        self::assertSame(2, Currency::USD->minorUnitExponent());
        self::assertFalse($eur->equals($usd));

        try {
            $eur->add($usd);
            self::fail('Expected exception for mismatched currency add.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Les montants doivent utiliser la même monnaie.', $exception->getMessage());
        }

        try {
            $eur->subtract($usd);
            self::fail('Expected exception for mismatched currency subtract.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Les montants doivent utiliser la même monnaie.', $exception->getMessage());
        }
    }

    public function testDecimalNumberParsesPricesAndPercentagesWithoutFloatMath(): void
    {
        self::assertSame(1250, DecimalNumber::toScaledInt('12,50', 2));
        self::assertSame(1055, DecimalNumber::toScaledInt('10.55', 2));
        self::assertSame(550, DecimalNumber::toScaledInt('5,5', 2));
        self::assertSame(-550, DecimalNumber::toScaledInt('-5,5', 2));
        self::assertSame(1000, DecimalNumber::toScaledInt(10, 2));
        self::assertNull(DecimalNumber::toScaledInt('bad', 2));
    }

    public function testMoneyOnlySupportsExplicitCurrenciesWithDeclaredMinorUnits(): void
    {
        $eur = Money::fromCents(1234, 'EUR');
        $usd = Money::fromCents(5678, 'usd');

        self::assertSame('EUR', $eur->currencyCode());
        self::assertSame(2, $eur->currency()->minorUnitExponent());
        self::assertSame('USD', $usd->currencyCode());
        self::assertSame(2, $usd->currency()->minorUnitExponent());

        $this->expectException(\InvalidArgumentException::class);
        Currency::fromCode('JPY');
    }

    public function testSlugifierTraitCoversNormalizationFallbackAndMojibakeRepair(): void
    {
        $slugifier = new class {
            use Slugifier;

            public function slug(string $value, string $fallback = 'n-a'): string
            {
                return $this->slugifyValue($value, $fallback);
            }
        };

        self::assertSame('iphone-15-pro', $slugifier->slug(' iPhone 15 Pro '));
        self::assertSame('ete-a-paris', $slugifier->slug('Été à Paris'));
        self::assertStringEndsWith('clair', $slugifier->slug('Ã‰clair'));
        self::assertSame('fallback', $slugifier->slug('***', 'fallback'));
    }
}
