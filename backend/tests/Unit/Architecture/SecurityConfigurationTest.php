<?php

declare(strict_types=1);

namespace App\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class SecurityConfigurationTest extends TestCase
{
    public function testPrivateApiControllersDeclareBackendAccessControl(): void
    {
        $violations = [];

        foreach ($this->controllerSources() as $path => $source) {
            if (!str_contains($source, "#[Route('/api/")) {
                continue;
            }

            if (
                str_contains($path, '/PublicApi/')
                || str_contains($source, "#[Route('/api/public/")
                || str_contains($path, '/ContactController.php')
                || str_contains($path, '/HealthController.php')
                || str_contains($path, '/ProfileController.php')
                || str_contains($path, '/LogoutController.php')
                || str_contains($path, '/RegisterController.php')
                || str_contains($path, '/VerifyAccountController.php')
                || str_contains($path, '/RequestPasswordResetController.php')
                || str_contains($path, '/ResetPasswordController.php')
                || str_contains($path, '/RefreshTokenController.php')
                || str_contains($path, '/StripeWebhookController.php')
            ) {
                continue;
            }

            if (!str_contains($source, '#[IsGranted(')) {
                $violations[] = $path;
            }
        }

        self::assertSame([], $violations);
    }

    public function testClientResourceControllersScopeAccessToTheAuthenticatedUser(): void
    {
        $expectations = [
            'src/Module/Appointment/UI/Controller/Client/UpdateAppointmentStatusController.php' => ['AppointmentAccessPolicy'],
            'src/Module/Audit/UI/Controller/Client/GeneratePdfController.php' => ['AuditAccessPolicy'],
            'src/Module/Audit/UI/Controller/Client/ListMyAuditsController.php' => ['findByUser($user)'],
            'src/Module/Audit/UI/Controller/Client/ShowMyAuditController.php' => ['AuditAccessPolicy'],
            'src/Module/BetaTest/UI/Controller/CreateBugReportCommentController.php' => ['BugReportAccessPolicy'],
            'src/Module/BetaTest/UI/Controller/DownloadBugReportAttachmentController.php' => ['BugReportAccessPolicy'],
            'src/Module/BetaTest/UI/Controller/ListBugReportCommentsController.php' => ['BugReportAccessPolicy'],
            'src/Module/BetaTest/UI/Controller/ListMyBugReportsController.php' => ['findForUserPaginated($user'],
            'src/Module/BetaTest/UI/Controller/ShowBugReportController.php' => ['BugReportAccessPolicy'],
            'src/Module/Order/UI/Controller/CancelMyOrderController.php' => ['OrderAccessPolicy'],
            'src/Module/Order/UI/Controller/CheckoutExistingOrderController.php' => ['OrderAccessPolicy', 'findOneForUser($addressId, $user)'],
            'src/Module/Order/UI/Controller/CheckoutSessionStatusController.php' => ['OrderAccessPolicy'],
            'src/Module/Order/UI/Controller/DownloadMyOrderInvoicePdfController.php' => ['OrderAccessPolicy'],
            'src/Module/Order/UI/Controller/DownloadMyOrderInvoiceXmlController.php' => ['OrderAccessPolicy'],
            'src/Module/Order/UI/Controller/ListMyOrdersController.php' => ['findByUser($user)'],
            'src/Module/Order/UI/Controller/ShowOrderController.php' => ['OrderAccessPolicy'],
            'src/Module/Quote/UI/Controller/Client/AcceptMyQuoteController.php' => ['QuoteAccessPolicy'],
            'src/Module/Quote/UI/Controller/Client/DeleteMyQuoteController.php' => ['QuoteAccessPolicy'],
            'src/Module/Quote/UI/Controller/Client/GenerateMyQuotePdfController.php' => ['QuoteAccessPolicy'],
            'src/Module/Quote/UI/Controller/Client/GetMyQuoteController.php' => ['QuoteAccessPolicy'],
            'src/Module/Quote/UI/Controller/Client/ListMyQuotesController.php' => ['findByCustomerEmail($user->getEmail())'],
            'src/Module/Quote/UI/Controller/Client/RefuseMyQuoteController.php' => ['QuoteAccessPolicy'],
            'src/Module/Rating/UI/Controller/CreateProductReviewController.php' => ['OrderAccessPolicy'],
            'src/Module/TradeIn/UI/Controller/DownloadMyTradeInReceiptController.php' => ['TradeInAccessPolicy'],
            'src/Module/TradeIn/UI/Controller/ListMyTradeInsController.php' => ['findByUser($user)'],
            'src/Module/TradeIn/UI/Controller/RespondToTradeInOfferController.php' => ['TradeInAccessPolicy'],
            'src/Module/Training/UI/Controller/Client/ListMyTrainingEnrollmentsController.php' => ['findForUser($user)'],
            'src/Module/User/UI/Controller/Address/DeleteAddressController.php' => ['findOneForUser($id, $user)'],
            'src/Module/User/UI/Controller/Address/ListMyAddressesController.php' => ['findAllForUser($user)'],
            'src/Module/User/UI/Controller/Address/SetDefaultAddressController.php' => ['findOneForUser($id, $user)'],
            'src/Module/User/UI/Controller/Address/UpdateAddressController.php' => ['findOneForUser($id, $user)'],
        ];

        $missing = [];
        foreach ($expectations as $relativePath => $needles) {
            $source = file_get_contents(__DIR__.'/../../../'.$relativePath);
            self::assertIsString($source);
            foreach ($needles as $needle) {
                if (!str_contains($source, $needle)) {
                    $missing[] = $relativePath.' missing '.$needle;
                }
            }
        }

        self::assertSame([], $missing);
    }

    public function testRuntimeSecurityConfigurationIsExplicitlyHardened(): void
    {
        $framework = Yaml::parseFile(__DIR__.'/../../../config/packages/framework.yaml');
        $security = Yaml::parseFile(__DIR__.'/../../../config/packages/security.yaml');
        $cors = Yaml::parseFile(__DIR__.'/../../../config/packages/nelmio_cors.yaml');
        $rateLimiter = Yaml::parseFile(__DIR__.'/../../../config/packages/rate_limiter.yaml');
        $jwt = Yaml::parseFile(__DIR__.'/../../../config/packages/lexik_jwt_authentication.yaml');
        $doctrine = Yaml::parseFile(__DIR__.'/../../../config/packages/doctrine.yaml');

        self::assertContains('ROLE_USER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_ORDERS_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_MARKETING_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);

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
        self::assertSame(['%env(CORS_ALLOW_ORIGIN)%'], $cors['nelmio_cors']['paths']['^/api/']['allow_origin']);
        self::assertTrue($cors['nelmio_cors']['paths']['^/api/']['allow_credentials']);
        self::assertContains('OPTIONS', $cors['nelmio_cors']['paths']['^/api/']['allow_methods']);

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
        self::assertStringContainsString('clearCookie(AuthCookiePort::ACCESS_COOKIE, \'/api\', null, $secure, true', $source);
        self::assertStringContainsString('$secure,', $source);
        self::assertStringContainsString('true,', $source);
    }

    public function testProductionSecurityHeadersAndDeploymentChecksAreDocumented(): void
    {
        $headers = file_get_contents(__DIR__.'/../../../../deploy/nginx/frontend-security-headers.conf');
        $productionCheck = file_get_contents(__DIR__.'/../../../../tools/production_check.sh');
        self::assertIsString($headers);
        self::assertIsString($productionCheck);

        self::assertStringContainsString('Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"', $headers);
        self::assertStringContainsString('Content-Security-Policy', $headers);
        self::assertStringContainsString("frame-ancestors 'none'", $headers);
        self::assertStringContainsString('X-Content-Type-Options "nosniff"', $headers);

        foreach (['APP_ENV prod', 'APP_DEBUG', 'APP_SECRET', 'TRUSTED_HOSTS', 'TRUSTED_PROXIES', 'CORS_ALLOW_ORIGIN', 'STRIPE_SECRET_KEY'] as $requiredCheck) {
            self::assertStringContainsString($requiredCheck, $productionCheck);
        }
        self::assertStringContainsString("forbid_env_pattern CORS_ALLOW_ORIGIN 'localhost|127\\.0\\.0\\.1|\\.\\*|\\*'", $productionCheck);
        self::assertStringContainsString("require_env_pattern APP_FRONTEND_URL '^https://'", $productionCheck);
        self::assertStringContainsString('require_command clamscan', $productionCheck);
        self::assertStringContainsString('upload_max_filesize', $productionCheck);
        self::assertStringContainsString('post_max_size', $productionCheck);
        self::assertStringContainsString('public/uploads/invoices', $productionCheck);
        self::assertStringContainsString('var/private', $productionCheck);
    }

    public function testPrivateDocumentsAndUploadLimitsAreGuardedInCode(): void
    {
        $tradeInStorage = file_get_contents(__DIR__.'/../../../src/Module/TradeIn/Application/Storage/TradeInPrivateFileStorage.php');
        $invoiceStorageCommand = file_get_contents(__DIR__.'/../../../src/Module/Order/Infrastructure/Command/SecureInvoiceStorageCommand.php');
        $jsonPayload = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Http/JsonPayload.php');
        self::assertIsString($tradeInStorage);
        self::assertIsString($invoiceStorageCommand);
        self::assertIsString($jsonPayload);

        self::assertStringContainsString('private const MAX_RIB_BYTES = 5_242_880', $tradeInStorage);
        self::assertStringContainsString('var/private/trade-ins', $tradeInStorage);
        self::assertStringContainsString('0600', $tradeInStorage);
        self::assertStringContainsString('resolvePrivateDocumentPath', $tradeInStorage);
        self::assertStringContainsString('var/private/invoices', $invoiceStorageCommand);
        self::assertStringContainsString('private/invoices/', $invoiceStorageCommand);
        self::assertStringContainsString('private const MAX_BYTES = 1_048_576', $jsonPayload);
    }

    /** @return array<string, string> */
    private function controllerSources(): array
    {
        $root = realpath(__DIR__.'/../../../src/Module');
        self::assertIsString($root);

        $sources = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), 'Controller.php')) {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            self::assertIsString($source);
            $sources[$file->getPathname()] = $source;
        }

        return $sources;
    }
}
