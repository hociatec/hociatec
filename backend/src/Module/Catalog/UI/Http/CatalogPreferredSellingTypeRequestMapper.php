<?php

declare(strict_types=1);

namespace App\Module\Catalog\UI\Http;

use Symfony\Component\HttpFoundation\Request;

final class CatalogPreferredSellingTypeRequestMapper
{
    public function map(Request $request): ?string
    {
        $value = $request->query->get('sellingType');
        $normalized = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($normalized, ['sale', 'rental'], true) ? $normalized : null;
    }
}
