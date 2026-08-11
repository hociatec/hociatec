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
            'src/Module/Appointment/UI/Controller/Client/UpdateAppointmentStatusController.php' => ['CustomerAppointmentPortalService', 'changeStatusForUser($this->currentUser(), $id, $input->status)'],
            'src/Module/Audit/UI/Controller/Client/GeneratePdfController.php' => ['CustomerAuditPortalService', 'currentUser()'],
            'src/Module/Audit/UI/Controller/Client/ListMyAuditsController.php' => ['CustomerAuditPortalService', 'listForUser($this->currentUser(),'],
            'src/Module/Audit/UI/Controller/Client/ShowMyAuditController.php' => ['CustomerAuditPortalService', 'showForUser($this->currentUser(),'],
            'src/Module/BetaTest/UI/Controller/CreateBugReportCommentController.php' => ['CustomerBugReportPortalService', 'createCommentForUser($user,'],
            'src/Module/BetaTest/UI/Controller/DownloadBugReportAttachmentController.php' => ['CustomerBugReportPortalService', 'attachmentPathForUser($user,'],
            'src/Module/BetaTest/UI/Controller/ListBugReportCommentsController.php' => ['CustomerBugReportPortalService', 'listCommentsForUser($user,'],
            'src/Module/BetaTest/UI/Controller/ListMyBugReportsController.php' => ['CustomerBugReportPortalService', 'listForUser($user,'],
            'src/Module/BetaTest/UI/Controller/ShowBugReportController.php' => ['CustomerBugReportPortalService', 'showForUser($user,'],
            'src/Module/Order/UI/Controller/CancelMyOrderController.php' => ['CustomerOrderPortalService', 'currentUser()'],
            'src/Module/Order/Application/Workflow/ExistingOrderCheckoutService.php' => ['OrderAccessPolicy', 'findOneForUser($addressId, $user)'],
            'src/Module/Order/UI/Controller/CheckoutSessionStatusController.php' => ['CustomerOrderPortalService', 'currentUser()'],
            'src/Module/Order/UI/Controller/DownloadMyOrderInvoicePdfController.php' => ['OrderAccessPolicy'],
            'src/Module/Order/UI/Controller/DownloadMyOrderInvoiceXmlController.php' => ['OrderAccessPolicy'],
            'src/Module/Order/UI/Controller/ListMyOrdersController.php' => ['CustomerOrderPortalService', 'listForUser($this->currentUser(),'],
            'src/Module/Order/UI/Controller/ShowOrderController.php' => ['CustomerOrderPortalService', 'showForUser($this->currentUser(),'],
            'src/Module/Quote/UI/Controller/Client/AcceptMyQuoteController.php' => ['CustomerQuotePortalService', 'currentUser()'],
            'src/Module/Quote/UI/Controller/Client/DeleteMyQuoteController.php' => ['CustomerQuotePortalService', 'currentUser()'],
            'src/Module/Quote/UI/Controller/Client/GenerateMyQuotePdfController.php' => ['CustomerQuotePortalService', 'currentUser()'],
            'src/Module/Quote/UI/Controller/Client/GetMyQuoteController.php' => ['CustomerQuotePortalService', 'showForUser($this->currentUser(),'],
            'src/Module/Quote/UI/Controller/Client/ListMyQuotesController.php' => ['CustomerQuotePortalService', 'listForUser($this->currentUser(),'],
            'src/Module/Quote/UI/Controller/Client/RefuseMyQuoteController.php' => ['CustomerQuotePortalService', 'currentUser()'],
            'src/Module/Rating/UI/Controller/CreateProductReviewController.php' => ['OrderAccessPolicy'],
            'src/Module/TradeIn/UI/Controller/DownloadMyTradeInReceiptController.php' => ['CustomerTradeInPortalService', 'currentUser()'],
            'src/Module/TradeIn/UI/Controller/ListMyTradeInsController.php' => ['CustomerTradeInPortalService', 'listForUser($this->currentUser(),'],
            'src/Module/TradeIn/UI/Controller/RespondToTradeInOfferController.php' => ['CustomerTradeInPortalService', 'currentUser()'],
            'src/Module/Training/UI/Controller/Client/ListMyTrainingEnrollmentsController.php' => ['CustomerTrainingPortalService', 'listEnrollmentsForUser($this->currentUser(),'],
            'src/Module/User/UI/Controller/Address/DeleteAddressController.php' => ['CustomerAddressBookService', 'findForUser($this->currentUser(), $id)'],
            'src/Module/User/UI/Controller/Address/ListMyAddressesController.php' => ['CustomerAddressBookService', 'listForUser($this->currentUser(),'],
            'src/Module/User/UI/Controller/Address/SetDefaultAddressController.php' => ['CustomerAddressBookService', 'findForUser($user, $id)'],
            'src/Module/User/UI/Controller/Address/UpdateAddressController.php' => ['CustomerAddressBookService', 'findForUser($this->currentUser(), $id)'],
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
        self::assertContains('ROLE_APPOINTMENTS_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_AUDITS_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_BETA_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_CUSTOMERS_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_LOYALTY_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_NEWS_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_PAYMENTS_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_PROMOTIONS_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_QUOTES_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_TRADE_INS_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_TRAINING_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);
        self::assertContains('ROLE_VOUCHERS_MANAGER', $security['security']['role_hierarchy']['ROLE_ADMIN']);

        self::assertSame('%env(APP_SECRET)%', $framework['framework']['secret']);
        self::assertSame('%env(TRUSTED_PROXIES)%', $framework['framework']['trusted_proxies']);
        self::assertSame('%env(TRUSTED_HOSTS)%', $framework['framework']['trusted_hosts']);
        self::assertFalse($framework['framework']['http_method_override']);
        self::assertSame('auto', $framework['framework']['session']['cookie_secure']);
        self::assertSame('lax', $framework['framework']['session']['cookie_samesite']);

        self::assertSame(5, $security['security']['firewalls']['api_login']['login_throttling']['max_attempts']);
        self::assertSame('15 minutes', $security['security']['firewalls']['api_login']['login_throttling']['interval']);
        self::assertTrue($security['security']['firewalls']['api']['stateless']);
        self::assertSame('argon2id', $security['security']['password_hashers']['Symfony\\Component\\Security\\Core\\User\\PasswordAuthenticatedUserInterface']['algorithm']);
        self::assertSame(65536, $security['security']['password_hashers']['Symfony\\Component\\Security\\Core\\User\\PasswordAuthenticatedUserInterface']['memory_cost']);
        self::assertSame(4, $security['security']['password_hashers']['Symfony\\Component\\Security\\Core\\User\\PasswordAuthenticatedUserInterface']['time_cost']);
        self::assertSame(1, $security['security']['password_hashers']['Symfony\\Component\\Security\\Core\\User\\PasswordAuthenticatedUserInterface']['threads']);

        self::assertSame(['%env(CORS_ALLOW_ORIGIN)%'], $cors['nelmio_cors']['defaults']['allow_origin']);
        self::assertTrue($cors['nelmio_cors']['defaults']['origin_regex']);
        self::assertTrue($cors['nelmio_cors']['defaults']['allow_credentials']);
        self::assertNotContains('*', $cors['nelmio_cors']['defaults']['allow_origin']);
        self::assertNotContains('Authorization', $cors['nelmio_cors']['defaults']['allow_headers']);
        self::assertContains('X-CSRF-Token', $cors['nelmio_cors']['defaults']['allow_headers']);
        self::assertContains('X-Request-Id', $cors['nelmio_cors']['defaults']['expose_headers']);
        self::assertSame(['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE'], $cors['nelmio_cors']['defaults']['allow_methods']);
        self::assertSame(['%env(CORS_ALLOW_ORIGIN)%'], $cors['nelmio_cors']['paths']['^/api/']['allow_origin']);
        self::assertTrue($cors['nelmio_cors']['paths']['^/api/']['origin_regex']);
        self::assertTrue($cors['nelmio_cors']['paths']['^/api/']['allow_credentials']);
        self::assertNotContains('*', $cors['nelmio_cors']['paths']['^/api/']['allow_origin']);
        self::assertNotContains('Authorization', $cors['nelmio_cors']['paths']['^/api/']['allow_headers']);
        self::assertSame(['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE'], $cors['nelmio_cors']['paths']['^/api/']['allow_methods']);
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

    public function testRoleHierarchyIsDeclaredOnlyOnce(): void
    {
        $filesWithHierarchy = [];
        foreach (glob(__DIR__.'/../../../config/packages/*.yaml') ?: [] as $file) {
            $config = Yaml::parseFile($file);
            if (isset($config['security']['role_hierarchy'])) {
                $filesWithHierarchy[] = basename($file);
            }
        }

        self::assertSame(['security.yaml'], $filesWithHierarchy);
    }

    public function testSecretMaterialStaysOutOfVersionedEnvironmentFiles(): void
    {
        $rootGitignore = file_get_contents(__DIR__.'/../../../../.gitignore');
        $backendGitignore = file_get_contents(__DIR__.'/../../../.gitignore');
        self::assertIsString($rootGitignore);
        self::assertIsString($backendGitignore);

        foreach ([
            'backend/.env.local',
            'backend/.env.*.local',
            'backend/.env.local.php',
            'backend/config/jwt/*.pem',
            'backend/config/jwt/*.key',
        ] as $ignoredPath) {
            self::assertStringContainsString($ignoredPath, $rootGitignore);
        }

        foreach ([
            '/.env.local',
            '/.env.local.php',
            '/.env.*.local',
            '/config/jwt/*.pem',
        ] as $ignoredPath) {
            self::assertStringContainsString($ignoredPath, $backendGitignore);
        }
    }

    public function testAuthenticationCookiesAreHttpOnlySameSiteAndSecureAware(): void
    {
        $source = file_get_contents(__DIR__.'/../../../src/Module/Auth/Infrastructure/Http/AuthCookieService.php');
        self::assertIsString($source);

        self::assertStringContainsString('Cookie::SAMESITE_LAX', $source);
        self::assertStringContainsString('clearCookie(AuthCookieResponseWriter::ACCESS_COOKIE, \'/api\', null, $secure, true', $source);
        self::assertStringContainsString('$secure,', $source);
        self::assertStringContainsString('true,', $source);
    }

    public function testAdminControllersRequireTheMinimalExpectedBusinessRole(): void
    {
        $expectations = [
            __DIR__.'/../../../src/Module/Admin/UI/Appointment' => 'ROLE_APPOINTMENTS_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/Audit' => 'ROLE_AUDITS_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/BetaTest' => 'ROLE_BETA_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/Catalog' => 'ROLE_CATALOG_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/Dashboard' => 'ROLE_ADMIN',
            __DIR__.'/../../../src/Module/Admin/UI/Marketing' => 'ROLE_MARKETING_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/News' => 'ROLE_NEWS_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/Operations' => 'ROLE_OPERATIONS',
            __DIR__.'/../../../src/Module/Admin/UI/Order' => 'ROLE_ORDERS_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/Payment' => 'ROLE_PAYMENTS_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/Promotion' => 'ROLE_PROMOTIONS_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/Quote' => 'ROLE_QUOTES_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/TradeIn' => 'ROLE_TRADE_INS_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/User' => 'ROLE_CUSTOMERS_MANAGER',
            __DIR__.'/../../../src/Module/Admin/UI/Voucher' => 'ROLE_VOUCHERS_MANAGER',
            __DIR__.'/../../../src/Module/Training/UI/Controller/Admin' => 'ROLE_TRAINING_MANAGER',
        ];

        $violations = [];
        foreach ($expectations as $directory => $role) {
            foreach (glob($directory.'/*Controller.php') ?: [] as $path) {
                $source = file_get_contents($path);
                self::assertIsString($source);

                if (str_contains($source, "#[Route('/api/public/")) {
                    continue;
                }

                if (!str_contains($source, "#[Route('/api/admin")) {
                    continue;
                }

                $expectedGrant = "#[IsGranted('".$role."')]";
                if (!str_contains($source, $expectedGrant)) {
                    $violations[] = $this->relativePath($path).' missing '.$expectedGrant;
                }

                if ('ROLE_ADMIN' !== $role && str_contains($source, "#[IsGranted('ROLE_ADMIN')]")) {
                    $violations[] = $this->relativePath($path).' uses ROLE_ADMIN instead of '.$role;
                }
            }
        }

        $loyaltyAdmin = __DIR__.'/../../../src/Module/Loyalty/UI/Controller/AdminLoyaltyController.php';
        $loyaltySource = file_get_contents($loyaltyAdmin);
        self::assertIsString($loyaltySource);
        if (!str_contains($loyaltySource, "#[Route('/api/admin/loyalty')]") || !str_contains($loyaltySource, "#[IsGranted('ROLE_LOYALTY_MANAGER')]")) {
            $violations[] = $this->relativePath($loyaltyAdmin).' must require ROLE_LOYALTY_MANAGER';
        }

        self::assertSame([], $violations);
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
        self::assertStringContainsString("forbid_env_pattern TRUSTED_PROXIES '^\\*$|0\\.0\\.0\\.0/0|::/0|votre-|change-me'", $productionCheck);
        self::assertStringContainsString("require_env_pattern TRUSTED_PROXIES 'REMOTE_ADDR|/|,|^([0-9]{1,3}\\.){3}[0-9]{1,3}$'", $productionCheck);
        self::assertStringContainsString("require_env_pattern APP_FRONTEND_URL '^https://'", $productionCheck);
        self::assertStringContainsString('require_command clamscan', $productionCheck);
        self::assertStringContainsString('upload_max_filesize', $productionCheck);
        self::assertStringContainsString('post_max_size', $productionCheck);
        self::assertStringContainsString('public/uploads/invoices', $productionCheck);
        self::assertStringContainsString('var/private', $productionCheck);
    }

    public function testProductionGuideDocumentsStrictCookieBasedCors(): void
    {
        $guide = file_get_contents(__DIR__.'/../../../PRODUCTION.md');
        self::assertIsString($guide);

        self::assertStringContainsString("CORS_ALLOW_ORIGIN='^https://(www\\.)?votre-domaine\\.tld$'", $guide);
        self::assertStringContainsString('pas de joker global', $guide);
        self::assertStringContainsString('allow_credentials: true', $guide);
        self::assertStringContainsString('jamais `*`', $guide);
        self::assertStringContainsString('authorization', $guide);
    }

    public function testProductionGuideDocumentsAuthSessionPolicy(): void
    {
        $guide = file_get_contents(__DIR__.'/../../../PRODUCTION.md');
        self::assertIsString($guide);

        self::assertStringContainsString('6bis) Politique de session / tokens', $guide);
        self::assertStringContainsString('1 heure', $guide);
        self::assertStringContainsString('30 jours', $guide);
        self::assertStringContainsString('10', $guide);
        self::assertStringContainsString('ancien refresh token devient inutilisable', $guide);
        self::assertStringContainsString('réinitialisation de mot de passe', $guide);
        self::assertStringContainsString('changement de mot de passe depuis le profil', $guide);
        self::assertStringContainsString('suppression de compte révoque toutes les sessions refresh', $guide);
        self::assertStringContainsString('app:auth:revoke-user-refresh-tokens', $guide);
    }

    public function testProductionGuideDocumentsSafeTrustedProxyConfiguration(): void
    {
        $guide = file_get_contents(__DIR__.'/../../../PRODUCTION.md');
        self::assertIsString($guide);

        self::assertStringContainsString('Ne jamais utiliser `*`, `0.0.0.0/0` ou `::/0` dans `TRUSTED_PROXIES`', $guide);
        self::assertStringContainsString('Cloudflare, Traefik, Nginx ou un load balancer', $guide);
        self::assertStringContainsString('127.0.0.1,REMOTE_ADDR', $guide);
        self::assertStringContainsString('rate limiting ne retombe pas systématiquement sur l’IP du reverse proxy', $guide);
    }

    public function testProductionGuideDocumentsSecretRotationProcedure(): void
    {
        $guide = file_get_contents(__DIR__.'/../../../PRODUCTION.md');
        self::assertIsString($guide);

        self::assertStringContainsString('11) Rotation des secrets', $guide);
        self::assertStringContainsString('APP_SECRET', $guide);
        self::assertStringContainsString('JWT_PASSPHRASE', $guide);
        self::assertStringContainsString('STRIPE_SECRET_KEY', $guide);
        self::assertStringContainsString('DATABASE_URL', $guide);
        self::assertStringContainsString('MAILER_DSN', $guide);
        self::assertStringContainsString('composer dump-env prod', $guide);
        self::assertStringContainsString('cache:clear', $guide);
    }

    public function testProductionGuideDocumentsTradeInPrivateDocumentRetentionAndEncryption(): void
    {
        $guide = file_get_contents(__DIR__.'/../../../PRODUCTION.md');
        self::assertIsString($guide);

        self::assertStringContainsString('Documents sensibles (RIB / justificatifs)', $guide);
        self::assertStringContainsString('var/private/trade-ins', $guide);
        self::assertStringContainsString('chiffré au repos', $guide);
        self::assertStringContainsString('app:trade-in:purge-private-documents', $guide);
        self::assertStringContainsString('--retention-days=180', $guide);
    }

    public function testPrivateDocumentsAndUploadLimitsAreGuardedInCode(): void
    {
        $tradeInStorage = file_get_contents(__DIR__.'/../../../src/Module/TradeIn/Infrastructure/Storage/TradeInPrivateFileStorage.php');
        $invoiceStorageCommand = file_get_contents(__DIR__.'/../../../src/Module/Order/Infrastructure/Command/SecureInvoiceStorageCommand.php');
        $jsonPayload = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Http/JsonPayload.php');
        self::assertIsString($tradeInStorage);
        self::assertIsString($invoiceStorageCommand);
        self::assertIsString($jsonPayload);

        self::assertStringContainsString('private const MAX_RIB_BYTES = 5_242_880', $tradeInStorage);
        self::assertStringContainsString('var/private/trade-ins', $tradeInStorage);
        self::assertStringContainsString('0600', $tradeInStorage);
        self::assertStringContainsString('securePrivateFile', $tradeInStorage);
        self::assertStringContainsString('function delete(', $tradeInStorage);
        self::assertStringNotContainsString('@chmod', $tradeInStorage);
        self::assertStringContainsString('resolvePrivateDocumentPath', $tradeInStorage);
        self::assertStringContainsString('var/private/invoices', $invoiceStorageCommand);
        self::assertStringContainsString('private/invoices/', $invoiceStorageCommand);
        self::assertStringContainsString('private const MAX_BYTES = 1_048_576', $jsonPayload);
    }

    public function testHighRiskPublicEndpointsUseIdentityAwareRateLimiting(): void
    {
        $expectations = [
            'src/Module/Contact/UI/Controller/ContactController.php' => ['RateLimitKeyFactory', '$input->email', 'limiter.contact_public'],
            'src/Module/Catalog/UI/Controller/PublicApi/ShareProductEmailController.php' => ['RateLimitKeyFactory', '$input->email', 'limiter.product_share_public'],
            'src/Module/News/UI/Controller/PublicApi/ShareNewsArticleEmailController.php' => ['RateLimitKeyFactory', '$input->email', 'limiter.content_share_public'],
            'src/Module/TradeIn/UI/Controller/CreatePublicTradeInController.php' => ['RateLimitKeyFactory', '$input->email', 'limiter.public_api'],
            'src/Module/Quote/UI/Controller/PublicApi/CreateQuoteController.php' => ['RateLimitKeyFactory', "customer['email']", 'limiter.public_api'],
        ];

        $violations = [];
        foreach ($expectations as $relativePath => $needles) {
            $source = file_get_contents(__DIR__.'/../../../'.$relativePath);
            self::assertIsString($source);

            foreach ($needles as $needle) {
                if (!str_contains($source, $needle)) {
                    $violations[] = $relativePath.' missing '.$needle;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testExternalFacingDtosDeclareExplicitValidationConstraints(): void
    {
        $expectations = [
            'src/Module/Contact/Application/DTO/ContactInput.php' => ['Assert\\NotBlank', 'Assert\\Length'],
            'src/Module/Auth/Application/DTO/RequestPasswordResetInput.php' => ['Assert\\Email', 'Assert\\Length'],
            'src/Module/Catalog/Application/DTO/ShareProductInput.php' => ['Assert\\Email', 'Assert\\Length'],
            'src/Module/News/Application/DTO/ShareNewsArticleInput.php' => ['Assert\\Email', 'Assert\\Length'],
            'src/Module/News/Application/DTO/CreateNewsCommentInput.php' => ['Assert\\NotBlank', 'Assert\\Length'],
            'src/Module/TradeIn/Application/DTO/TradeInInput.php' => ['Assert\\NotBlank', 'Assert\\Length', 'Assert\\Choice'],
            'src/Module/Quote/Application/DTO/QuotePayload.php' => ['Assert\\Callback', 'validateCustomer', 'validateItems'],
        ];

        $violations = [];
        foreach ($expectations as $relativePath => $needles) {
            $source = file_get_contents(__DIR__.'/../../../'.$relativePath);
            self::assertIsString($source);

            foreach ($needles as $needle) {
                if (!str_contains($source, $needle)) {
                    $violations[] = $relativePath.' missing '.$needle;
                }
            }
        }

        self::assertSame([], $violations);
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

    private function relativePath(string $path): string
    {
        $root = realpath(__DIR__.'/../../..');
        self::assertIsString($root);

        return ltrim(str_replace($root, '', $path), '/');
    }
}
