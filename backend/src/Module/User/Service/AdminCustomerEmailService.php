<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\User\Entity\User;
use App\Shared\Http\OvhRoundcubeMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class AdminCustomerEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly OvhRoundcubeMailer $ovhRoundcubeMailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function send(User $user, string $subject, string $message): void
    {
        $subject = trim($subject);
        $message = trim($message);

        if ($subject === '' || $message === '') {
            throw new \InvalidArgumentException('Sujet et message sont obligatoires.');
        }

        $from = $_ENV['MAILER_FROM'] ?? 'no-reply@localhost';

        try {
            $this->ovhRoundcubeMailer->send($user->getEmail(), $subject, $message);

            return;
        } catch (\Throwable $roundcubeException) {
            $this->logger->warning('Admin customer email Roundcube primary transport failed.', [
                'customerId' => $user->getId(),
                'email' => $user->getEmail(),
                'exception' => $roundcubeException,
            ]);
        }

        try {
            $email = (new Email())
                ->from(new Address($from, 'Hociatec'))
                ->to(new Address($user->getEmail(), $user->getFullName()))
                ->subject($subject)
                ->text($message)
                ->html(nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));

            $this->mailer->send($email);
        } catch (\Throwable $smtpException) {
            $this->logger->error('Admin customer email send failed.', [
                'customerId' => $user->getId(),
                'email' => $user->getEmail(),
                'exception' => $smtpException,
            ]);

            throw new \RuntimeException('Envoi impossible pour le moment. Vérifie la configuration email SMTP ou OVH.');
        }
    }
}
