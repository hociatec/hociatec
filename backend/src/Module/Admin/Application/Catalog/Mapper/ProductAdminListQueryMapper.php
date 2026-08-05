<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\Mapper;

use App\Module\Admin\Application\Catalog\DTO\ProductAdminListQuery;
use Symfony\Component\HttpFoundation\Request;

final readonly class ProductAdminListQueryMapper
{
    private const MAX_PER_PAGE = 48;
    private const DEFAULT_PER_PAGE = 12;

    public function fromRequest(Request $request): ProductAdminListQuery
    {
        return new ProductAdminListQuery(
            max(1, $request->query->getInt('page', 1)),
            max(1, min(self::MAX_PER_PAGE, $request->query->getInt('perPage', self::DEFAULT_PER_PAGE))),
            $this->string($request->query->get('category')),
            $this->string($request->query->get('search')),
            $this->boolean($request, 'featured'),
            $this->choice($request->query->get('sellingType'), ['sale', 'rental']),
            $this->price($request->query->get('minPrice')),
            $this->price($request->query->get('maxPrice')),
            'low' === $this->string($request->query->get('stock')),
            $this->choice($request->query->get('sort'), [
                'relevance',
                'price_asc',
                'price_desc',
                'release_year_desc',
                'release_year_asc',
                'name_desc',
                'stock_desc',
                'stock_asc',
                'created_desc',
            ]),
        );
    }

    private function string(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return '' !== $value ? $value : null;
    }

    /** @param list<string> $allowed */
    private function choice(mixed $value, array $allowed): ?string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function boolean(Request $request, string $name): ?bool
    {
        if (!$request->query->has($name)) {
            return null;
        }

        return in_array(strtolower((string) $request->query->get($name)), ['1', 'true', 'yes', 'on'], true);
    }

    private function price(mixed $value): ?int
    {
        if (null === $value || '' === $value || !is_numeric($value)) {
            return null;
        }

        return max(0, (int) round((float) $value * 100));
    }
}
