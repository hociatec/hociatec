<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Mail;

use App\Shared\Application\Mail\EmailSender;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class SymfonyEmailSender implements EmailSender
{
    public function __construct(
        private MailerInterface $mailer,
        private float $timeoutSeconds = 10.0,
    )
    {
    }

    public function send(Email $email): void
    {
        if ($this->timeoutSeconds <= 0) {
            $this->mailer->send($email);

            return;
        }

        $previousTimeout = ini_get('default_socket_timeout');
        $timeout = (string) max(1, (int) ceil($this->timeoutSeconds));
        ini_set('default_socket_timeout', $timeout);

        try {
            $this->mailer->send($email);
        } finally {
            if (false !== $previousTimeout) {
                ini_set('default_socket_timeout', $previousTimeout);
            }
        }
    }
}
