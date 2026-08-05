<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Cache;

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CatalogCacheInvalidator
{
    public function __construct(
        #[Autowire(service: 'app.catalog_cache')]
        private CacheItemPoolInterface $catalogCache,
        private LoggerInterface $logger,
    ) {
    }

    public function invalidateAfterWrite(string $operation): void
    {
        try {
            if ($this->catalogCache->clear()) {
                return;
            }

            $this->logger->warning('Catalog cache invalidation returned false after product write.', [
                'operation' => $operation,
            ]);
        } catch (CacheException|\RuntimeException $exception) {
            $this->logger->warning('Catalog cache invalidation failed after product write.', [
                'operation' => $operation,
                'exception' => $exception,
            ]);
        }
    }
}
