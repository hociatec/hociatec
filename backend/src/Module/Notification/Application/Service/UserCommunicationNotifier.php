<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Service;

use App\Module\Notification\Application\Message\UserCommunicationEmailMessage;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class UserCommunicationNotifier
{
    public function __construct(
        private AccountNotificationEventRepository $notifications,
        private DoctrineUnitOfWork $persistence,
        private MailerInterface $mailer,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
        private string $mailerFrom,
        private string $frontendUrl,
    ) {
    }

    public function notify(User $user, string $key, string $title, string $message, string $targetUrl, string $type): void
    {
        if ($this->notifications->existsForKey($key)) {
            return;
        }

        $this->notifyInternal($user, $key, $title, $message, $targetUrl, $type);

        if ($this->shouldSendEmail($user)) {
            $this->dispatchEmail($user, $title, $message, $targetUrl, $type);
        }
    }

    public function notifyInternal(User $user, string $key, string $title, string $message, string $targetUrl, string $type): void
    {
        try {
            if (!in_array(CommunicationPreferences::NOTIFICATION, $user->getCommunicationPreferences(), true)) {
                return;
            }

            if ($this->notifications->existsForKey($key)) {
                return;
            }

            $this->persistence->persist(new AccountNotificationEvent($user, $key, $title, $message, $targetUrl, $type));
            $this->persistence->commit();
        } catch (\RuntimeException|\InvalidArgumentException $exception) {
            $this->logger->warning('Internal account notification failed.', [
                'userId' => $user->getId(),
                'key' => $key,
                'type' => $type,
                'exception' => $exception,
            ]);
        }
    }

    public function shouldSendEmail(User $user): bool
    {
        return in_array(CommunicationPreferences::EMAIL, $user->getCommunicationPreferences(), true);
    }

    public function shouldSendNewsEmail(User $user): bool
    {
        return in_array(CommunicationPreferences::NEWS_EMAIL, $user->getCommunicationPreferences(), true);
    }

    public function sendEmailNow(User $user, string $title, string $message, string $targetUrl, string $type): void
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
            ->subject($title)
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

    private function dispatchEmail(User $user, string $title, string $message, string $targetUrl, string $type): void
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
