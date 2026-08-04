<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Notification\Service;

use App\Module\Notification\Application\Service\CommunicationPreferences;
use PHPUnit\Framework\TestCase;

final class CommunicationPreferencesTest extends TestCase
{
    public function testAllowedReturnsTheWhitelistedValues(): void
    {
        self::assertSame(
            ['notification', 'email', 'news_email', 'phone'],
            CommunicationPreferences::allowed(),
        );
    }

    public function testNormalizeFiltersUnknownValuesAndDeduplicates(): void
    {
        self::assertSame(
            ['email', 'phone'],
            CommunicationPreferences::normalize(['email', 'phone', 'email', 'sms', 12]),
        );

        self::assertSame([], CommunicationPreferences::normalize('email'));
    }

    public function testChoicesExposeAllCommunicationChannels(): void
    {
        $choices = CommunicationPreferences::choices();

        self::assertCount(4, $choices);
        self::assertSame('notification', $choices[0]['value']);
        self::assertSame('email', $choices[1]['value']);
        self::assertSame('news_email', $choices[2]['value']);
        self::assertSame('phone', $choices[3]['value']);
    }
}
