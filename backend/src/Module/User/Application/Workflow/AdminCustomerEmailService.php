<?php

declare(strict_types=1);

namespace App\Module\User\Application\Workflow;

use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Support\Application\Port\SupportCustomerMessengerPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\Mail\EmailSender;
use App\Shared\Infrastructure\Mail\EmailHeaderSanitizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class AdminCustomerEmailService implements SupportCustomerMessengerPort
{
    public function __construct(
        private readonly EmailSender $mailer,
        private readonly LoggerInterface $logger,
        private readonly UserCommunicationNotifier $userNotifications,
        private readonly string $mailerFrom,
    ) {
    }

    public function send(User $user, string $subject, string $message): void
    {
        $subject = EmailHeaderSanitizer::subject($subject);
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
                ->to(new Address($user->getEmail(), EmailHeaderSanitizer::displayName($user->getFullName())))
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
