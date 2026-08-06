<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Handler;

use App\Module\Catalog\Application\Cache\CatalogCacheInvalidator;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final readonly class ProductWriteExecution
{
    public function __construct(
        public UnitOfWork $persistence,
        public TransactionManager $transactions,
        public CatalogCacheInvalidator $cache,
    ) {
    }
}
