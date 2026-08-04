<?php

declare(strict_types=1);

namespace App\Module\Auth\Service;

use App\Module\Marketing\Service\EmailTemplateRenderer;
use App\Module\User\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;

final readonly class PasswordResetEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private EmailTemplateRenderer $emailTemplates,
        private LoggerInterface $logger,
        private string $frontendUrl,
        private string $mailerFrom,
    ) {
    }

    public function send(User $user, string $token): void
    {
        $frontendUrl = rtrim($this->frontendUrl, '/');
        $resetLink = $frontendUrl.'/reset-password/'.$token;

        $content = $this->emailTemplates->renderScenario('password_reset', [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'password_reset_url' => $resetLink,
            'password_reset_expires_in' => '1 heure',
            'app_frontend_url' => $frontendUrl,
        ], [
            'subject' => 'Réinitialisez votre mot de passe Hociatec',
            'html' => '<p>Bonjour {{first_name}},</p><p>Une demande de réinitialisation de mot de passe a été reçue pour votre compte Hociatec.</p><p>Le lien ci-dessous vous permet de définir un nouveau mot de passe. Il reste valide pendant {{password_reset_expires_in}}.</p><p><a href="{{password_reset_url}}">Réinitialiser mon mot de passe</a></p><p>Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail.</p>',
            'text' => "Bonjour {{first_name}},\n\nUne demande de réinitialisation de mot de passe a été reçue pour votre compte Hociatec.\nPour définir un nouveau mot de passe, ouvrez ce lien dans l'heure :\n{{password_reset_url}}\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez simplement cet e-mail.",
        ]);

        try {
            $email = (new Email())
                ->from(new Address($this->mailerFrom, 'Hociatec'))
                ->to(new Address($user->getEmail(), $user->getFullName()))
                ->subject($content['subject'])
                ->html($content['html'])
                ->text($content['text']);

            $this->mailer->send($email);
        } catch (\RuntimeException $exception) {
            $this->logger->warning('Password reset email send failed.', [
                'userId' => $user->getId(),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }
}
