<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Workflow;

use App\Module\Auth\Application\DTO\RefreshTokenContext;
use App\Module\Auth\Application\Port\RefreshTokenRepositoryPort;
use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final class RefreshTokenService
{
    private const REFRESH_TOKEN_TTL_DAYS = 30;
    private const MAX_ACTIVE_SESSIONS_PER_USER = 10;

    public function __construct(
        private readonly RefreshTokenRepositoryPort $refreshTokenRepository,
        private readonly UnitOfWork $unitOfWork,
        private readonly TransactionManager $transactions,
        private readonly RefreshTokenRevocationService $revocations,
    ) {
    }

    /**
     * @return array{refreshToken: string, expiresAt: string}
     */
    public function issueForUser(User $user, ?RefreshTokenContext $context = null): array
    {
        return $this->transactions->transactional(function () use ($user, $context): array {
            $sourceToken = $this->revokeExistingDeviceSessions($user, $context);
            [$refreshToken, $plainToken, $expiresAt] = $this->createRefreshToken($user, $context, $sourceToken);

            $this->unitOfWork->persist($refreshToken);
            $this->unitOfWork->flush();
            $this->revocations->revokeActiveTokensOverLimit($user, self::MAX_ACTIVE_SESSIONS_PER_USER);

            return [
                'refreshToken' => $plainToken,
                'expiresAt' => $expiresAt->format(DATE_ATOM),
            ];
        });
    }

    /**
     * @return array{user: User, refreshToken: string, expiresAt: string}|null
     */
    public function rotate(string $plainToken, ?RefreshTokenContext $context = null): ?array
    {
        $parts = $this->splitToken($plainToken);
        if (null === $parts) {
            return null;
        }
        [$selector, $secret] = $parts;

        return $this->transactions->transactional(function () use ($selector, $secret, $context): ?array {
            $storedToken = $this->refreshTokenRepository->findOneBySelectorForUpdate($selector);
            if (null === $storedToken || $storedToken->isRevoked() || $storedToken->isExpired()) {
                return null;
            }

            if (!hash_equals($storedToken->getTokenHash(), hash('sha256', $secret))) {
                $storedToken->revoke();

                return null;
            }

            $storedToken->revoke();
            $sourceToken = $this->resolveRotationSourceToken($storedToken, $context);
            [$refreshToken, $plainToken, $expiresAt] = $this->createRefreshToken($storedToken->getUser(), $context, $sourceToken);
            $this->unitOfWork->persist($refreshToken);
            $this->unitOfWork->flush();
            $this->revocations->revokeActiveTokensOverLimit($storedToken->getUser(), self::MAX_ACTIVE_SESSIONS_PER_USER);

            return [
                'user' => $storedToken->getUser(),
                'refreshToken' => $plainToken,
                'expiresAt' => $expiresAt->format(DATE_ATOM),
            ];
        });
    }

    public function revokePlainToken(string $plainToken): void
    {
        $parts = $this->splitToken($plainToken);
        if (null === $parts) {
            return;
        }
        [$selector, $secret] = $parts;

        $this->transactions->transactional(function () use ($selector, $secret): void {
            $storedToken = $this->refreshTokenRepository->findOneBySelectorForUpdate($selector);
            if (null === $storedToken || $storedToken->isRevoked()) {
                return;
            }

            if (!hash_equals($storedToken->getTokenHash(), hash('sha256', $secret))) {
                return;
            }

            $storedToken->revoke();
            $this->unitOfWork->flush();
        });
    }

    /** @return array{0: string, 1: string}|null */
    private function splitToken(string $plainToken): ?array
    {
        $parts = explode('.', $plainToken, 2);

        if (2 !== count($parts) || '' === $parts[0] || '' === $parts[1]) {
            return null;
        }

        return [$parts[0], $parts[1]];
    }

    private function revokeExistingDeviceSessions(User $user, ?RefreshTokenContext $context): ?RefreshToken
    {
        $deviceIdentifier = $context?->deviceIdentifier;
        if (null === $deviceIdentifier) {
            return null;
        }

        $tokens = $this->refreshTokenRepository->findActiveForUserAndDeviceIdentifier($user, $deviceIdentifier);
        if ([] === $tokens) {
            return null;
        }

        $sourceToken = $tokens[0];
        foreach ($tokens as $token) {
            $token->revoke();
        }

        return $sourceToken;
    }

    private function resolveRotationSourceToken(RefreshToken $storedToken, ?RefreshTokenContext $context): RefreshToken
    {
        $deviceIdentifier = $context?->deviceIdentifier;
        if (null === $deviceIdentifier || $deviceIdentifier === $storedToken->getDeviceIdentifier()) {
            return $storedToken;
        }

        $tokens = $this->refreshTokenRepository->findActiveForUserAndDeviceIdentifier($storedToken->getUser(), $deviceIdentifier);
        if ([] === $tokens) {
            return $storedToken;
        }

        $sourceToken = $tokens[0];
        foreach ($tokens as $token) {
            $token->revoke();
        }

        return $sourceToken;
    }

    /**
     * @return array{0: RefreshToken, 1: string, 2: \DateTimeImmutable}
     */
    private function createRefreshToken(User $user, ?RefreshTokenContext $context = null, ?RefreshToken $sourceToken = null): array
    {
        $selector = bin2hex(random_bytes(16));
        $secret = bin2hex(random_bytes(32));
        $plainToken = $selector.'.'.$secret;
        $expiresAt = (new \DateTimeImmutable())->add(new \DateInterval('P'.self::REFRESH_TOKEN_TTL_DAYS.'D'));
        $createdAt = null;
        $deviceIdentifier = $context?->deviceIdentifier;
        $deviceLabel = $context?->deviceLabel;
        $platformLabel = $context?->platformLabel;
        $clientLabel = $context?->clientLabel;
        $locationLabel = $context?->locationLabel;
        $userAgent = $context?->userAgent;
        $ipAddress = $context?->ipAddress;

        if (null !== $sourceToken) {
            $createdAt = $sourceToken->getCreatedAt();
            $deviceIdentifier ??= $sourceToken->getDeviceIdentifier();
            $deviceLabel ??= $sourceToken->getDeviceLabel();
            $platformLabel ??= $sourceToken->getPlatformLabel();
            $clientLabel ??= $sourceToken->getClientLabel();
            $locationLabel ??= $sourceToken->getLocationLabel();
            $userAgent ??= $sourceToken->getUserAgent();
            $ipAddress ??= $sourceToken->getIpAddress();
        }

        return [
            new RefreshToken(
                $user,
                $selector,
                hash('sha256', $secret),
                $expiresAt,
                $deviceIdentifier,
                $deviceLabel,
                $platformLabel,
                $clientLabel,
                $locationLabel,
                $userAgent,
                $ipAddress,
                $createdAt,
            ),
            $plainToken,
            $expiresAt,
        ];
    }
}
