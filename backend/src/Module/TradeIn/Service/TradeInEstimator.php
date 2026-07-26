<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Service;

use App\Module\TradeIn\DTO\TradeInInput;

final class TradeInEstimator
{
    /** @var array<string,int> */
    private const CATEGORY_BASE_CENTS = ['smartphone' => 40000, 'ordinateur' => 55000, 'tablette' => 28000, 'console' => 25000, 'appareil-photo' => 35000, 'audio' => 18000, 'electromenager' => 22000, 'autre' => 20000];
    /** @var array<string,float> */
    private const CONDITION_COEFFICIENTS = ['comme_neuf' => 0.65, 'tres_bon' => 0.55, 'bon' => 0.42, 'correct' => 0.28, 'hors_service' => 0.08];

    /** @return array{minCents:int,maxCents:int,baseCents:int,coefficient:float} */
    public function estimate(TradeInInput $input, ?int $catalogPriceCents = null): array
    {
        $base = null !== $catalogPriceCents && $catalogPriceCents > 0 ? $catalogPriceCents : (self::CATEGORY_BASE_CENTS[$input->category] ?? self::CATEGORY_BASE_CENTS['autre']);
        $coefficient = self::CONDITION_COEFFICIENTS[$input->conditionGrade] ?? self::CONDITION_COEFFICIENTS['correct'];
        if (!$input->functional) { $coefficient *= 0.45; }
        if ($input->hasAccessories) { $coefficient += 0.04; }
        if ($input->hasProofOfPurchase) { $coefficient += 0.03; }
        $mid = max(0, (int) round($base * min(0.8, $coefficient)));

        return ['minCents' => max(0, (int) round($mid * 0.85)), 'maxCents' => (int) round($mid * 1.15), 'baseCents' => $base, 'coefficient' => $coefficient];
    }
}
