<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Mail;

use App\Shared\Infrastructure\Mail\SymfonyEmailSender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class SymfonyEmailSenderTest extends TestCase
{
    public function testItTemporarilyOverridesSocketTimeoutDuringSend(): void
    {
        $previous = ini_get('default_socket_timeout');
        self::assertNotFalse($previous);
        ini_set('default_socket_timeout', '60');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::isInstanceOf(Email::class))
            ->willReturnCallback(static function (): void {
                self::assertSame('8', ini_get('default_socket_timeout'));
            });

        try {
            (new SymfonyEmailSender($mailer, 7.5))->send(new Email());
            self::assertSame('60', ini_get('default_socket_timeout'));
        } finally {
            ini_set('default_socket_timeout', $previous);
        }
    }

    public function testItSkipsOverrideWhenTimeoutIsDisabled(): void
    {
        $previous = ini_get('default_socket_timeout');
        self::assertNotFalse($previous);
        ini_set('default_socket_timeout', '42');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function (): void {
                self::assertSame('42', ini_get('default_socket_timeout'));
            });

        try {
            (new SymfonyEmailSender($mailer, 0.0))->send(new Email());
            self::assertSame('42', ini_get('default_socket_timeout'));
        } finally {
            ini_set('default_socket_timeout', $previous);
        }
    }
}
