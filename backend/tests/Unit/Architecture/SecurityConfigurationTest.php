<?php

declare(strict_types=1);

namespace App\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class SecurityConfigurationTest extends TestCase
{
    public function testRuntimeSecurityConfigurationIsExplicitlyHardened(): void
    {
        $framework = Yaml::parseFile(__DIR__.'/../../../config/packages/framework.yaml');
        $security = Yaml::parseFile(__DIR__.'/../../../config/packages/security.yaml');
        $cors = Yaml::parseFile(__DIR__.'/../../../config/packages/nelmio_cors.yaml');
        $rateLimiter = Yaml::parseFile(__DIR__.'/../../../config/packages/rate_limiter.yaml');
        $jwt = Yaml::parseFile(__DIR__.'/../../../config/packages/lexik_jwt_authentication.yaml');
        $doctrine = Yaml::parseFile(__DIR__.'/../../../config/packages/doctrine.yaml');

        self::assertSame('%env(APP_SECRET)%', $framework['framework']['secret']);
        self::assertSame('%env(TRUSTED_PROXIES)%', $framework['framework']['trusted_proxies']);
        self::assertSame('%env(TRUSTED_HOSTS)%', $framework['framework']['trusted_hosts']);
        self::assertFalse($framework['framework']['http_method_override']);
        self::assertSame('auto', $framework['framework']['session']['cookie_secure']);
        self::assertSame('lax', $framework['framework']['session']['cookie_samesite']);

        self::assertSame(5, $security['security']['firewalls']['api_login']['login_throttling']['max_attempts']);
        self::assertSame('15 minutes', $security['security']['firewalls']['api_login']['login_throttling']['interval']);
        self::assertTrue($security['security']['firewalls']['api']['stateless']);

        self::assertSame(['%env(CORS_ALLOW_ORIGIN)%'], $cors['nelmio_cors']['defaults']['allow_origin']);
        self::assertTrue($cors['nelmio_cors']['defaults']['allow_credentials']);
        self::assertContains('X-CSRF-Token', $cors['nelmio_cors']['defaults']['allow_headers']);
        self::assertContains('X-Request-Id', $cors['nelmio_cors']['defaults']['expose_headers']);

        foreach (['public_api', 'checkout', 'password_reset_request', 'auth_register', 'beta_report_create'] as $limiterName) {
            self::assertArrayHasKey($limiterName, $rateLimiter['framework']['rate_limiter']);
            self::assertGreaterThan(0, $rateLimiter['framework']['rate_limiter'][$limiterName]['limit']);
        }

        self::assertSame('%env(resolve:JWT_SECRET_KEY)%', $jwt['lexik_jwt_authentication']['secret_key']);
        self::assertSame('%env(resolve:JWT_PUBLIC_KEY)%', $jwt['lexik_jwt_authentication']['public_key']);
        self::assertSame('%env(JWT_PASSPHRASE)%', $jwt['lexik_jwt_authentication']['pass_phrase']);
        self::assertSame(3600, $jwt['lexik_jwt_authentication']['token_ttl']);
        self::assertFalse($jwt['lexik_jwt_authentication']['token_extractors']['authorization_header']['enabled']);
        self::assertTrue($jwt['lexik_jwt_authentication']['token_extractors']['cookie']['enabled']);

        self::assertSame('%env(resolve:DATABASE_URL)%', $doctrine['doctrine']['dbal']['url']);
        self::assertTrue($doctrine['doctrine']['dbal']['use_savepoints']);
    }

    public function testAuthenticationCookiesAreHttpOnlySameSiteAndSecureAware(): void
    {
        $source = file_get_contents(__DIR__.'/../../../src/Module/Auth/Infrastructure/Http/AuthCookieService.php');
        self::assertIsString($source);

        self::assertStringContainsString('Cookie::SAMESITE_LAX', $source);
        self::assertStringContainsString('clearCookie(self::ACCESS_COOKIE, \'/api\', null, $secure, true', $source);
        self::assertStringContainsString('$secure,', $source);
        self::assertStringContainsString('true,', $source);
    }
}
