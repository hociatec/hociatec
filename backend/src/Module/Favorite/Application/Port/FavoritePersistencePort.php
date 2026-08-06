<?php

declare(strict_types=1);

namespace App\Module\Favorite\Application\Port;

use App\Module\Favorite\Domain\Entity\Favorite;

interface FavoritePersistencePort
{
    public function save(Favorite $favorite): void;

    public function delete(Favorite $favorite): void;

    public function commit(): void;
}
