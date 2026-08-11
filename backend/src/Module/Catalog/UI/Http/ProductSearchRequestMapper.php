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

        return new ProductSearchCriteria(
            $page,
            $perPage,
            $this->string($query->get('category')),
            $this->string($query->get('q')),
            $query->has('homepage') && $this->boolean($query->get('homepage')) ? true : null,
            $this->choice($query->get('sellingType'), ['sale', 'rental']),
            $this->string($query->get('brand')),
            $this->string($query->get('storageCapacity')),
            $this->string($query->get('memoryRam')),
            $this->string($query->get('color')),
            $this->price($query->get('minPrice')),
            $this->price($query->get('maxPrice')),
            $query->has('inStock') ? $this->boolean($query->get('inStock')) : null,
            $this->choice($query->get('sort'), self::SORTS),
        );
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
}
