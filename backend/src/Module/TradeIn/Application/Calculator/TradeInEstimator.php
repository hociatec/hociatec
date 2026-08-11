<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Calculator;

use App\Module\TradeIn\Application\DTO\TradeInInput;

final class TradeInEstimator
{
    private const BPS_SCALE = 10_000;
    private const COEFFICIENT_SCALE = 1_000_000;

    /** @var array<string,int> */
    private const CATEGORY_BASE_CENTS = ['smartphone' => 40000, 'ordinateur' => 55000, 'tablette' => 28000, 'console' => 25000, 'appareil-photo' => 35000, 'audio' => 18000, 'electromenager' => 22000, 'autre' => 20000];
    /** @var array<string,int> */
    private const CONDITION_COEFFICIENTS_MICRO = ['comme_neuf' => 1_150_000, 'tres_bon' => 1_000_000, 'bon' => 850_000, 'correct' => 650_000, 'hors_service' => 250_000];

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
        $ageCoefficientMicro = match (true) {
            0 === $age => 700_000,
            1 === $age => 600_000,
            2 === $age => 500_000,
            3 === $age => 420_000,
            4 === $age => 340_000,
            5 === $age => 270_000,
            6 === $age => 210_000,
            7 === $age => 160_000,
            default => 120_000,
        };

        $coefficientMicro = $this->multiplyMicro(
            $ageCoefficientMicro,
            self::CONDITION_COEFFICIENTS_MICRO[$input->conditionGrade] ?? self::CONDITION_COEFFICIENTS_MICRO['correct'],
        );

        if (!$input->functional) {
            $coefficientMicro = $this->multiplyMicro($coefficientMicro, 450_000);
        }
        if ($input->hasAccessories) {
            $coefficientMicro += 30_000;
        }
        if ($input->hasProofOfPurchase) {
            $coefficientMicro += 20_000;
        }
        $coefficientMicro = min(800_000, $coefficientMicro);

        $mid = $this->applyMicro($base, $coefficientMicro);

        return [
            'minCents' => $this->applyBps($mid, 8_500),
            'maxCents' => $this->applyBps($mid, 11_500),
            'baseCents' => $base,
            'coefficient' => $coefficientMicro / self::COEFFICIENT_SCALE,
        ];
    }

    private function applyBps(int $amountCents, int $basisPoints): int
    {
        return (int) round(($amountCents * $basisPoints) / self::BPS_SCALE);
    }

    private function applyMicro(int $amountCents, int $coefficientMicro): int
    {
        return (int) round(($amountCents * $coefficientMicro) / self::COEFFICIENT_SCALE);
    }

    private function multiplyMicro(int $leftMicro, int $rightMicro): int
    {
        return (int) round(($leftMicro * $rightMicro) / self::COEFFICIENT_SCALE);
    }
}
