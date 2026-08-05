<?php

declare(strict_types=1);

namespace App\Module\Rating\Application\Port;

interface RatingPersistencePort
{
    public function persist(object $entity): void;
    public function commit(): void;
}
