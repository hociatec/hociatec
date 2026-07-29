<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Http;

use App\Shared\Http\RoundcubeCredentialsProvider;
use PHPUnit\Framework\TestCase;

final class RoundcubeCredentialsProviderTest extends TestCase
{
    public function testProvideUsesExplicitOvhCredentialsFirst(): void
    {
        $provider = new RoundcubeCredentialsProvider('ovh@example.com', 'secret', 'smtp://ignored:ignored@example.com');

        self::assertSame([
            'email' => 'ovh@example.com',
            'password' => 'secret',
        ], $provider->provide());
    }

    public function testProvideFallsBackToDecodedMailerDsnCredentials(): void
    {
        $provider = new RoundcubeCredentialsProvider('', '', 'smtp://user%2Bbox%40example.com:p%40ss%2Fword@mail.example.com');

        self::assertSame([
            'email' => 'user+box@example.com',
            'password' => 'p@ss/word',
        ], $provider->provide());
    }

    public function testProvideReturnsNullWhenConfigurationIsIncomplete(): void
    {
        self::assertNull((new RoundcubeCredentialsProvider('', '', ''))->provide());
        self::assertNull((new RoundcubeCredentialsProvider('', '', 'not a valid dsn'))->provide());
        self::assertNull((new RoundcubeCredentialsProvider('', '', '://'))->provide());
        self::assertNull((new RoundcubeCredentialsProvider('', '', 'smtp://useronly@mail.example.com'))->provide());
    }
}
