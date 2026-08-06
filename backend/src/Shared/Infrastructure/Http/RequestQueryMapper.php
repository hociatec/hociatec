<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Domain\DateTime\DateTimeParser;
use Symfony\Component\HttpFoundation\Request;

final class RequestQueryMapper
{
    private function __construct()
    {
    }

    public static function pagination(Request $request, int $defaultPerPage = 20, int $maxPerPage = 100): Pagination
    {
        return Pagination::fromRequest($request, $defaultPerPage, $maxPerPage);
    }

    public static function string(Request $request, string $name, string $default = ''): string
    {
        $value = $request->query->get($name, $default);

        return trim((string) $value);
    }

    public static function nullableString(Request $request, string $name): ?string
    {
        $value = self::string($request, $name);

        return '' !== $value ? $value : null;
    }

    public static function lowerString(Request $request, string $name): string
    {
        return mb_strtolower(self::string($request, $name));
    }

    /** @param list<string> $allowed */
    public static function choice(Request $request, string $name, array $allowed, ?string $default = null): ?string
    {
        $value = self::string($request, $name, $default ?? '');

        return in_array($value, $allowed, true) ? $value : $default;
    }

    public static function intOrNull(Request $request, string $name): ?int
    {
        if (!$request->query->has($name)) {
            return null;
        }

        $value = self::string($request, $name);

        return '' !== $value && is_numeric($value) ? (int) $value : null;
    }

    public static function requiredInt(Request $request, string $name): ?int
    {
        $value = self::intOrNull($request, $name);

        return null !== $value && 0 !== $value ? $value : null;
    }

    /** @param list<string> $names */
    public static function positiveIntFromAny(Request $request, array $names): ?int
    {
        foreach ($names as $name) {
            if (!$request->query->has($name)) {
                continue;
            }

            $value = self::string($request, $name);
            if ('' === $value) {
                return null;
            }
            if (!is_numeric($value)) {
                throw new \InvalidArgumentException('Le nombre de mois doit etre un entier positif.');
            }

            $intValue = (int) $value;
            if ($intValue < 1) {
                throw new \InvalidArgumentException('La duree de location doit etre superieure ou egale a 1 mois.');
            }

            return $intValue;
        }

        return null;
    }

    public static function dateTime(Request $request, string $name): ?\DateTimeImmutable
    {
        $value = self::nullableString($request, $name);
        if (null === $value) {
            return null;
        }

        $fromIso = DateTimeParser::fromFormat(\DateTimeImmutable::ATOM, $value);
        if (null !== $fromIso) {
            return $fromIso;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @return array{status:string,severity:string,search:string,campaignId:?int,assignedTo:?int}
     */
    public static function betaReportFilters(Request $request): array
    {
        return [
            'status' => self::string($request, 'status'),
            'severity' => self::string($request, 'severity'),
            'search' => self::string($request, 'search'),
            'campaignId' => self::intOrNull($request, 'campaignId'),
            'assignedTo' => self::intOrNull($request, 'assignedTo'),
        ];
    }
}
