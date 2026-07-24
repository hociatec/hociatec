<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Service;

final class ProductVariantPayloadParser
{
    /**
     * @return list<array{color: ?string, storageCapacity: ?string, stock: int}>
     */
    public function parse(mixed $value): array
    {
        if (!is_string($value) || '' === trim($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('Définition des variantes invalide.');
        }

        $variants = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $variants[] = [
                'color' => ProductFormValueNormalizer::optionalString($row['color'] ?? null),
                'storageCapacity' => ProductFormValueNormalizer::optionalString($row['storageCapacity'] ?? null),
                'stock' => isset($row['stock']) ? (int) $row['stock'] : 0,
            ];
        }

        return $variants;
    }
}
