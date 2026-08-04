<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\User\Infrastructure\Repository\UserRepository;

final readonly class AccountVerificationService
{
    public const INVALID = 'invalid';
    public const EXPIRED = 'expired';
    public const ALREADY_VERIFIED = 'already_verified';
    public const VERIFIED = 'verified';

    public function __construct(
        private UserRepository $users,
        private DoctrineUnitOfWork $unitOfWork,
    ) {
    }

    public function verify(string $token): string
    {
        if (!VerificationTokenHasher::isValidRawToken($token)) {
            return self::INVALID;
        }

        $user = $this->users->findOneByVerificationTokens(
            VerificationTokenHasher::hash($token),
            $token,
        );
        if (null === $user) {
            return self::INVALID;
        }

        $now = new \DateTimeImmutable();
        $expiresAt = $user->getVerificationTokenExpiresAt();
        if ($user->isVerified()) {
            return self::ALREADY_VERIFIED;
        }
        if (null !== $expiresAt && $expiresAt < $now) {
            return self::EXPIRED;
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt($now);
        $this->users->save($user);
        $this->unitOfWork->commit();

        return self::VERIFIED;
    }
}
