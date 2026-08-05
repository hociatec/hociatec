<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Port;

use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\User\Domain\Entity\User;

interface RefreshTokenRepositoryPort
{
    public function findOneBySelector(string $selector): ?RefreshToken;

    public function findOneBySelectorForUpdate(string $selector): ?RefreshToken;

    public function revokeAllForUser(User $user): void;

    public function revokeActiveTokensOverLimit(User $user, int $limit): int;
}
