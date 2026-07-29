<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Notification\Service;

use App\Module\Notification\Service\AccountNotificationFormatter;
use PHPUnit\Framework\TestCase;

final class AccountNotificationFormatterTest extends TestCase
{
    public function testComputedNotificationSanitizesTargetAndFormatsDate(): void
    {
        $formatter = new AccountNotificationFormatter();
        $createdAt = new \DateTimeImmutable('2026-07-29 15:45:00+00:00');

        $notification = $formatter->computedNotification(
            'key-1',
            'Titre',
            'Message',
            'https://evil.example.com',
            'info',
            $createdAt,
        );

        self::assertSame(
            [
                'key' => 'key-1',
                'label' => 'Titre',
                'message' => 'Message',
                'to' => '/mon-espace',
                'type' => 'info',
                'createdAt' => '2026-07-29T15:45:00+00:00',
            ],
            $notification,
        );
    }

    public function testSafeInternalTargetAcceptsOnlySingleSlashInternalPaths(): void
    {
        $formatter = new AccountNotificationFormatter();

        self::assertSame('/mon-espace', $formatter->safeInternalTarget(''));
        self::assertSame('/mon-espace', $formatter->safeInternalTarget('https://evil.example.com'));
        self::assertSame('/mon-espace', $formatter->safeInternalTarget('//evil'));
        self::assertSame('/mon-espace/commandes', $formatter->safeInternalTarget('/mon-espace/commandes'));
        self::assertSame('29/07/2026 15:45', $formatter->formatFrenchDateTime(new \DateTimeImmutable('2026-07-29 15:45:00')));
    }
}
