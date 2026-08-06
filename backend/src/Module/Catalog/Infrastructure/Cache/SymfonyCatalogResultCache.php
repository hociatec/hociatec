<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Cache;

use App\Module\Catalog\Application\Cache\CatalogResultCache;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class SymfonyCatalogResultCache implements CatalogResultCache
{
    public function __construct(
        #[Autowire(service: 'app.catalog_cache')]
        private CacheInterface $cache,
    ) {
    }

    public function get(string $key, \Closure $callback): mixed
    {
        return $this->cache->get($key, $callback);
    }
}
