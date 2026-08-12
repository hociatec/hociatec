<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\Parser;

use App\Module\Admin\Application\Catalog\Normalizer\ProductFormValueNormalizer;
use App\Shared\Application\Exception\PublicInvalidArgumentException;

final class ProductVariantPayloadParser
{
    /**
     * @return list<array{color: ?string, storageCapacity: ?string, stock: int, priceCents?: int|null}>
     */
    public function parse(mixed $value): array
    {
        if (!is_string($value) || '' === trim($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            throw new PublicInvalidArgumentException('Définition des variantes invalide.');
        }

        $variants = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $stock = isset($row['stock']) ? (int) $row['stock'] : 0;
            if ($stock < 0) {
                throw new PublicInvalidArgumentException('Le stock des variantes doit être positif.');
            }

            $priceCents = array_key_exists('price', $row)
                ? ProductFormValueNormalizer::priceToCents($row['price'])
                : null;
            if (null !== $priceCents && $priceCents < 0) {
                throw new PublicInvalidArgumentException('Le prix des variantes est invalide.');
            }

            $variant = [
                'color' => ProductFormValueNormalizer::optionalString($row['color'] ?? null),
                'storageCapacity' => ProductFormValueNormalizer::optionalString($row['storageCapacity'] ?? null),
                'stock' => $stock,
            ];

            if (null !== $priceCents) {
                $variant['priceCents'] = $priceCents;
            }

            $variants[] = $variant;
        }

        return $variants;
    }
}
