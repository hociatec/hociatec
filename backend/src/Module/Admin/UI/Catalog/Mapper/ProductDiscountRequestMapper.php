<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Mapper;

use App\Module\Admin\Application\Catalog\Normalizer\ProductFormValueNormalizer;
use Symfony\Component\HttpFoundation\Request;

final class ProductDiscountRequestMapper
{
    /**
     * @return array{enabled: bool, type: ?string, value: ?int, startsAt: ?\DateTimeImmutable, endsAt: ?\DateTimeImmutable}
     */
    public function map(Request $request): array
    {
        $type = ProductFormValueNormalizer::optionalString($request->request->get('discountType'));
        if (null !== $type && !in_array($type, ['percent', 'fixed'], true)) {
            $type = null;
        }

        $rawValue = $request->request->get('discountValue');
        $value = null;
        if (null !== $rawValue && '' !== $rawValue) {
            $value = match ($type) {
                'percent' => (int) round((float) str_replace(',', '.', (string) $rawValue)),
                'fixed' => ProductFormValueNormalizer::priceToCents($rawValue),
                default => null,
            };
        }

        return [
            'enabled' => ProductFormValueNormalizer::boolean($request->request->get('discountEnabled', false)),
            'type' => 'fixed' === $type ? 'fixed_cents' : $type,
            'value' => $value,
            'startsAt' => ProductFormValueNormalizer::date($request->request->get('discountStartsAt')),
            'endsAt' => ProductFormValueNormalizer::date($request->request->get('discountEndsAt')),
        ];
    }
}
