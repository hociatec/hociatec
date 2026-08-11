<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Workflow;

use App\Module\Outbox\Application\Outbox;
use App\Module\User\Application\Port\UserPasswordHasher;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

class PasswordResetService
{
    public function __construct(
        private readonly UserRepositoryPort $users,
        private readonly UnitOfWork $unitOfWork,
        private readonly TransactionManager $transactions,
        private readonly UserPasswordHasher $passwordHasher,
        private readonly Outbox $outbox,
        private readonly RefreshTokenRevocationService $refreshTokenRevocations,
    ) {
    }

    public function request(string $email): void
    {
        $user = $this->users->findOneByEmailInsensitive($email);
        if (!$user instanceof User) {
            return;
        }

        $token = PasswordResetTokenHasher::generateRawToken();
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $user
            ->setPasswordResetToken(PasswordResetTokenHasher::hash($token))
            ->setPasswordResetTokenExpiresAt($expiresAt);

        $this->transactions->transactional(function () use ($user, $token): void {
            $this->users->save($user);
            $this->outbox->record('auth.password_reset.'.hash('sha256', $token), 'auth.password_reset_email_requested', [
                'email' => $user->getEmail(),
                'token' => $token,
            ]);
        });
    }

    public function reset(string $token, string $plainPassword): void
    {
        $user = $this->users->findOneByPasswordResetTokens(PasswordResetTokenHasher::hash($token), $token);
        if (!$user instanceof User) {
            throw new \RuntimeException('Lien de réinitialisation invalide.');
        }

        $expiresAt = $user->getPasswordResetTokenExpiresAt();
        if (null === $expiresAt || $expiresAt < new \DateTimeImmutable()) {
            $user
                ->setPasswordResetToken(null)
                ->setPasswordResetTokenExpiresAt(null);
            $this->users->save($user);
            $this->unitOfWork->flush();

            throw new \RuntimeException('Le lien de réinitialisation a expiré.');
        }

        $user
            ->setPassword($this->passwordHasher->hashPassword($user, $plainPassword))
            ->setPasswordResetToken(null)
            ->setPasswordResetTokenExpiresAt(null);

        $this->transactions->transactional(function () use ($user): void {
            $this->users->save($user);
            $this->refreshTokenRevocations->revokeAllForUser($user);
            $this->unitOfWork->flush();
        });
    }
}
