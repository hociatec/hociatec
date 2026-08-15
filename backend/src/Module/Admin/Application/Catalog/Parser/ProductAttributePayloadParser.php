<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\Parser;

use App\Module\Admin\Application\Catalog\Normalizer\ProductFormValueNormalizer;
use App\Shared\Application\Exception\PublicInvalidArgumentException;

final class ProductAttributePayloadParser
{
    /**
     * @return list<array{code:string,label:string,value:string}>
     */
    public function parse(mixed $value): array
    {
        if (!is_string($value) || '' === trim($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            throw new PublicInvalidArgumentException('Définition des attributs invalide.');
        }

        $attributes = [];

        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = $this->normalizeCode($row['code'] ?? null);
            $label = ProductFormValueNormalizer::optionalString($row['label'] ?? null);
            $value = ProductFormValueNormalizer::optionalString($row['value'] ?? null);

            if (null === $label && isset($row['name'])) {
                $label = ProductFormValueNormalizer::optionalString($row['name']);
            }

            if (null === $code && null !== $label) {
                $code = $this->normalizeCode($label);
            }

            if (null === $code || null === $label || null === $value) {
                continue;
            }

            $attributes[$code] = [
                'code' => $code,
                'label' => $label,
                'value' => $value,
            ];
        }

        return array_values($attributes);
    }

    private function normalizeCode(mixed $value): ?string
    {
        if (!is_scalar($value) && null !== $value) {
            return null;
        }

        $code = trim(mb_strtolower((string) $value));
        $code = preg_replace('/[^a-z0-9]+/u', '-', $code) ?? $code;
        $code = trim($code, '-');

        return '' !== $code ? $code : null;
    }
}
