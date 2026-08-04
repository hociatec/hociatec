<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Repository;

use Doctrine\ORM\QueryBuilder;

trait ProductCatalogFacetProjection
{
    /**
     * @return list<array{value: string, count: int, extra?: string|null}>
     */
    private function collectFacetCounts(QueryBuilder $qb, string $valueExpression, string $valueAlias, ?string $secondaryExpression = null): array
    {
        $select = [
            sprintf('%s AS value', $valueExpression),
            'COUNT(p.id) AS count',
        ];

        if (null !== $secondaryExpression) {
            $select[] = sprintf('%s AS extra', $secondaryExpression);
        }

        $rows = $qb
            ->resetDQLPart('orderBy')
            ->select(implode(', ', $select))
            ->andWhere(sprintf('%s IS NOT NULL', $valueExpression))
            ->andWhere(sprintf("%s != ''", $valueExpression))
            ->groupBy($valueExpression)
            ->orderBy($valueExpression, 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_map(
            static function (array $row) use ($secondaryExpression): array {
                $item = [
                    'value' => (string) ($row['value'] ?? ''),
                    'count' => isset($row['count']) ? (int) $row['count'] : 0,
                ];

                if (null !== $secondaryExpression) {
                    $item['extra'] = isset($row['extra']) ? (string) $row['extra'] : null;
                }

                return $item;
            },
            $rows,
        ));
    }

    /**
     * @return array{min:int|null,max:int|null}
     */
    private function collectPriceBounds(QueryBuilder $qb): array
    {
        $row = $qb
            ->resetDQLPart('orderBy')
            ->select('MIN(p.priceCents) AS minPrice, MAX(p.priceCents) AS maxPrice')
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'min' => isset($row['minPrice']) ? (int) $row['minPrice'] : null,
            'max' => isset($row['maxPrice']) ? (int) $row['maxPrice'] : null,
        ];
    }
}
