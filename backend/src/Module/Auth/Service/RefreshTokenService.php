<?php

declare(strict_types=1);

namespace App\Module\Auth\Service;

use App\Module\Auth\Entity\RefreshToken;
use App\Module\Auth\Repository\RefreshTokenRepository;
use App\Module\User\Entity\User;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class RefreshTokenService
{
    private const REFRESH_TOKEN_TTL_DAYS = 30;

    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array{refreshToken: string, expiresAt: string}
     */
    public function issueForUser(User $user): array
    {
        $selector = bin2hex(random_bytes(16));
        $secret = bin2hex(random_bytes(32));
        $plainToken = $selector . '.' . $secret;
        $expiresAt = (new DateTimeImmutable())->add(new DateInterval('P' . self::REFRESH_TOKEN_TTL_DAYS . 'D'));

        $refreshToken = new RefreshToken(
            $user,
            $selector,
            hash('sha256', $secret),
            $expiresAt,
        );

        $this->refreshTokenRepository->save($refreshToken);
        $this->em->flush();

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
        [$selector, $secret] = $this->splitToken($plainToken);
        if ($selector === null || $secret === null) {
            return null;
        }

        $storedToken = $this->refreshTokenRepository->findOneBySelector($selector);
        if ($storedToken === null || $storedToken->isRevoked() || $storedToken->isExpired()) {
            return null;
        }

        if (!hash_equals($storedToken->getTokenHash(), hash('sha256', $secret))) {
            $storedToken->revoke();
            $this->em->flush();
            return null;
        }

        $storedToken->revoke();
        $issued = $this->issueForUser($storedToken->getUser());

        return [
            'user' => $storedToken->getUser(),
            'refreshToken' => $issued['refreshToken'],
            'expiresAt' => $issued['expiresAt'],
        ];
    }

    private function splitToken(string $plainToken): array
    {
        $parts = explode('.', $plainToken, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }
}
