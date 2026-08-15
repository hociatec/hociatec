<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Workflow;

use App\Module\Auth\Application\Port\RefreshTokenRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class RefreshTokenRevocationService
{
    public function __construct(
        private RefreshTokenRepositoryPort $refreshTokenRepository,
        private ?UnitOfWork $unitOfWork = null,
    ) {
    }

    public function revokeAllForUser(User $user): void
    {
        $this->refreshTokenRepository->revokeAllForUser($user);
        $this->unitOfWork?->flush();
    }

    /**
     * @return list<\App\Module\Auth\Domain\Entity\RefreshToken>
     */
    public function activeSessionsForUser(User $user): array
    {
        $tokens = $this->refreshTokenRepository->findActiveForUser($user);
        $groupedTokens = [];
        $ungroupedTokens = [];

        foreach ($tokens as $token) {
            $deviceIdentifier = $token->getDeviceIdentifier();
            if (null === $deviceIdentifier) {
                $ungroupedTokens[] = $token;
                continue;
            }

            $groupedTokens[$deviceIdentifier] ??= $token;
        }

        return [...array_values($groupedTokens), ...$ungroupedTokens];
    }

    public function revokeOneForUser(User $user, int $sessionId): ?\App\Module\Auth\Domain\Entity\RefreshToken
    {
        $token = $this->refreshTokenRepository->findOneActiveByIdForUser($sessionId, $user);
        if (null === $token) {
            return null;
        }

        $deviceIdentifier = $token->getDeviceIdentifier();
        if (null !== $deviceIdentifier) {
            foreach ($this->refreshTokenRepository->findActiveForUserAndDeviceIdentifier($user, $deviceIdentifier) as $deviceToken) {
                $deviceToken->revoke();
            }
        } else {
            $token->revoke();
        }
        $this->unitOfWork?->flush();

        return $token;
    }

    public function isSessionActiveForUser(User $user, string $selector): bool
    {
        return null !== $this->refreshTokenRepository->findOneActiveBySelectorForUser($selector, $user);
    }

    public function revokeAllActive(): int
    {
        $count = $this->refreshTokenRepository->revokeAllActive();
        if ($count > 0) {
            $this->unitOfWork?->flush();
        }

        return $count;
    }

    public function revokeActiveTokensOverLimit(User $user, int $limit): void
    {
        if ($limit <= 0) {
            throw new \InvalidArgumentException('La limite de sessions doit être positive.');
        }

        if ($this->refreshTokenRepository->revokeActiveTokensOverLimit($user, $limit) > 0) {
            $this->unitOfWork?->flush();
        }
    }
}
