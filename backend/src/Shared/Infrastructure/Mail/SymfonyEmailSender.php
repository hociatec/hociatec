<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Mail;

use App\Shared\Application\Mail\EmailSender;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class SymfonyEmailSender implements EmailSender
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function send(Email $email): void
    {
        $this->mailer->send($email);
    }
}
