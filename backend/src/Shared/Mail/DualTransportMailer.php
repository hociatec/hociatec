<?php

declare(strict_types=1);

namespace App\Shared\Mail;

use App\Shared\Http\OvhRoundcubeMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class DualTransportMailer
{
    public function __construct(
        private OvhRoundcubeMailer $primary,
        private MailerInterface $fallback,
        private LoggerInterface $logger,
    ) {
    }

    public function send(
        string $to,
        string $subject,
        string $text,
        Email $fallbackEmail,
        string $context,
        ?string $replyTo = null,
    ): void {
        try {
            $this->primary->send($to, $subject, $text, $replyTo);

            return;
        } catch (\Throwable $exception) {
            $this->logger->warning('Primary email transport failed.', [
                'context' => $context,
                'exception' => $exception,
            ]);
        }

        try {
            $this->fallback->send($fallbackEmail);
        } catch (\Throwable $exception) {
            $this->logger->error('Fallback email transport failed.', [
                'context' => $context,
                'exception' => $exception,
            ]);

            throw MailDeliveryException::failed($context, $exception);
        }
    }
}
