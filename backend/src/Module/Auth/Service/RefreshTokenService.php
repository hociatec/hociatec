<?php

declare(strict_types=1);

namespace App\Module\Auth\Service;

use App\Module\Auth\Entity\RefreshToken;
use App\Module\Auth\Repository\RefreshTokenRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class RefreshTokenService
{
    private const REFRESH_TOKEN_TTL_DAYS = 30;

    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly RefreshTokenPersistence $persistence,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{refreshToken: string, expiresAt: string}
     */
    public function issueForUser(User $user): array
    {
        [$refreshToken, $plainToken, $expiresAt] = $this->createRefreshToken($user);

        $this->persistence->save($refreshToken);
        $this->persistence->flush();

        return [
            'refreshToken' => $plainToken,
            'expiresAt' => $expiresAt->format(DATE_ATOM),
        ];
    }

    /**
     * @return array{user: User, refreshToken: string, expiresAt: string}|null
     */
    public function rotate(string $plainToken): ?array
    {
        $parts = $this->splitToken($plainToken);
        if (null === $parts) {
            return null;
        }
        [$selector, $secret] = $parts;

        return $this->entityManager->wrapInTransaction(function () use ($selector, $secret): ?array {
            $storedToken = $this->refreshTokenRepository->findOneBySelectorForUpdate($selector);
            if (null === $storedToken || $storedToken->isRevoked() || $storedToken->isExpired()) {
                return null;
            }

            if (!hash_equals($storedToken->getTokenHash(), hash('sha256', $secret))) {
                $storedToken->revoke();

                return null;
            }

            $storedToken->revoke();
            [$refreshToken, $plainToken, $expiresAt] = $this->createRefreshToken($storedToken->getUser());
            $this->persistence->save($refreshToken);

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

        $storedToken = $this->refreshTokenRepository->findOneBySelector($selector);
        if (null === $storedToken || $storedToken->isRevoked()) {
            return;
        }

        if (!hash_equals($storedToken->getTokenHash(), hash('sha256', $secret))) {
            return;
        }

        $storedToken->revoke();
        $this->persistence->flush();
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

    /**
     * @return array{0: RefreshToken, 1: string, 2: \DateTimeImmutable}
     */
    private function createRefreshToken(User $user): array
    {
        $selector = bin2hex(random_bytes(16));
        $secret = bin2hex(random_bytes(32));
        $plainToken = $selector.'.'.$secret;
        $expiresAt = (new \DateTimeImmutable())->add(new \DateInterval('P'.self::REFRESH_TOKEN_TTL_DAYS.'D'));

        return [
            new RefreshToken(
                $user,
                $selector,
                hash('sha256', $secret),
                $expiresAt,
            ),
            $plainToken,
            $expiresAt,
        ];
    }
}
