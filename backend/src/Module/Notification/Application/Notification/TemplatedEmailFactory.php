<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Notification;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class TemplatedEmailFactory
{
    public static function create(string $mailerFrom, string $senderName, string $toEmail, string $toName, string $subject, string $html, string $text): Email
    {
        $toName = trim($toName);

        return (new Email())
            ->from(new Address($mailerFrom, $senderName))
            ->to(new Address($toEmail, '' !== $toName ? $toName : $toEmail))
            ->subject($subject)
            ->html($html)
            ->text($text);
    }

    public static function createWithReplyTo(
        string $mailerFrom,
        string $senderName,
        string $toEmail,
        string $toName,
        string $replyToEmail,
        string $replyToName,
        string $subject,
        string $html,
        string $text,
    ): Email {
        $email = self::create($mailerFrom, $senderName, $toEmail, $toName, $subject, $html, $text);
        $email->replyTo(new Address($replyToEmail, $replyToName));

        return $email;
    }
}
