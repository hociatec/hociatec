<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Application\Port\CatalogPersistencePort;
use App\Module\Catalog\Application\Port\CategoryRepositoryPort;
use App\Shared\Application\Text\Slugifier;

readonly class CategoryCatalogWorkflow
{
    use CategoryReaderTrait;
    use CategoryValidationTrait;
    use CategoryWriterTrait;
    use Slugifier;

    public function __construct(
        private CategoryRepositoryPort $categoryRepository,
        private CatalogPersistencePort $persistence,
    ) {
    }
}
