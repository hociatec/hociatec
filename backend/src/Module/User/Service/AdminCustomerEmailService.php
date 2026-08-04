<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\Notification\Service\UserCommunicationNotifier;
use App\Module\User\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class AdminCustomerEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly UserCommunicationNotifier $userNotifications,
        private readonly string $mailerFrom,
    ) {
    }

    public function send(User $user, string $subject, string $message): void
    {
        $subject = trim($subject);
        $message = trim($message);

        if ('' === $subject || '' === $message) {
            throw new \InvalidArgumentException('Sujet et message sont obligatoires.');
        }

        $this->userNotifications->notifyInternal(
            $user,
            'admin-customer-message:'.$user->getId().':'.hash('sha256', $subject."\n".$message."\n".microtime(true)),
            $subject,
            $message,
            '/mon-espace',
            'admin_customer_message',
        );

        if (!$this->userNotifications->shouldSendEmail($user)) {
            return;
        }

        try {
            $email = (new Email())
                ->from(new Address($this->mailerFrom, 'Hociatec'))
                ->to(new Address($user->getEmail(), $user->getFullName()))
                ->subject($subject)
                ->text($message)
                ->html(nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));

            $this->mailer->send($email);
        } catch (\RuntimeException $smtpException) {
            $this->logger->error('Admin customer email send failed.', [
                'customerId' => $user->getId(),
                'exception' => $smtpException,
            ]);

            throw new \RuntimeException('Envoi impossible pour le moment. Vérifie la configuration email SMTP.', previous: $smtpException);
        }
    }
}
