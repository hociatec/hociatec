<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Persistence;

/** Persistence boundary for operational workflows. */
interface OperationsPersistence
{
    public function persist(object $entity): void;

    public function flush(): void;
}
