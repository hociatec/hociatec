<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Port;

use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\User\Domain\Entity\User;

interface RefreshTokenRepositoryPort
{
    public function findOneBySelector(string $selector): ?RefreshToken;

    public function findOneBySelectorForUpdate(string $selector): ?RefreshToken;

    /**
     * @return list<RefreshToken>
     */
    public function findActiveForUser(User $user): array;

    /**
     * @return list<RefreshToken>
     */
    public function findActiveForUserAndDeviceIdentifier(User $user, string $deviceIdentifier): array;

    public function findOneActiveBySelectorForUser(string $selector, User $user): ?RefreshToken;

    public function findOneActiveByIdForUser(int $id, User $user): ?RefreshToken;

    public function revokeAllForUser(User $user): void;

    public function revokeAllActive(): int;

    public function revokeActiveTokensOverLimit(User $user, int $limit): int;
}
