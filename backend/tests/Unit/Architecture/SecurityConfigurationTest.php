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
            'src/Module/Order/UI/Controller/CancelCheckoutSessionController.php' => ['CustomerOrderPortalService', 'currentUser()'],
            'src/Module/Order/UI/Controller/CancelMyOrderController.php' => ['CustomerOrderPortalService', 'currentUser()'],
            'src/Module/Order/Application/Workflow/ExistingOrderCheckoutService.php' => ['OrderAccessPolicy', 'findOneForUser($addressId, $user)'],
            'src/Module/Order/UI/Controller/CheckoutSessionStatusController.php' => ['CustomerOrderPortalService', 'currentUser()'],
            'src/Module/Order/UI/Http/MyOrderInvoiceAccessService.php' => ['OrderAccessPolicy', 'canDownloadInvoice($user, $order)'],
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
        self::assertStringContainsString('expose_php', $productionCheck);
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
        self::assertStringContainsString('app:auth:revoke-all-refresh-tokens --confirm', $guide);
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
        self::assertStringContainsString('Limitation du périmètre système', $guide);
        self::assertStringContainsString('workers ne doivent pouvoir écrire que dans `var/`', $guide);
        self::assertStringContainsString('outbox n’est plus inscriptible', $guide);
    }

    public function testMessengerWorkerRunsWithLeastPrivilegeAccount(): void
    {
        $service = file_get_contents(__DIR__.'/../../../../deploy/systemd/hociatec-messenger.service');
        self::assertIsString($service);

        self::assertStringContainsString('User=www-data', $service);
        self::assertStringContainsString('Group=www-data', $service);
        self::assertStringContainsString('messenger:consume async', $service);
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

    public function testObservabilityAndStructuredLoggingContractsAreExplicit(): void
    {
        $monolog = Yaml::parseFile(__DIR__.'/../../../config/packages/monolog.yaml');
        $metricsController = file_get_contents(__DIR__.'/../../../src/Module/System/UI/Controller/MetricsController.php');
        $metricProvider = file_get_contents(__DIR__.'/../../../src/Module/System/Application/Provider/PrometheusMetricContractProvider.php');
        $alertPolicy = file_get_contents(__DIR__.'/../../../src/Module/Outbox/Application/OutboxAlertPolicy.php');
        self::assertIsArray($monolog);
        self::assertIsString($metricsController);
        self::assertIsString($metricProvider);
        self::assertIsString($alertPolicy);

        self::assertSame('monolog.formatter.json', $monolog['when@prod']['monolog']['handlers']['nested']['formatter']);
        self::assertSame('monolog.formatter.json', $monolog['when@prod']['monolog']['handlers']['deprecation']['formatter']);
        self::assertStringContainsString("public const HEADER = 'X-Request-Id';", file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Http/RequestIdSubscriber.php') ?: '');
        self::assertStringContainsString('hociatec_http_request_duration_seconds_count', $metricProvider);
        self::assertStringContainsString('hociatec_http_responses_total{status_class="5xx"}', $metricProvider);
        self::assertStringContainsString('hociatec_webhook_failures_total', $metricProvider);
        self::assertStringContainsString('hociatec_backup_failed_total', $metricProvider);
        self::assertStringContainsString('hociatec_outbox_dead_events', $metricProvider);
        self::assertStringContainsString('Outbox events reached the dead-letter queue.', $alertPolicy);
        self::assertStringContainsString('Outbox events are stuck in processing.', $alertPolicy);
        self::assertStringContainsString('X-Metrics-Token', $metricsController);
    }

    public function testBackendQualityWorkflowAndComposerScriptsCoverValidationAnalysisAndTests(): void
    {
        $workflow = file_get_contents(__DIR__.'/../../../../.github/workflows/backend-quality.yml');
        $composer = json_decode((string) file_get_contents(__DIR__.'/../../../composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $phpstan = file_get_contents(__DIR__.'/../../../phpstan.neon');
        $phpCsFixer = file_get_contents(__DIR__.'/../../../.php-cs-fixer.dist.php');
        self::assertIsString($workflow);
        self::assertIsArray($composer);
        self::assertIsString($phpstan);
        self::assertIsString($phpCsFixer);

        self::assertStringContainsString('composer quality', $workflow);
        self::assertStringContainsString('cache:warmup', $workflow);
        self::assertSame('composer validate --strict --no-check-publish', $composer['scripts']['app:validate']);
        self::assertSame('composer audit --locked', $composer['scripts']['app:audit']);
        self::assertSame('APP_ENV=prod php bin/console lint:container', $composer['scripts']['app:lint-container']);
        self::assertSame('php bin/console lint:yaml config --parse-tags', $composer['scripts']['app:lint-yaml']);
        self::assertSame('APP_ENV=prod php bin/console doctrine:migrations:up-to-date --no-interaction', $composer['scripts']['app:migrations-up-to-date']);
        self::assertStringContainsString('phpstan analyse', $composer['scripts']['app:phpstan']);
        self::assertSame('php bin/phpunit --testdox', $composer['scripts']['app:test']);
        self::assertContains('@app:validate', $composer['scripts']['quality']);
        self::assertContains('@app:audit', $composer['scripts']['quality']);
        self::assertContains('@app:phpstan', $composer['scripts']['quality']);
        self::assertContains('@app:lint-container', $composer['scripts']['quality']);
        self::assertContains('@app:lint-yaml', $composer['scripts']['quality']);
        self::assertContains('@app:migrations-up-to-date', $composer['scripts']['quality']);
        self::assertContains('@app:test', $composer['scripts']['quality']);
        self::assertContains('@app:deptrac', $composer['scripts']['quality']);
        self::assertStringContainsString('level: 8', $phpstan);
        self::assertStringContainsString('@Symfony', $phpCsFixer);
        self::assertStringContainsString('declare_strict_types', $phpCsFixer);
        self::assertStringContainsString('no_unused_imports', $phpCsFixer);
    }

    public function testProductionGuideDocumentsDeploymentRollbackAndIncidentProcedure(): void
    {
        $guide = file_get_contents(__DIR__.'/../../../PRODUCTION.md');
        self::assertIsString($guide);

        self::assertStringContainsString('Déploiement applicatif', $guide);
        self::assertStringContainsString('composer install --no-dev --classmap-authoritative', $guide);
        self::assertStringContainsString('cache:warmup', $guide);
        self::assertStringContainsString('Rollback', $guide);
        self::assertStringContainsString('artefact applicatif précédent', $guide);
        self::assertStringContainsString('GET /api/health/readiness', $guide);
        self::assertStringContainsString('Incident response', $guide);
        self::assertStringContainsString('compromission JWT', $guide);
        self::assertStringContainsString('webhook Stripe compromis', $guide);
        self::assertStringContainsString('fuite de base ou de fichier sensible', $guide);
    }

    public function testComposerLockAndTokenGenerationPoliciesAreExplicit(): void
    {
        self::assertFileExists(__DIR__.'/../../../composer.lock');

        $violations = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.'/../../../src'));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            self::assertIsString($source);

            foreach (['uniqid(', 'mt_rand(', 'str_shuffle('] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($file->getPathname()).' contains '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testTemporalPoliciesHaveDedicatedRegressionCoverage(): void
    {
        $files = [
            __DIR__.'/../Module/Auth/AuthSupportTest.php',
            __DIR__.'/../Module/Auth/PasswordResetAndVerifyControllersTest.php',
            __DIR__.'/../Module/Auth/RefreshTokenControllersAndLogoutTest.php',
            __DIR__.'/../Module/Order/Entity/OrderCheckoutSessionTest.php',
            __DIR__.'/../Module/TradeIn/Controller/TradeInUserControllersTest.php',
            __DIR__.'/../Module/Voucher/Service/VoucherEngineTest.php',
            __DIR__.'/../Module/Promotion/Service/PromotionEngineTest.php',
        ];

        foreach ($files as $file) {
            self::assertFileExists($file);
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertMatchesRegularExpression('/expired|expiresAt|Expiration|\\+1 hour|\\+1 day|\\+1 week/i', $source);
        }
    }

    public function testProductionGuideDocumentsContinuousSecurityDecisionReview(): void
    {
        $guide = file_get_contents(__DIR__.'/../../../PRODUCTION.md');
        self::assertIsString($guide);

        self::assertStringContainsString('Décisions de sécurité à conserver', $guide);
        self::assertStringContainsString('SameSite=Lax', $guide);
        self::assertStringContainsString('rotaté à chaque usage valide', $guide);
        self::assertStringContainsString('Revue continue du socle', $guide);
        self::assertStringContainsString('code mort', $guide);
        self::assertStringContainsString('garanties automatiques', $guide);
        self::assertStringContainsString('simplification', $guide);
    }

    public function testDependencySupportStatusIsDocumentedWithCurrentSecurityChecks(): void
    {
        $guide = file_get_contents(__DIR__.'/../../../PRODUCTION.md');
        $status = file_get_contents(__DIR__.'/../../../../docs/dependency-support-status-2026-08-11.md');
        self::assertIsString($guide);
        self::assertIsString($status);

        self::assertStringContainsString('Maintenance des dépendances', $guide);
        self::assertStringContainsString('composer.lock', $guide);
        self::assertStringContainsString('composer audit --locked', $guide);
        self::assertStringContainsString('11 août 2026', $guide);

        self::assertStringContainsString('August 11, 2026', $status);
        self::assertStringContainsString('PHP `8.3.32`', $status);
        self::assertStringContainsString('PHP `8.3` remains security-supported until `December 31, 2027`', $status);
        self::assertStringContainsString('Symfony `7.4.16`', $status);
        self::assertStringContainsString('security fixes until `November 2029`', $status);
        self::assertStringContainsString('composer audit --locked', $status);
        self::assertStringContainsString('PHP `8.3.33` security release from `July 30, 2026`', $status);
    }

    public function testHighRiskPublicEndpointsUseIdentityAwareRateLimiting(): void
    {
        $expectations = [
            'src/Module/Contact/UI/Controller/ContactController.php' => ['RateLimitKeyFactory', '$input->email', 'limiter.contact_public'],
            'src/Module/Catalog/UI/Controller/PublicApi/ShareProductEmailController.php' => ['RateLimitKeyFactory', '$input->email', 'limiter.product_share_public'],
            'src/Module/News/UI/Controller/PublicApi/ShareNewsArticleEmailController.php' => ['RateLimitKeyFactory', '$input->email', 'limiter.content_share_public'],
            'src/Module/TradeIn/UI/Controller/CreatePublicTradeInController.php' => ['$input->email'],
            'src/Module/TradeIn/UI/Http/PublicTradeInRateLimiter.php' => ['RateLimitKeyFactory', 'limiter.public_api'],
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
            'src/Module/News/Application/DTO/NewsArticleInput.php' => ['Assert\\NotBlank', 'Assert\\Length'],
            'src/Module/TradeIn/Application/DTO/TradeInInput.php' => ['Assert\\NotBlank', 'Assert\\Length', 'Assert\\Choice'],
            'src/Module/TradeIn/Application/DTO/TradeInConditionInput.php' => ['Assert\\NotBlank', 'Assert\\Length', 'Assert\\Choice'],
            'src/Module/BetaTest/Application/DTO/BetaProfileInput.php' => ['Assert\\NotBlank', 'Assert\\Length'],
            'src/Module/Admin/Application/Operations/DTO/SupportCreateInput.php' => ['Assert\\NotBlank', 'Assert\\Length'],
            'src/Module/Admin/Application/Operations/DTO/SupportUpdateInput.php' => ['Assert\\Length', 'Assert\\Choice'],
            'src/Module/Admin/Application/Operations/DTO/SupportReplyInput.php' => ['Assert\\NotBlank', 'Assert\\Length', 'Assert\\Choice'],
            'src/Module/User/Application/DTO/UpdateProfileInput.php' => ['Assert\\Email', 'Assert\\Length'],
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

    public function testHtmlAndEmailRenderingEscapeUserControlledContent(): void
    {
        $paths = [
            'src/Module/Marketing/Application/Notification/EmailTemplateRenderer.php',
            'src/Module/Order/Application/Provider/OrderNotificationTemplateRenderer.php',
            'src/Module/Voucher/Application/Workflow/VoucherNotificationTemplateRenderer.php',
            'src/Module/TradeIn/Application/Workflow/TradeInClosureService.php',
            'src/Shared/Infrastructure/Pdf/PdfHtmlFormatter.php',
            'src/Module/Notification/Application/Notification/UserCommunicationEmailSender.php',
        ];

        foreach ($paths as $relativePath) {
            $source = file_get_contents(__DIR__.'/../../../'.$relativePath);
            self::assertIsString($source);
            self::assertTrue(
                str_contains($source, 'htmlspecialchars(') || str_contains($source, '->escape('),
                $relativePath.' must escape user-controlled HTML output.',
            );
        }
    }

    public function testCriticalPersistenceConstraintsLocksAndObservabilityContractsAreExplicit(): void
    {
        $expectations = [
            'src/Module/Outbox/Infrastructure/Repository/OutboxEventRepository.php' => ['FOR UPDATE SKIP LOCKED', 'recoverStaleProcessing', 'STATUS_PROCESSING'],
            'src/Module/Outbox/Application/OutboxDispatcher.php' => ['private const MAX_ATTEMPTS = 5', 'recoverStaleProcessing', 'markDead', 'markFailed'],
            'src/Module/Auth/Application/Outbox/SendPasswordResetEmailHandler.php' => ['hash_equals', "auth.password_reset.'.hash('sha256', \$token)", 'return;'],
            'src/Module/User/Application/Outbox/SendActivationEmailHandler.php' => ['sendActivationEmail($user, $token, $event->getKey())'],
            'src/Module/Quote/Application/Outbox/SendQuoteCreatedEmailHandler.php' => ['force', 'getCreatedEmailSentAt()', 'setCreatedEmailSentAt(new \\DateTimeImmutable())'],
            'src/Module/Marketing/Infrastructure/MessageHandler/SendMarketingCampaignRecipientEmailHandler.php' => ['canAttemptDelivery()', 'markSent()', 'markFailed('],
            'src/Module/Order/Application/Workflow/RefundStripeProcessor.php' => ['findForUpdate($refundId)', 'transactional(function () use ($refundId)'],
            'src/Module/Admin/Application/Operations/Workflow/StockOperationsService.php' => ['findForUpdate($productId)', 'transactional(function () use ($productId'],
            'src/Module/Auth/Infrastructure/Repository/RefreshTokenRepository.php' => ['LockMode::PESSIMISTIC_WRITE', 'findOneBySelectorForUpdate'],
            'src/Module/Catalog/Infrastructure/Repository/ProductRepository.php' => ['findForUpdate(int $id)', 'LockMode::PESSIMISTIC_WRITE'],
            'src/Module/Order/Infrastructure/Repository/OrderRepository.php' => ['findForUpdate(int $id)', 'LockMode::PESSIMISTIC_WRITE'],
            'src/Module/Training/Infrastructure/Repository/TrainingSessionRepository.php' => ['findForUpdate(int $id)', 'LockMode::PESSIMISTIC_WRITE'],
            'src/Module/User/Infrastructure/Repository/UserRepository.php' => ['findForUpdate(int $id)', 'LockMode::PESSIMISTIC_WRITE'],
            'src/Module/Appointment/Infrastructure/Repository/WorkingDayConfigurationRepository.php' => ['findOneByDayForUpdate', 'LockMode::PESSIMISTIC_WRITE'],
            'src/Module/Appointment/Application/Workflow/AppointmentService.php' => ['findOneByDayForUpdate($dayOfWeek)', 'transactional(function () use ($user, $prestation, $startAt)'],
            'src/Module/Voucher/Application/Handler/CreateVoucherHandler.php' => ['UniqueConstraintViolationException', 'Ce code existe déjà.'],
            'src/Module/Order/Domain/Entity/StripeWebhookEvent.php' => ['unique: true'],
            'src/Module/Outbox/Domain/Entity/OutboxEvent.php' => ['uniq_outbox_event_key', 'idx_outbox_pending'],
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

    public function testMySqlIsolationAndLockingStrategyIsDocumentedForCriticalWritePaths(): void
    {
        $guide = file_get_contents(__DIR__.'/../../../../docs/backend-transaction-conventions.md');
        self::assertIsString($guide);

        foreach ([
            'MySQL/InnoDB',
            'does not override the database isolation level',
            'PESSIMISTIC_WRITE',
            'unique constraints plus duplicate-key handling',
            'RefreshTokenRepository::findOneBySelectorForUpdate()',
            'ProductRepository::findForUpdate()',
            'WorkingDayConfigurationRepository::findOneByDayForUpdate()',
            'TrainingSessionRepository::findForUpdate()',
            'UserRepository::findForUpdate()',
            'voucher codes is the final authority',
        ] as $needle) {
            self::assertStringContainsString($needle, $guide);
        }
    }

    public function testBackupRestoreRetentionEncryptionAndSafeCommandExecutionAreDocumentedAndCovered(): void
    {
        $guide = file_get_contents(__DIR__.'/../../../PRODUCTION.md');
        $backupTest = file_get_contents(__DIR__.'/../Module/Admin/Backup/BackupWorkflowCoverageTest.php');
        $dumper = file_get_contents(__DIR__.'/../../../src/Module/Admin/Infrastructure/Backup/Dumper/DatabaseBackupDumper.php');
        $encryption = file_get_contents(__DIR__.'/../../../src/Module/Admin/Application/Backup/Workflow/BackupEncryptionService.php');
        $storage = file_get_contents(__DIR__.'/../../../src/Module/Admin/Application/Backup/Storage/BackupFileStorage.php');
        self::assertIsString($guide);
        self::assertIsString($backupTest);
        self::assertIsString($dumper);
        self::assertIsString($encryption);
        self::assertIsString($storage);

        foreach ([
            'Restauration de backup',
            'restauration complète',
            'BACKUP_ENCRYPTION_KEY_FILE',
            'clé séparée du backup',
            'Run a monthly restore drill',
        ] as $needle) {
            self::assertStringContainsString($needle, $guide);
        }

        self::assertStringContainsString("['mysqldump'", $dumper);
        self::assertStringContainsString('PROCESS_TIMEOUT_SECONDS = 900', $dumper);
        self::assertStringContainsString('sodium_crypto_secretstream_xchacha20poly1305_init_push', $encryption);
        self::assertStringContainsString('function decryptFile(', $encryption);
        self::assertStringContainsString('testEncryptedBackupCanBeDecryptedAndRestoredToSqlPayload', $backupTest);
        self::assertStringContainsString('applyRetention', $storage);
    }

    public function testSensitiveConsoleCommandsValidateInputsBoundWorkAndNormalizeFailures(): void
    {
        $expectations = [
            'src/Module/Admin/Infrastructure/Backup/Command/RunDueBackupsCommand.php' => [
                "addOption('force'",
                "catch (\\RuntimeException \$e)",
                'return Command::FAILURE;',
            ],
            'src/Module/Admin/Infrastructure/Backup/Command/EncryptExistingBackupsCommand.php' => [
                'legacyPaths()',
                "catch (\\RuntimeException \$exception)",
                'return Command::FAILURE;',
            ],
            'src/Module/Auth/Infrastructure/Command/RevokeUserRefreshTokensCommand.php' => [
                "trim((string) \$input->getArgument('email'))",
                'return Command::INVALID;',
                'return Command::FAILURE;',
            ],
            'src/Module/Auth/Infrastructure/Command/PurgeRefreshTokensCommand.php' => [
                "addOption('revoked-retention-days'",
                'max(0, (int) $input->getOption(\'revoked-retention-days\'))',
            ],
            'src/Module/TradeIn/Infrastructure/Command/PurgeTradeInPrivateDocumentsCommand.php' => [
                "addOption('retention-days'",
                "addOption('limit'",
                'max(1, (int) $input->getOption(\'limit\'))',
            ],
            'src/Module/Order/Infrastructure/Command/SecureInvoiceStorageCommand.php' => [
                "addOption('batch-size'",
                'min(1000, (int) $input->getOption(\'batch-size\'))',
                "preg_match('/^[A-Za-z0-9._-]+\\\\.'",
                'return Command::FAILURE;',
            ],
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

    public function testDebugAndFixturesToolingStayDisabledInProduction(): void
    {
        $bundles = require __DIR__.'/../../../config/bundles.php';
        $frameworkRoutes = file_get_contents(__DIR__.'/../../../config/routes/framework.yaml');
        self::assertIsArray($bundles);
        self::assertIsString($frameworkRoutes);

        self::assertSame(['dev' => true], $bundles['Symfony\\Bundle\\MakerBundle\\MakerBundle']);
        self::assertSame(['dev' => true, 'test' => true], $bundles['Doctrine\\Bundle\\FixturesBundle\\DoctrineFixturesBundle']);
        self::assertStringContainsString('when@dev:', $frameworkRoutes);
        self::assertStringContainsString("@FrameworkBundle/Resources/config/routing/errors.php", $frameworkRoutes);
        self::assertStringContainsString('prefix: /_error', $frameworkRoutes);
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
