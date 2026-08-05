<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Cache;

final readonly class CatalogCacheInvalidator
{
    public function __construct(
        private CatalogCacheVersion $cacheVersion,
    ) {
    }

    public function invalidateAfterWrite(string $operation): void
    {
        $this->cacheVersion->bump($operation);
    }
}
