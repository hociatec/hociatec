<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Workflow;

use App\Module\Auth\Application\Port\RefreshTokenRepositoryPort;
use App\Module\User\Domain\Entity\User;
use InvalidArgumentException;

final readonly class RefreshTokenRevocationService
{
    public function __construct(
        private RefreshTokenRepositoryPort $refreshTokenRepository,
    ) {
    }

    public function revokeAllForUser(User $user): void
    {
        $this->refreshTokenRepository->revokeAllForUser($user);
    }

    public function revokeActiveTokensOverLimit(User $user, int $limit): void
    {
        if ($limit <= 0) {
            throw new InvalidArgumentException('La limite de sessions doit être positive.');
        }

        $this->refreshTokenRepository->revokeActiveTokensOverLimit($user, $limit);
    }
}
