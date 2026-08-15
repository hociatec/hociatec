<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Support;

final class RentalPeriodCalculator
{
    public static function normalizeDate(?\DateTimeImmutable $date): ?\DateTimeImmutable
    {
        return $date?->setTime(0, 0, 0);
    }

    public static function parseDate(?string $value): ?\DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim($value);
        if ('' === $normalized) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $normalized);
        if (!$date instanceof \DateTimeImmutable) {
            return null;
        }

        return self::normalizeDate($date);
    }

    public static function formatDate(?\DateTimeImmutable $date): ?string
    {
        return self::normalizeDate($date)?->format('Y-m-d');
    }

    public static function calculateEndDate(?\DateTimeImmutable $startDate, ?int $months): ?\DateTimeImmutable
    {
        if (null === $startDate || null === $months || $months < 1) {
            return null;
        }

        return self::normalizeDate($startDate)
            ?->add(new \DateInterval(sprintf('P%dM', $months)))
            ?->sub(new \DateInterval('P1D'));
    }

    public static function findAlignedMonthsForEndDate(
        ?\DateTimeImmutable $startDate,
        ?\DateTimeImmutable $endDate,
        int $maxMonths = 120,
    ): ?int {
        $normalizedStartDate = self::normalizeDate($startDate);
        $normalizedEndDate = self::normalizeDate($endDate);
        if (null === $normalizedStartDate || null === $normalizedEndDate || $maxMonths < 1) {
            return null;
        }

        for ($months = 1; $months <= $maxMonths; ++$months) {
            if (self::calculateEndDate($normalizedStartDate, $months)?->format('Y-m-d') === $normalizedEndDate->format('Y-m-d')) {
                return $months;
            }
        }

        return null;
    }
}
