<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Port;

interface CatalogPersistencePort
{
    public function save(object $entity): void;

    public function flush(): void;

    public function delete(object $entity): void;
}
