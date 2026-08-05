<?php

declare(strict_types=1);

namespace App\Shared\Application;

interface UnitOfWork
{
    public function persist(object $entity): void;

    public function remove(object $entity): void;

    public function commit(): void;
}
