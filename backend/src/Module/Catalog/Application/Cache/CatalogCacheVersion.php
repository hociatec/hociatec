<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Cache;

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CatalogCacheVersion
{
    private const KEY = 'catalog_version';

    public function __construct(
        #[Autowire(service: 'app.catalog_cache')]
        private CacheItemPoolInterface $catalogCache,
        private LoggerInterface $logger,
    ) {
    }

    public function current(): int
    {
        try {
            $item = $this->catalogCache->getItem(self::KEY);
            $version = $item->get();

            return is_int($version) && $version > 0 ? $version : 1;
        } catch (CacheException|\RuntimeException $exception) {
            $this->logger->warning('Catalog cache version read failed.', ['exception' => $exception]);

            return 1;
        }
    }

    public function bump(string $operation): void
    {
        try {
            $item = $this->catalogCache->getItem(self::KEY);
            $current = $item->get();
            $item->set((is_int($current) && $current > 0 ? $current : 1) + 1);

            if ($this->catalogCache->save($item)) {
                return;
            }

            $this->logger->warning('Catalog cache version bump returned false after product write.', [
                'operation' => $operation,
            ]);
        } catch (CacheException|\RuntimeException $exception) {
            $this->logger->warning('Catalog cache version bump failed after product write.', [
                'operation' => $operation,
                'exception' => $exception,
            ]);
        }
    }
}
