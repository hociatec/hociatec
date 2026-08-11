<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Notification;

use App\Module\Notification\Application\Message\UserCommunicationEmailMessage;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\Mail\EmailHeaderSanitizer;
use App\Shared\Application\Mail\EmailSender;
use App\Shared\Application\Messaging\AsyncMessageDispatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class UserCommunicationEmailSender
{
    public function __construct(
        private EmailSender $mailer,
        private AsyncMessageDispatcher $bus,
        private LoggerInterface $logger,
        private string $mailerFrom,
        private string $frontendUrl,
    ) {
    }

    public function dispatch(User $user, string $title, string $message, string $targetUrl, string $type): void
    {
        $userId = $user->getId();
        if (null === $userId) {
            return;
        }

        try {
            $this->bus->dispatch(new UserCommunicationEmailMessage($userId, $title, $message, $targetUrl, $type));
        } catch (\RuntimeException $exception) {
            $this->logger->warning('Communication email dispatch failed.', [
                'userId' => $userId,
                'type' => $type,
                'exception' => $exception,
            ]);
        }
    }

    public function sendNow(User $user, string $title, string $message, string $targetUrl, string $type): void
    {
        $absoluteUrl = rtrim($this->frontendUrl, '/').$targetUrl;
        $linkLabel = $this->linkLabel($targetUrl, $type);
        $text = $message."\n\n".$linkLabel.' : '.$absoluteUrl."\n\nHociatec";
        $html = sprintf(
            '<p>%s</p><p><a href="%s">%s</a></p><p>%s</p><p>Hociatec</p>',
            nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            htmlspecialchars($absoluteUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($linkLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($absoluteUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to($user->getEmail())
            ->subject(EmailHeaderSanitizer::subject($title))
            ->text($text)
            ->html($html);

        try {
            $this->mailer->send($email);
        } catch (\RuntimeException $exception) {
            $this->logger->warning('Communication email send failed.', [
                'userId' => $user->getId(),
                'type' => $type,
                'exception' => $exception,
            ]);
        }
    }

    private function linkLabel(string $targetUrl, string $type): string
    {
        if (str_starts_with($type, 'beta_') || str_starts_with($targetUrl, '/beta')) {
            return 'Accéder à l’espace bêta';
        }

        if ('news_article' === $type || str_starts_with($targetUrl, '/actualites')) {
            return 'Lire l’actualité';
        }

        return 'Consulter le suivi';
    }
}
