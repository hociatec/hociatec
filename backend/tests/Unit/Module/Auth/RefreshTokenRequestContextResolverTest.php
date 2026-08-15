<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth;

use App\Module\Auth\Infrastructure\Http\AccessSessionLocationResolver;
use App\Module\Auth\Infrastructure\Http\GeoIpAccessSessionLocationResolver;
use App\Module\Auth\Infrastructure\Http\HeaderAccessSessionLocationResolver;
use App\Module\Auth\Infrastructure\Http\RefreshTokenRequestContextResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RefreshTokenRequestContextResolverTest extends TestCase
{
    public function testUsesInfrastructureHeadersForLocation(): void
    {
        $resolver = new RefreshTokenRequestContextResolver(new HeaderAccessSessionLocationResolver());
        $request = Request::create('/', server: [
            'HTTP_CF_IPCITY' => 'Lyon',
            'HTTP_CF_REGION' => 'Auvergne-Rhone-Alpes',
            'HTTP_CF_IPCOUNTRY' => 'FR',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1.15',
            'REMOTE_ADDR' => '8.8.8.8',
        ]);

        $context = $resolver->resolve($request);

        self::assertSame('Lyon, Auvergne-Rhone-Alpes, FR', $context->locationLabel);
        self::assertSame('Safari', $context->clientLabel);
        self::assertSame('macOS', $context->platformLabel);
        self::assertSame('Mac', $context->deviceLabel);
        self::assertSame('8.8.8.8', $context->ipAddress);
    }

    public function testUsesInjectedFallbackLocationResolver(): void
    {
        $resolver = new RefreshTokenRequestContextResolver(new class implements AccessSessionLocationResolver {
            public function resolve(Request $request, ?string $clientIp): string
            {
                unset($request);

                return 'Paris, Ile-de-France, FR for '.$clientIp;
            }
        });

        $context = $resolver->resolve(Request::create('/', server: [
            'HTTP_X_HOCIATEC_CLIENT_APP' => 'Application iPhone',
            'HTTP_X_HOCIATEC_CLIENT_PLATFORM' => 'iOS 18.0',
            'HTTP_X_HOCIATEC_DEVICE_NAME' => 'iPhone de Test',
            'REMOTE_ADDR' => '1.1.1.1',
        ]));

        self::assertSame('Paris, Ile-de-France, FR for 1.1.1.1', $context->locationLabel);
        self::assertSame('Application iPhone', $context->clientLabel);
        self::assertSame('iOS 18.0', $context->platformLabel);
        self::assertSame('iPhone de Test', $context->deviceLabel);
    }

    public function testGeoIpResolverReturnsNullWithoutReadableDatabase(): void
    {
        $resolver = new GeoIpAccessSessionLocationResolver('/tmp/does-not-exist.mmdb');

        self::assertNull($resolver->resolve(Request::create('/'), '8.8.8.8'));
    }

    public function testGeoIpResolverSkipsPrivateAddresses(): void
    {
        $resolver = new GeoIpAccessSessionLocationResolver('/tmp/does-not-exist.mmdb');

        self::assertNull($resolver->resolve(Request::create('/'), '127.0.0.1'));
        self::assertNull($resolver->resolve(Request::create('/'), '10.0.0.4'));
    }
}
