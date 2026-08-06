<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Module\Notification\Application\Notification\CommunicationPreferencePolicy;
use App\Module\Notification\Application\Notification\InternalAccountNotificationSender;
use App\Module\Notification\Application\Notification\UserCommunicationEmailSender;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Notification\Application\Port\AccountNotificationEventRepositoryPort;
use App\Shared\Application\Mail\EmailSender;
use App\Shared\Application\Messaging\AsyncMessageDispatcher;
use App\Shared\Application\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;

final class UserCommunicationNotifierFactory
{
    private function __construct()
    {
    }

    public static function create(TestCase $test, mixed ...$args): UserCommunicationNotifier
    {
        if (($args[1] ?? null) instanceof CommunicationPreferencePolicy) {
            return new UserCommunicationNotifier($args[0], $args[1], $args[2], $args[3]);
        }

        $repository = $args[0];
        $persistence = $args[1];
        $mailer = $args[2];
        $bus = $args[3];
        $logger = $args[4];
        $mailerFrom = $args[5];
        $frontendUrl = $args[6];

        if (!$repository instanceof AccountNotificationEventRepositoryPort || !$persistence instanceof UnitOfWork || !$logger instanceof LoggerInterface) {
            throw new \InvalidArgumentException('Invalid UserCommunicationNotifier test factory arguments.');
        }

        $preferences = new CommunicationPreferencePolicy();

        return new UserCommunicationNotifier(
            $repository,
            $preferences,
            new InternalAccountNotificationSender($repository, $persistence, $preferences, $logger),
            new UserCommunicationEmailSender(
                $mailer instanceof EmailSender ? $mailer : self::emailSender($mailer),
                $bus instanceof AsyncMessageDispatcher ? $bus : self::messageDispatcher($bus),
                $logger,
                (string) $mailerFrom,
                (string) $frontendUrl,
            ),
        );
    }

    private static function emailSender(object $mailer): EmailSender
    {
        return new readonly class($mailer) implements EmailSender {
            public function __construct(private object $mailer)
            {
            }

            public function send(Email $email): void
            {
                $this->mailer->send($email);
            }
        };
    }

    private static function messageDispatcher(object $bus): AsyncMessageDispatcher
    {
        return new readonly class($bus) implements AsyncMessageDispatcher {
            public function __construct(private object $bus)
            {
            }

            public function dispatch(object $message): void
            {
                $this->bus->dispatch($message);
            }
        };
    }
}
