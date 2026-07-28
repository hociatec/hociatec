<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\User\Entity\AccountNotificationEvent;
use App\Module\User\Entity\User;
use App\Module\User\Repository\AccountNotificationEventRepository;
use App\Shared\Mail\DualTransportMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class UserCommunicationNotifier
{
    public function __construct(
        private AccountNotificationEventRepository $notifications,
        private UserPersistence $persistence,
        private DualTransportMailer $mailer,
        private LoggerInterface $logger,
        private string $mailerFrom,
        private string $frontendUrl,
    ) {
    }

    public function notify(User $user, string $key, string $title, string $message, string $targetUrl, string $type): void
    {
        $preferences = $user->getCommunicationPreferences();

        if (in_array(CommunicationPreferences::NOTIFICATION, $preferences, true) && !$this->notifications->existsForKey($key)) {
            $this->persistence->persist(new AccountNotificationEvent($user, $key, $title, $message, $targetUrl, $type));
        }

        $this->persistence->flush();

        if (in_array(CommunicationPreferences::EMAIL, $preferences, true)) {
            $this->sendEmail($user, $title, $message, $targetUrl, $type);
        }
    }

    private function sendEmail(User $user, string $title, string $message, string $targetUrl, string $type): void
    {
        $absoluteUrl = rtrim($this->frontendUrl, '/').$targetUrl;
        $text = $message."\n\nConsulter le suivi : ".$absoluteUrl."\n\nHociatec";
        $html = sprintf(
            '<p>%s</p><p><a href="%s">Consulter le suivi</a></p><p>Hociatec</p>',
            nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            htmlspecialchars($absoluteUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to($user->getEmail())
            ->subject($title)
            ->text($text)
            ->html($html);

        try {
            $this->mailer->send($user->getEmail(), $title, $text, $email, $type);
        } catch (\Throwable $exception) {
            $this->logger->warning('Communication preference email notification failed.', [
                'userId' => $user->getId(),
                'type' => $type,
                'exception' => $exception,
            ]);
        }
    }
}
