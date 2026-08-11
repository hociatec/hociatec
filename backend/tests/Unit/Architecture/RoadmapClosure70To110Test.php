<?php

declare(strict_types=1);

namespace App\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class RoadmapClosure70To110Test extends TestCase
{
    public function testUsefulCoveragePolicyAndCriticalSuitesAreExplicit(): void
    {
        $guide = file_get_contents(__DIR__.'/../../../../docs/quality-gates.md');
        self::assertIsString($guide);

        foreach ([
            'Couverture utile minimale',
            'refresh tokens',
            'permissions HTTP',
            'CSRF',
            'Stripe',
            'concurrence',
            'pagination bornée',
        ] as $needle) {
            self::assertStringContainsString($needle, $guide);
        }

        foreach ([
            __DIR__.'/../Module/Auth/RefreshTokenControllersAndLogoutTest.php',
            __DIR__.'/../Module/Auth/RefreshTokenConcurrencyTest.php',
            __DIR__.'/../Module/Misc/RoadmapConcurrencyClosureTest.php',
            __DIR__.'/../Module/Security/ResourceAccessPolicyTest.php',
            __DIR__.'/../Module/Appointment/Controller/PublicAndClientAppointmentControllersIntegrationTest.php',
            __DIR__.'/../Module/Training/Controller/TrainingPublicAndClientControllersIntegrationTest.php',
            __DIR__.'/../Module/Outbox/OutboxTest.php',
            __DIR__.'/../Module/Admin/Backup/BackupWorkflowCoverageTest.php',
            __DIR__.'/../Module/Order/Service/StripeWebhookServiceTest.php',
        ] as $path) {
            self::assertFileExists($path);
        }
    }

    public function testIntermoduleBoundariesForbidCrossImportsOfUiAndInfrastructure(): void
    {
        $allowedExceptions = [
            'src/Module/Auth/Infrastructure/Security/EmailUserProvider.php',
            'src/Module/Cart/Infrastructure/Http/CartMergeSubscriber.php',
            'src/Module/User/Infrastructure/Security/SymfonyUserPasswordHasher.php',
        ];
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            if (!preg_match('#/src/Module/([^/]+)/#', $path, $moduleMatch)) {
                continue;
            }
            $currentModule = $moduleMatch[1];

            if (preg_match_all('/use App\\\\Module\\\\([^\\\\]+)\\\\(Infrastructure|UI)\\\\[^;]+;/', $source, $matches, \PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    if ($match[1] !== $currentModule) {
                        $relativePath = $this->relativePath($path);
                        if (!in_array($relativePath, $allowedExceptions, true)) {
                            $violations[] = $relativePath.' imports '.$match[0];
                        }
                    }
                }
            }
        }

        self::assertSame([], $violations);
        $doc = file_get_contents(__DIR__.'/../../../../docs/architecture/intermodule-dependencies.md');
        self::assertIsString($doc);
        self::assertStringContainsString('Exceptions techniques contrôlées', $doc);
        self::assertStringContainsString('SymfonySecurityUser', $doc);
        self::assertStringContainsString('EmailUserProvider', $doc);
    }

    public function testDomainLayerAvoidsTechnicalRuntimeDependenciesOutsideDocumentedDoctrineMetadata(): void
    {
        $violations = [];
        $patterns = [
            'Symfony\\Component\\HttpFoundation',
            'Symfony\\Component\\Mailer',
            'Symfony\\Component\\Process',
            'Symfony\\Contracts\\HttpClient',
            'Psr\\Cache',
            'Psr\\Log',
            'Stripe\\',
            'App\\Module\\',
            'App\\Shared\\Infrastructure',
        ];

        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_contains($path, '/Domain/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach ($patterns as $pattern) {
                if ('App\\Module\\' === $pattern) {
                    if (preg_match('/use App\\\\Module\\\\[^\\\\]+\\\\Infrastructure\\\\/', $source)) {
                        $violations[] = $this->relativePath($path).' imports another module infrastructure';
                    }

                    continue;
                }

                if (str_contains($source, $pattern)) {
                    $violations[] = $this->relativePath($path).' imports '.$pattern;
                }
            }

            foreach (['file_put_contents(', 'fopen(', 'unlink(', 'rename(', 'mkdir(', 'curl_'] as $forbiddenRuntimeCall) {
                if (str_contains($source, $forbiddenRuntimeCall)) {
                    $violations[] = $this->relativePath($path).' uses '.$forbiddenRuntimeCall;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testConstructorsStayBelowDependencyThresholdAndThinGlueAuditIsDocumented(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src') as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            if (!preg_match('/function __construct\((.*?)\)/s', $source, $match)) {
                continue;
            }

            preg_match_all('/\$[A-Za-z_][A-Za-z0-9_]*/', $match[1], $parameters);
            if (count($parameters[0]) > 9) {
                $violations[] = $this->relativePath($path).' has '.count($parameters[0]).' constructor dependencies';
            }
        }

        self::assertSame([], $violations);

        $doc = file_get_contents(__DIR__.'/../../../../docs/architecture/ports-and-service-audit.md');
        self::assertIsString($doc);
        foreach ([
            'OrderRepositoryPort',
            'StripeRefundClient',
            'ProductWriteExecution',
            'QuoteConversionPolicy',
            '9 dépendances',
        ] as $needle) {
            self::assertStringContainsString($needle, $doc);
        }
    }

    public function testTransactionalBoundariesAvoidDirectExternalCallsAndLongRunningEffects(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src') as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            if (!str_contains($source, 'transactional(')) {
                continue;
            }

            foreach ([
                'MailerInterface',
                'HttpClientInterface',
                'curl_setopt(',
                'new Process(',
                'file_put_contents(',
                'rename(',
                'unlink(',
            ] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).' mixes transactional boundary with '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
        self::assertStringContainsString(
            'TransactionManager::transactional()',
            (string) file_get_contents(__DIR__.'/../../../../docs/backend-transaction-conventions.md'),
        );
    }

    public function testPaginationStreamingCacheAndPrivateFilePoliciesAreExplicit(): void
    {
        $searchProvider = file_get_contents(__DIR__.'/../../../src/Module/Catalog/Application/Provider/ProductCatalogSearchProvider.php');
        $cacheConfig = file_get_contents(__DIR__.'/../../../config/packages/cache.yaml');
        $attachmentFactory = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Http/AttachmentResponseFactory.php');
        $healthController = file_get_contents(__DIR__.'/../../../src/Module/System/UI/Controller/HealthController.php');
        $metricsController = file_get_contents(__DIR__.'/../../../src/Module/System/UI/Controller/MetricsController.php');
        $operationsExport = file_get_contents(__DIR__.'/../../../src/Module/Admin/UI/Operations/Controller/OperationsExportController.php');
        $privateCacheSubscriber = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Http/PrivateApiCacheControlSubscriber.php');
        self::assertIsString($searchProvider);
        self::assertIsString($cacheConfig);
        self::assertIsString($attachmentFactory);
        self::assertIsString($healthController);
        self::assertIsString($metricsController);
        self::assertIsString($operationsExport);
        self::assertIsString($privateCacheSubscriber);

        self::assertStringContainsString("'version' => \$this->cacheVersion->current()", $searchProvider);
        self::assertStringContainsString("'criteria' => \$criteria->criteria()->cacheKeyPayload()", $searchProvider);
        self::assertStringContainsString('default_lifetime: 300', $cacheConfig);
        self::assertStringContainsString('default_lifetime: 900', $cacheConfig);
        self::assertStringContainsString('makeDisposition(', $attachmentFactory);
        self::assertStringContainsString("X-Content-Type-Options', 'nosniff", $attachmentFactory);
        self::assertStringContainsString("Cache-Control', 'no-store, max-age=0", $healthController);
        self::assertStringContainsString("#[Route('/api/health/liveness'", $healthController);
        self::assertStringContainsString("#[Route('/api/health/readiness'", $healthController);
        self::assertStringContainsString("'Cache-Control' => 'no-store'", $metricsController);
        self::assertStringContainsString('StreamedResponse', $operationsExport);
        self::assertStringContainsString("Cache-Control', 'no-store, private", $privateCacheSubscriber);
        self::assertStringContainsString("Pragma', 'no-cache", $privateCacheSubscriber);
    }

    public function testDeletionPrivacyTimezoneAndMigrationPoliciesAreDocumented(): void
    {
        $privacy = file_get_contents(__DIR__.'/../../../../docs/data-lifecycle-and-privacy.md');
        $migrations = file_get_contents(__DIR__.'/../../../../docs/backend-migration-conventions.md');
        $audit = file_get_contents(__DIR__.'/../../../../docs/performance/text-search-and-timezone-audit.md');
        self::assertIsString($privacy);
        self::assertIsString($migrations);
        self::assertIsString($audit);

        foreach ([
            'suppression',
            'anonymisation',
            'Droit d’accès / export',
            'UTC',
            '`User`',
            'Matrice de décision actuelle',
            '`ShippingAddress`',
            '`TradeInRequest`',
            '`EmailTemplate`',
            '`BetaCampaign`',
            'export JSON authentifié',
            'commandes anciennes',
        ] as $needle) {
            self::assertStringContainsString($needle, $privacy);
        }

        foreach ([
            'réversibles',
            'nullable',
            'backfill',
            'downtime',
            'cache:warmup',
        ] as $needle) {
            self::assertStringContainsString($needle, $migrations);
        }

        foreach ([
            'Catalog/ProductCatalogFilterQueries',
            'UserAdminCustomerQueries',
            'TradeInRequestRepository',
            'UTC',
            'DATE_ATOM',
            'ClockInterface',
        ] as $needle) {
            self::assertStringContainsString($needle, $audit);
        }
    }

    public function testDeadCodeCleanupRemovesRedundantVoucherLookupInterfaceAndSingleUseTraits(): void
    {
        self::assertFileDoesNotExist(__DIR__.'/../../../src/Module/Voucher/Infrastructure/Repository/VoucherLookupInterface.php');

        foreach ([
            __DIR__.'/../../../src/Module/Catalog/Application/Workflow/CategoryReaderTrait.php',
            __DIR__.'/../../../src/Module/Catalog/Application/Workflow/CategoryWriterTrait.php',
            __DIR__.'/../../../src/Module/Catalog/Application/Workflow/CategoryValidationTrait.php',
        ] as $path) {
            self::assertFileDoesNotExist($path);
        }

        $voucherRepository = file_get_contents(__DIR__.'/../../../src/Module/Voucher/Infrastructure/Repository/VoucherRepository.php');
        $categoryWorkflow = file_get_contents(__DIR__.'/../../../src/Module/Catalog/Application/Workflow/CategoryCatalogWorkflow.php');
        self::assertIsString($voucherRepository);
        self::assertIsString($categoryWorkflow);
        self::assertStringNotContainsString('implements VoucherLookupInterface', $voucherRepository);
        self::assertStringNotContainsString('use CategoryReaderTrait;', $categoryWorkflow);
        self::assertStringNotContainsString('use CategoryWriterTrait;', $categoryWorkflow);
        self::assertStringNotContainsString('use CategoryValidationTrait;', $categoryWorkflow);
    }

    public function testSensitiveCommandsUseLocksAndIdempotencyGuards(): void
    {
        $checkoutOrderCreator = file_get_contents(__DIR__.'/../../../src/Module/Order/Application/Workflow/CheckoutSessionOrderCreator.php');
        $cartOrderCreator = file_get_contents(__DIR__.'/../../../src/Module/Order/Application/Factory/CartOrderCreator.php');
        $refundProcessor = file_get_contents(__DIR__.'/../../../src/Module/Order/Application/Workflow/RefundStripeProcessor.php');
        $stripeWebhook = file_get_contents(__DIR__.'/../../../src/Module/Order/Application/Workflow/StripeWebhookService.php');
        $orderHostedCheckout = file_get_contents(__DIR__.'/../../../src/Module/Order/Application/Workflow/OrderHostedCheckoutCreator.php');
        $cartHostedCheckout = file_get_contents(__DIR__.'/../../../src/Module/Order/Application/Workflow/CartHostedCheckoutCreator.php');
        self::assertIsString($checkoutOrderCreator);
        self::assertIsString($cartOrderCreator);
        self::assertIsString($refundProcessor);
        self::assertIsString($stripeWebhook);
        self::assertIsString($orderHostedCheckout);
        self::assertIsString($cartHostedCheckout);

        self::assertStringContainsString('if (null !== $checkout->getOrderId())', $checkoutOrderCreator);
        self::assertStringContainsString('findForUpdate($checkout->getOrderId())', $checkoutOrderCreator);
        self::assertStringContainsString('return $existing;', $checkoutOrderCreator);
        self::assertStringContainsString('findForUpdate($cartId)', $cartOrderCreator);
        self::assertStringContainsString("if (\$lockedCart->isConverted())", $cartOrderCreator);
        self::assertStringContainsString('Ce panier a deja ete valide.', $cartOrderCreator);
        self::assertStringContainsString('findForUpdate($refundId)', $refundProcessor);
        self::assertStringContainsString("'refund_request:'.\$refund->getId()", $refundProcessor);
        self::assertStringContainsString("'duplicate' => true", $stripeWebhook);
        self::assertStringContainsString('findReusableOpenSessionForOrder($user, $orderId)', $orderHostedCheckout);
        self::assertStringContainsString('return $existing;', $orderHostedCheckout);
        self::assertStringContainsString('findReusableOpenSessionForCart($user, $cart->getToken())', $cartHostedCheckout);
        self::assertStringContainsString('return $existing;', $cartHostedCheckout);
    }

    public function testDoctrineDeleteStrategiesAndConstraintsStayExplicitlyOwned(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_contains($path, '/Domain/Entity/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            if (
                (str_contains($source, "cascade: ['persist', 'remove']") || str_contains($source, 'orphanRemoval: true'))
                && preg_match('#/src/Module/([^/]+)/#', $path, $moduleMatch)
                && preg_match_all('/targetEntity:\s*([A-Za-z0-9_\\\\]+)::class/', $source, $matches)
            ) {
                foreach ($matches[1] as $targetEntity) {
                    if (str_contains($targetEntity, '\\Module\\') && !str_contains($targetEntity, '\\Module\\'.$moduleMatch[1].'\\')) {
                        $violations[] = $this->relativePath($path).' cascades to '.$targetEntity;
                    }
                }
            }
        }

        self::assertSame([], $violations);

        $constraints = [
            'src/Module/Outbox/Domain/Entity/OutboxEvent.php' => ['uniq_outbox_event_key', 'idx_outbox_pending'],
            'src/Module/Order/Domain/Entity/StripeWebhookEvent.php' => ['unique: true'],
            'src/Module/User/Domain/Entity/User.php' => ['UNIQ_USERS_EMAIL'],
            'src/Module/Training/Domain/Entity/TrainingEnrollment.php' => ['uniq_training_session_user'],
        ];
        foreach ($constraints as $relativePath => $needles) {
            $source = file_get_contents(__DIR__.'/../../../'.$relativePath);
            self::assertIsString($source);
            foreach ($needles as $needle) {
                self::assertStringContainsString($needle, $source);
            }
        }
    }

    public function testClockAndStatusConventionsRemainExplicitForCriticalFlows(): void
    {
        $adr = file_get_contents(__DIR__.'/../../../../docs/architecture/decisions/0001-doctrine-in-domain.md');
        self::assertIsString($adr);
        self::assertStringContainsString('ClockInterface', $adr);

        foreach ([
            'src/Module/Order/Application/Factory/OrderNumberGenerator.php',
            'src/Module/Order/Application/Factory/InvoiceNumberGenerator.php',
            'src/Module/Order/Application/Factory/CartOrderCreator.php',
            'src/Module/Order/Application/Workflow/CheckoutSessionOrderCreator.php',
            'src/Module/Quote/Application/Conversion/QuoteOrderFactory.php',
            'src/Module/Voucher/Application/Calculator/VoucherEngine.php',
            'src/Module/Voucher/Application/Workflow/VoucherNotificationValidator.php',
            'src/Module/Appointment/Application/Workflow/AppointmentService.php',
            'src/Module/Promotion/Application/Calculator/PromotionEngine.php',
            'src/Module/Notification/Application/Provider/AccountNotificationProvider.php',
            'src/Module/BetaTest/Application/Provider/BetaCampaignProvider.php',
            'src/Module/News/Application/Writer/NewsArticleWriter.php',
        ] as $relativePath) {
            $source = file_get_contents(__DIR__.'/../../../'.$relativePath);
            self::assertIsString($source);
            self::assertStringContainsString('ClockInterface', $source);
        }

        foreach ([
            'src/Module/Order/Domain/Workflow/OrderStatusWorkflow.php' => ['transitionFor', 'canTransitionTo'],
            'src/Module/TradeIn/Application/Workflow/TradeInRequestWorkflow.php' => ['Cette transition est impossible', 'canTransition'],
            'src/Module/Appointment/Application/Workflow/AppointmentStatusWorkflow.php' => ['canTransition', 'STATUS_DEFINITIONS'],
            'src/Module/Outbox/Domain/Entity/OutboxEvent.php' => ['STATUS_PENDING', 'STATUS_PROCESSING', 'STATUS_PROCESSED', 'STATUS_FAILED', 'STATUS_DEAD'],
        ] as $relativePath => $needles) {
            $source = file_get_contents(__DIR__.'/../../../'.$relativePath);
            self::assertIsString($source);
            foreach ($needles as $needle) {
                self::assertStringContainsString($needle, $source);
            }
        }
    }

    public function testExternalClientsAndProcessesDeclareExplicitTimeouts(): void
    {
        $stripe = file_get_contents(__DIR__.'/../../../src/Module/Order/Application/Workflow/StripeApiClient.php');
        $translate = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Http/LibreTranslateClient.php');
        $outboxWebhook = file_get_contents(__DIR__.'/../../../src/Module/Outbox/Infrastructure/Alert/WebhookOutboxAlertNotifier.php');
        $pdf = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Pdf/AccessiblePdfRenderer.php');
        $backup = file_get_contents(__DIR__.'/../../../src/Module/Admin/Infrastructure/Backup/Dumper/DatabaseBackupDumper.php');
        $privateStorage = file_get_contents(__DIR__.'/../../../src/Module/TradeIn/Infrastructure/Storage/TradeInPrivateFileStorage.php');
        $mailerSender = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Mail/SymfonyEmailSender.php');
        $services = file_get_contents(__DIR__.'/../../../config/services.yaml');
        $envExample = file_get_contents(__DIR__.'/../../../.env.prod.example');
        self::assertIsString($stripe);
        self::assertIsString($translate);
        self::assertIsString($outboxWebhook);
        self::assertIsString($pdf);
        self::assertIsString($backup);
        self::assertIsString($privateStorage);
        self::assertIsString($mailerSender);
        self::assertIsString($services);
        self::assertIsString($envExample);

        self::assertStringContainsString('CURLOPT_CONNECTTIMEOUT', $stripe);
        self::assertStringContainsString('CURLOPT_TIMEOUT', $stripe);
        self::assertStringContainsString("'timeout' => self::REQUEST_TIMEOUT_SECONDS", $translate);
        self::assertStringContainsString("'max_duration' => self::REQUEST_TIMEOUT_SECONDS + 1.0", $translate);
        self::assertStringContainsString("'timeout' => 5", $outboxWebhook);
        self::assertStringContainsString('setTimeout(5)', $pdf);
        self::assertStringContainsString('setTimeout($this->timeoutSeconds)', $pdf);
        self::assertStringContainsString('PROCESS_TIMEOUT_SECONDS = 900', $backup);
        self::assertStringContainsString('setTimeout(self::PROCESS_TIMEOUT_SECONDS)', $backup);
        self::assertStringContainsString('ANTIVIRUS_TIMEOUT_SECONDS = 20', $privateStorage);
        self::assertStringContainsString('setTimeout(self::ANTIVIRUS_TIMEOUT_SECONDS)', $privateStorage);
        self::assertStringContainsString("ini_set('default_socket_timeout', \$timeout)", $mailerSender);
        self::assertStringContainsString('MAILER_TIMEOUT_SECONDS', $services);
        self::assertStringContainsString('MAILER_TIMEOUT_SECONDS=10', $envExample);
    }

    public function testExternalRetriesAndCircuitBreakingStayLimitedToSafeOperations(): void
    {
        $stripe = file_get_contents(__DIR__.'/../../../src/Module/Order/Application/Workflow/StripeApiClient.php');
        $translate = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Http/LibreTranslateClient.php');
        self::assertIsString($stripe);
        self::assertIsString($translate);

        self::assertStringContainsString('MAX_ATTEMPTS = 2', $stripe);
        self::assertStringContainsString("'GET' === \$method || (null !== \$idempotencyKey", $stripe);
        self::assertStringContainsString('RETRYABLE_STATUS_CODES', $stripe);

        self::assertStringContainsString('MAX_ATTEMPTS_PER_ENDPOINT = 2', $translate);
        self::assertStringContainsString('CIRCUIT_BREAKER_FAILURE_THRESHOLD = 2', $translate);
        self::assertStringContainsString('CIRCUIT_BREAKER_COOLDOWN_SECONDS = 60', $translate);
        self::assertStringContainsString('isCircuitOpen', $translate);
        self::assertStringContainsString('markFailure', $translate);
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $paths = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $paths[] = $file->getPathname();
            }
        }

        sort($paths);

        return $paths;
    }

    private function relativePath(string $path): string
    {
        $root = realpath(__DIR__.'/../../../');
        $normalized = realpath($path) ?: $path;

        return null !== $root ? ltrim(str_replace($root, '', $normalized), '/') : $normalized;
    }
}
