<?php

declare(strict_types=1);

namespace App\Module\Auth\Service;

use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use App\Shared\Http\OvhRoundcubeMailer;
use DateTimeImmutable;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly OvhRoundcubeMailer $ovhRoundcubeMailer,
    ) {
    }

    public function request(string $email): void
    {
        $user = $this->users->findOneByEmailInsensitive($email);
        if (!$user instanceof User) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = new DateTimeImmutable('+1 hour');

        $user
            ->setPasswordResetToken($token)
            ->setPasswordResetTokenExpiresAt($expiresAt);

        $this->users->save($user, true);

        $frontendUrl = $_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173';
        $resetLink = rtrim($frontendUrl, '/') . '/reset-password/' . $token;
        $from = $_ENV['MAILER_FROM'] ?? 'no-reply@localhost';

        try {
            $plainMessage =
                "Bonjour {$user->getFirstName()},\n\n" .
                "Une demande de réinitialisation de mot de passe a été reçue pour votre compte Hociatec.\n" .
                "Pour définir un nouveau mot de passe, ouvrez ce lien dans l'heure :\n" .
                $resetLink . "\n\n" .
                "Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet e-mail.\n";

            $this->ovhRoundcubeMailer->send(
                $user->getEmail(),
                'Réinitialisez votre mot de passe Hociatec',
                $plainMessage,
            );
        } catch (\Throwable) {
            try {
                $emailMessage = (new Email())
                    ->from(new Address($from, 'Hociatec'))
                    ->to(new Address($user->getEmail(), $user->getFullName()))
                    ->subject('Réinitialisez votre mot de passe Hociatec')
                    ->html(
                        '<p>Bonjour ' . htmlspecialchars($user->getFirstName()) . ',</p>' .
                        '<p>Une demande de réinitialisation de mot de passe a été reçue pour votre compte Hociatec.</p>' .
                        '<p>Le lien ci-dessous vous permet de définir un nouveau mot de passe. Il reste valide pendant 1 heure.</p>' .
                        '<p><a href="' . htmlspecialchars($resetLink) . '">Réinitialiser mon mot de passe</a></p>' .
                        '<p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez simplement cet e-mail.</p>'
                    );

                $this->mailer->send($emailMessage);
            } catch (\Throwable) {
                // Ne divulgue pas l'état du compte ni les erreurs d'envoi.
            }
        }
    }

    public function reset(string $token, string $plainPassword): void
    {
        $user = $this->users->findOneByPasswordResetToken($token);
        if (!$user instanceof User) {
            throw new \RuntimeException('Lien de réinitialisation invalide.');
        }

        $expiresAt = $user->getPasswordResetTokenExpiresAt();
        if ($expiresAt === null || $expiresAt < new DateTimeImmutable()) {
            $user
                ->setPasswordResetToken(null)
                ->setPasswordResetTokenExpiresAt(null);
            $this->users->save($user, true);

            throw new \RuntimeException('Le lien de réinitialisation a expiré.');
        }

        $user
            ->setPassword($this->passwordHasher->hashPassword($user, $plainPassword))
            ->setPasswordResetToken(null)
            ->setPasswordResetTokenExpiresAt(null);

        $this->users->save($user, true);
    }
}
