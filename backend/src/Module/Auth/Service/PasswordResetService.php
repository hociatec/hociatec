<?php

declare(strict_types=1);

namespace App\Module\Auth\Service;

use App\Module\Marketing\Service\EmailTemplateRenderer;
use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use Psr\Log\LoggerInterface;
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
        private readonly EmailTemplateRenderer $emailTemplates,
        private readonly LoggerInterface $logger,
        private readonly string $frontendUrl,
        private readonly string $mailerFrom,
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

        $this->users->save($user, true);

        $resetLink = rtrim($this->frontendUrl, '/').'/reset-password/'.$token;

        $content = $this->emailTemplates->renderScenario('password_reset', [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'password_reset_url' => $resetLink,
            'password_reset_expires_in' => '1 heure',
            'app_frontend_url' => rtrim($this->frontendUrl, '/'),
        ], [
            'subject' => 'Réinitialisez votre mot de passe Hociatec',
            'html' => '<p>Bonjour {{first_name}},</p><p>Une demande de réinitialisation de mot de passe a été reçue pour votre compte Hociatec.</p><p>Le lien ci-dessous vous permet de définir un nouveau mot de passe. Il reste valide pendant {{password_reset_expires_in}}.</p><p><a href="{{password_reset_url}}">Réinitialiser mon mot de passe</a></p><p>Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail.</p>',
            'text' => "Bonjour {{first_name}},\n\nUne demande de réinitialisation de mot de passe a été reçue pour votre compte Hociatec.\nPour définir un nouveau mot de passe, ouvrez ce lien dans l'heure :\n{{password_reset_url}}\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez simplement cet e-mail.",
        ]);

        try {
            $emailMessage = (new Email())
                ->from(new Address($this->mailerFrom, 'Hociatec'))
                ->to(new Address($user->getEmail(), $user->getFullName()))
                ->subject($content['subject'])
                ->html($content['html'])
                ->text($content['text']);

            $this->mailer->send($emailMessage);
        } catch (\RuntimeException $exception) {
            $this->logger->warning('Password reset email send failed.', [
                'userId' => $user->getId(),
                'exception' => $exception,
            ]);
            // Ne divulgue pas l'état du compte ni les erreurs d'envoi.
        }
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
