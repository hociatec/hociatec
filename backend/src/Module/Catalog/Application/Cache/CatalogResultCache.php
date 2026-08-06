<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Cache;

interface CatalogResultCache
{
    /**
     * @template T
     *
     * @param \Closure(): T $callback
     *
     * @return T
     */
    public function get(string $key, \Closure $callback): mixed;
}
