<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Calculator;

use App\Module\TradeIn\Application\DTO\TradeInInput;

final class TradeInEstimator
{
    /** @var array<string,int> */
    private const CATEGORY_BASE_CENTS = ['smartphone' => 40000, 'ordinateur' => 55000, 'tablette' => 28000, 'console' => 25000, 'appareil-photo' => 35000, 'audio' => 18000, 'electromenager' => 22000, 'autre' => 20000];
    /** @var array<string,float> */
    private const CONDITION_COEFFICIENTS = ['comme_neuf' => 1.15, 'tres_bon' => 1.0, 'bon' => 0.85, 'correct' => 0.65, 'hors_service' => 0.25];

    /** @return array{minCents:int,maxCents:int,baseCents:int,coefficient:float} */
    public function estimate(TradeInInput $input, ?int $catalogPriceCents = null): array
    {
        if ($input->purchasePriceCents < 0 || (null !== $catalogPriceCents && $catalogPriceCents < 0)) {
            throw new \InvalidArgumentException('Le prix de référence de reprise ne peut pas être négatif.');
        }

        $base = $input->purchasePriceCents > 0 ? $input->purchasePriceCents : (null !== $catalogPriceCents && $catalogPriceCents > 0 ? $catalogPriceCents : (self::CATEGORY_BASE_CENTS[$input->category] ?? self::CATEGORY_BASE_CENTS['autre']));
        $currentYear = (int) date('Y');
        if ($input->purchaseYear > $currentYear) {
            throw new \InvalidArgumentException('L’année d’achat ne peut pas être future.');
        }

        $age = $currentYear - $input->purchaseYear;
        $ageCoefficient = match (true) {
            0 === $age => 0.70,
            1 === $age => 0.60,
            2 === $age => 0.50,
            3 === $age => 0.42,
            4 === $age => 0.34,
            5 === $age => 0.27,
            6 === $age => 0.21,
            7 === $age => 0.16,
            default => 0.12,
        };
        $coefficient = $ageCoefficient * (self::CONDITION_COEFFICIENTS[$input->conditionGrade] ?? self::CONDITION_COEFFICIENTS['correct']);
        if (!$input->functional) {
            $coefficient *= 0.45;
        }
        if ($input->hasAccessories) {
            $coefficient += 0.03;
        }
        if ($input->hasProofOfPurchase) {
            $coefficient += 0.02;
        }
        if ($coefficient > 0.8) {
            $coefficient = 0.8;
        }

        $mid = (int) round($base * $coefficient);

        return ['minCents' => (int) round($mid * 0.85), 'maxCents' => (int) round($mid * 1.15), 'baseCents' => $base, 'coefficient' => $coefficient];
    }
}
