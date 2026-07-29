<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\Marketing\Service\EmailTemplateRenderer;
use App\Module\User\Entity\User;
use App\Module\User\Exception\ActivationEmailDeliveryException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class AccountActivationEmailService
{
    public function __construct(
        private EmailTemplateRenderer $emailTemplates,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $frontendUrl,
        private string $mailerFrom,
    ) {
    }

    public function sendActivationEmail(User $user, string $rawToken): void
    {
        $frontendUrl = rtrim($this->frontendUrl, '/');
        $verifyLink = $frontendUrl.'/activation/'.$rawToken;

        try {
            $content = $this->emailTemplates->renderScenario('user_account_activation', [
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'full_name' => $user->getFullName(),
                'email' => $user->getEmail(),
                'activation_url' => $verifyLink,
                'activation_expires_in' => '24 heures',
                'app_frontend_url' => $frontendUrl,
            ], [
                'subject' => 'Activez votre compte Hociatec',
                'html' => '<p>Bonjour {{first_name}},</p><p>Merci pour votre inscription. Pour activer votre compte, cliquez sur le lien ci-dessous, valide {{activation_expires_in}}.</p><p><a href="{{activation_url}}">Activer mon compte</a></p><p>Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail.</p>',
                'text' => "Bonjour {{first_name}},\n\nMerci pour votre inscription. Pour activer votre compte, ouvrez le lien ci-dessous dans les {{activation_expires_in}} :\n{{activation_url}}\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.",
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to render activation email.', [
                'exception' => $exception,
                'userId' => $user->getId(),
            ]);

            throw ActivationEmailDeliveryException::deliveryFailed($exception);
        }

        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject($content['subject'])
            ->html($content['html'])
            ->text($content['text']);

        try {
            $this->mailer->send($email);
        } catch (\Exception $exception) {
            $this->logger->warning('Account activation email send failed.', [
                'userId' => $user->getId(),
                'exception' => $exception,
            ]);
            throw ActivationEmailDeliveryException::deliveryFailed($exception);
        }
    }
}
