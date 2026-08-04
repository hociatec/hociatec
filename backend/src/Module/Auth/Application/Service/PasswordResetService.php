<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Service;

use App\Module\Outbox\Application\Outbox;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Application\TransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly DoctrineUnitOfWork $unitOfWork,
        private readonly TransactionManager $transactions,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Outbox $outbox,
    ) {
    }

    public function request(string $email): void
    {
        $user = $this->users->findOneByEmailInsensitive($email);
        if (!$user instanceof User) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $user
            ->setPasswordResetToken($token)
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
        $user = $this->users->findOneByPasswordResetToken($token);
        if (!$user instanceof User) {
            throw new \RuntimeException('Lien de réinitialisation invalide.');
        }

        $expiresAt = $user->getPasswordResetTokenExpiresAt();
        if (null === $expiresAt || $expiresAt < new \DateTimeImmutable()) {
            $user
                ->setPasswordResetToken(null)
                ->setPasswordResetTokenExpiresAt(null);
            $this->users->save($user);
            $this->unitOfWork->commit();

            throw new \RuntimeException('Le lien de réinitialisation a expiré.');
        }

        $user
            ->setPassword($this->passwordHasher->hashPassword($user, $plainPassword))
            ->setPasswordResetToken(null)
            ->setPasswordResetTokenExpiresAt(null);

        $this->users->save($user);
        $this->unitOfWork->commit();
    }
}
