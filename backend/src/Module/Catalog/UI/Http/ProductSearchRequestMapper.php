<?php

declare(strict_types=1);

namespace App\Module\Catalog\UI\Http;

use App\Module\Catalog\Application\DTO\ProductSearchCriteria;
use App\Shared\Domain\ValueObject\DecimalNumber;
use Symfony\Component\HttpFoundation\Request;

final class ProductSearchRequestMapper
{
    private const SORTS = [
        'relevance', 'price_asc', 'price_desc', 'release_year_desc', 'release_year_asc',
        'name_desc', 'stock_desc', 'stock_asc', 'created_desc',
    ];

    public function map(Request $request): ProductSearchCriteria
    {
        $query = $request->query;
        $page = $query->getInt('page', 1);
        if (1 > $page) {
            $page = 1;
        }
        $perPage = $query->getInt('perPage', 12);
        if (1 > $perPage) {
            $perPage = 1;
        } elseif ($perPage > 48) {
            $perPage = 48;
        }

        return new ProductSearchCriteria([
            'page' => $page,
            'perPage' => $perPage,
            'categorySlug' => $this->string($query->get('category')),
            'search' => $this->string($query->get('q')),
            'onlyFeatured' => $query->has('homepage') && $this->boolean($query->get('homepage')) ? true : null,
            'sellingType' => $this->choice($query->get('sellingType'), ['sale', 'rental']),
            'brand' => $this->string($query->get('brand')),
            'attributeFilters' => $this->attributeFilters($request),
            'storageCapacity' => $this->string($query->get('storageCapacity')),
            'memoryRam' => $this->string($query->get('memoryRam')),
            'color' => $this->string($query->get('color')),
            'minPriceCents' => $this->price($query->get('minPrice')),
            'maxPriceCents' => $this->price($query->get('maxPrice')),
            'inStockOnly' => $query->has('inStock') ? $this->boolean($query->get('inStock')) : null,
            'sort' => $this->choice($query->get('sort'), self::SORTS),
        ]);
    }

    private function boolean(mixed $value): bool
    {
        return match (true) {
            is_bool($value) => $value,
            is_string($value) => in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true),
            is_int($value) => 1 === $value,
            default => (bool) $value,
        };
    }

    private function string(mixed $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : '';

        return '' !== $normalized ? $normalized : null;
    }

    /**
     * @param list<string> $allowed
     */
    private function choice(mixed $value, array $allowed): ?string
    {
        $normalized = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($normalized, $allowed, true) ? $normalized : null;
    }

    private function price(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $cents = DecimalNumber::toScaledInt($value, 2);
        if (null === $cents) {
            return null;
        }

        return 0 > $cents ? 0 : $cents;
    }

    /**
     * @return array<string, string>
     */
    private function attributeFilters(Request $request): array
    {
        $filters = [];

        foreach ($request->query->all() as $key => $value) {
            if (!is_string($key) || !str_starts_with($key, 'attribute_')) {
                continue;
            }

            $code = trim(mb_strtolower(substr($key, 10)));
            $normalizedValue = $this->string($value);

            if ('' === $code || null === $normalizedValue) {
                continue;
            }

            $filters[$code] = $normalizedValue;
        }

        foreach ([
            'storage' => $this->string($request->query->get('storageCapacity')),
            'ram' => $this->string($request->query->get('memoryRam')),
            'color' => $this->string($request->query->get('color')),
        ] as $code => $value) {
            if (null !== $value && !isset($filters[$code])) {
                $filters[$code] = $value;
            }
        }

        return $filters;
    }
}
