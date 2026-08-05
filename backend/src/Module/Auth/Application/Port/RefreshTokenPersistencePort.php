<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Port;

use App\Module\Auth\Domain\Entity\RefreshToken;

interface RefreshTokenPersistencePort
{
    public function save(RefreshToken $token): void;
    public function commit(): void;
}
