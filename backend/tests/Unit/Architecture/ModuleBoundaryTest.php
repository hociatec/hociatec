<?php

declare(strict_types=1);

namespace App\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class ModuleBoundaryTest extends TestCase
{
    public function testControllersDoNotDependOnPersistenceDetails(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_ends_with($path, 'Controller.php')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (['EntityManagerInterface', 'DoctrineUnitOfWork', '->persist(', '->flush(', '->remove('] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testControllersDoNotImportInfrastructureRepositories(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_ends_with($path, 'Controller.php')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            if (preg_match_all('/use App\\\\Module\\\\[^;]+\\\\Infrastructure\\\\Repository\\\\[^;]+;/', $source, $matches)) {
                foreach ($matches[0] as $import) {
                    $violations[] = $this->relativePath($path).': '.$import;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testControllersKeepHttpMappersInUiNamespace(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_ends_with($path, 'Controller.php')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            if (preg_match_all('/use App\\\\Module\\\\[^;]+\\\\Infrastructure\\\\Http\\\\[^;]*(?:Formatter|Mapper);/', $source, $matches)) {
                foreach ($matches[0] as $import) {
                    $violations[] = $this->relativePath($path).': '.$import;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testTransactionBoundaryIsSeparatedFromUnitOfWork(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_ends_with($path, 'Persistence.php')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (['implements TransactionManager', 'wrapInTransaction(', 'function transactional('] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        $unitOfWork = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Doctrine/DoctrineUnitOfWork.php');
        self::assertIsString($unitOfWork);
        foreach (['implements TransactionManager', 'wrapInTransaction(', 'function transactional('] as $forbidden) {
            if (str_contains($unitOfWork, $forbidden)) {
                $violations[] = 'src/Shared/Infrastructure/Doctrine/DoctrineUnitOfWork.php: '.$forbidden;
            }
        }

        self::assertSame([], $violations);
    }

    public function testDoctrineUnitOfWorkOnlyExposesWriteUnitOfWorkOperations(): void
    {
        $unitOfWork = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Doctrine/DoctrineUnitOfWork.php');
        self::assertIsString($unitOfWork);

        foreach (['createQueryBuilder', 'function queryBuilder(', 'function findForUpdate(', 'LockMode::', 'function clear('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $unitOfWork);
        }
    }

    public function testRepositoriesDoNotExposeImplicitFlushBooleans(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src') as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (['bool $flush', ', true)'] as $forbidden) {
                if (!str_contains($source, $forbidden)) {
                    continue;
                }

                if (preg_match('/->(?:save|remove|revokeAllForUser)\([^;]*,\s*true\)/s', $source) || str_contains($source, 'bool $flush')) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], array_values(array_unique($violations)));
    }

    public function testSourceDoesNotCheckRolesManually(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src') as $path) {
            if (str_ends_with($path, 'User.php')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (["in_array('ROLE_", 'in_array("ROLE_', 'getRoles(), true)'] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testExternalServiceExceptionMessageIsNotExposedDirectly(): void
    {
        $subscriber = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Http/ApiExceptionSubscriber.php');
        self::assertIsString($subscriber);

        self::assertStringNotContainsString('ExternalServiceException => [$exception->getMessage()', $subscriber);
        self::assertStringContainsString('PublicApiException => [$exception->publicMessage()', $subscriber);
    }

    public function testApplicationModulesDoNotUseGenericManagerServices(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (str_ends_with($path, 'Manager.php')) {
                $violations[] = $this->relativePath($path);
            }
        }

        self::assertSame([], $violations);
    }

    public function testSharedConceptsDoNotLiveInRootInfrastructure(): void
    {
        $forbiddenPaths = [
            __DIR__.'/../../../src/Infrastructure',
            __DIR__.'/../../../src/Infrastructure/Application',
            __DIR__.'/../../../src/Infrastructure/ValueObject',
            __DIR__.'/../../../src/Infrastructure/Persistence/DoctrineTransactionManager.php',
            __DIR__.'/../../../src/Infrastructure/Persistence/DoctrineUnitOfWork.php',
        ];

        $violations = [];
        foreach ($forbiddenPaths as $path) {
            if (file_exists($path)) {
                $violations[] = $this->relativePath($path);
            }
        }

        self::assertSame([], $violations);
    }

    public function testApplicationDtoDirectoriesUseUppercaseDtoConvention(): void
    {
        $violations = [];
        foreach ($this->directories(__DIR__.'/../../../src/Module') as $path) {
            $relativePath = $this->relativePath($path);
            if (
                str_contains($relativePath, '/Application/Dto')
                || str_contains($relativePath, '/Application/input')
                || str_contains($relativePath, '/Application/command')
                || str_contains($relativePath, '/Application/query')
                || str_contains($relativePath, '/Application/result')
                || str_contains($relativePath, '/Application/viewModel')
                || str_contains($relativePath, '/Application/viewmodel')
            ) {
                $violations[] = $this->relativePath($path);
            }
        }

        self::assertSame([], $violations);
    }

    public function testPdfGeneratorsLiveInInfrastructurePdf(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (str_contains($path, '/Application/') && str_ends_with($path, 'PdfService.php')) {
                $violations[] = $this->relativePath($path);
            }
        }

        self::assertSame([], $violations);
    }

    public function testPdfRendererFacadesStaySmall(): void
    {
        $limits = [
            __DIR__.'/../../../src/Module/Quote/Infrastructure/Pdf/QuotePdfService.php' => 80,
            __DIR__.'/../../../src/Module/Order/Infrastructure/Pdf/OrderInvoicePdfService.php' => 80,
        ];
        $violations = [];

        foreach ($limits as $path => $limit) {
            $lines = file($path);
            self::assertIsArray($lines);
            if (count($lines) > $limit) {
                $violations[] = $this->relativePath($path).': '.count($lines).' lines';
            }
        }

        self::assertSame([], $violations);
    }

    public function testApplicationFormattersLiveInProjectionNamespaces(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (str_contains($path, '/Application/') && !str_contains($path, '/Projection/') && str_ends_with($path, 'Formatter.php')) {
                $violations[] = $this->relativePath($path);
            }
        }

        self::assertSame([], $violations);
    }

    public function testLargeProjectionFormattersStaySplitByConcern(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_contains($path, '/Application/') || !str_contains($path, '/Projection/') || !str_ends_with($path, 'Formatter.php')) {
                continue;
            }

            $lines = file($path);
            self::assertIsArray($lines);
            if (count($lines) > 180) {
                $violations[] = $this->relativePath($path).': '.count($lines).' lines';
            }
        }

        self::assertSame([], $violations);
    }

    public function testApplicationLayerDoesNotUseGenericServiceBucketInMainModules(): void
    {
        $violations = [];

        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            $relativePath = $this->relativePath($path);
            if (str_contains($relativePath, '/Application/Service/')) {
                $violations[] = $relativePath;
            }
        }

        self::assertSame([], $violations);
    }

    public function testApplicationLayerUsesPortsForMainAggregateRepositories(): void
    {
        $forbiddenImports = [
            'App\\Module\\Catalog\\Infrastructure\\Repository\\ProductRepository',
            'App\\Module\\Order\\Infrastructure\\Repository\\OrderRepository',
            'App\\Module\\Quote\\Infrastructure\\Repository\\QuoteRepository',
            'App\\Module\\TradeIn\\Infrastructure\\Repository\\TradeInRequestRepository',
            'App\\Module\\User\\Infrastructure\\Repository\\UserRepository',
            'App\\Module\\Voucher\\Infrastructure\\Repository\\VoucherRepository',
        ];
        $violations = [];

        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            $relativePath = $this->relativePath($path);
            if (!str_contains($relativePath, '/Application/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach ($forbiddenImports as $forbiddenImport) {
                if (str_contains($source, 'use '.$forbiddenImport.';')) {
                    $violations[] = $relativePath.': '.$forbiddenImport;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testApplicationLayerDoesNotImportInfrastructureRepositories(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            $relativePath = $this->relativePath($path);
            if (!str_contains($relativePath, '/Application/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            if (preg_match_all('/use App\\\\Module\\\\[^;]+\\\\Infrastructure\\\\Repository\\\\[^;]+;/', $source, $matches)) {
                foreach ($matches[0] as $import) {
                    $violations[] = $relativePath.': '.$import;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testApplicationLayerDoesNotImportModuleInfrastructure(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            $relativePath = $this->relativePath($path);
            if (!str_contains($relativePath, '/Application/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            if (preg_match_all('/use App\\\\Module\\\\[^;]+\\\\Infrastructure\\\\[^;]+;/', $source, $matches)) {
                foreach ($matches[0] as $import) {
                    $violations[] = $relativePath.': '.$import;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testApplicationLayerDoesNotDependOnInfrastructureOrDoctrineDetails(): void
    {
        $forbiddenPatterns = [
            '/\buse\s+App\\\\(?:Module\\\\[^;]+|Shared)\\\\Infrastructure\\\\[^;]+;/',
            '/(?<![A-Za-z0-9_\\\\])App\\\\(?:Module\\\\[^;]+|Shared)\\\\Infrastructure\\\\[A-Za-z0-9_\\\\]+/',
            '/\buse\s+Doctrine\\\\ORM\\\\EntityManagerInterface;/',
            '/(?<![A-Za-z0-9_\\\\])Doctrine\\\\ORM\\\\EntityManagerInterface(?![A-Za-z0-9_\\\\])/',
            '/\buse\s+Doctrine\\\\DBAL\\\\LockMode;/',
            '/(?<![A-Za-z0-9_\\\\])Doctrine\\\\DBAL\\\\LockMode(?![A-Za-z0-9_\\\\])/',
        ];
        $violations = [];

        foreach ($this->applicationLayerPhpFiles() as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach ($forbiddenPatterns as $pattern) {
                if (preg_match_all($pattern, $source, $matches)) {
                    foreach ($matches[0] as $match) {
                        $violations[] = $this->relativePath($path).': '.$match;
                    }
                }
            }
        }

        self::assertSame([], array_values(array_unique($violations)));
    }

    public function testUiLayerDoesNotImportModuleInfrastructure(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            $relativePath = $this->relativePath($path);
            if (!str_contains($relativePath, '/UI/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            if (preg_match_all('/use App\\\\Module\\\\[^;]+\\\\Infrastructure\\\\[^;]+;/', $source, $matches)) {
                foreach ($matches[0] as $import) {
                    $violations[] = $relativePath.': '.$import;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testReadWriteApplicationNamingConventionIsDocumented(): void
    {
        $doc = file_get_contents(__DIR__.'/../../../docs/architecture-naming.md');
        self::assertIsString($doc);

        foreach ([
            'Provider` executes a read',
            'Projection` is the read model',
            'Handler` executes a write',
            'Query` directory is optional',
            'pragmatic Symfony approach',
        ] as $expected) {
            self::assertStringContainsString($expected, $doc);
        }
    }

    public function testLargeAggregateRepositoriesKeepQueryConcernsSplit(): void
    {
        $limits = [
            __DIR__.'/../../../src/Module/User/Infrastructure/Repository/UserRepository.php' => 180,
            __DIR__.'/../../../src/Module/Order/Infrastructure/Repository/OrderRepository.php' => 140,
            __DIR__.'/../../../src/Module/Order/Infrastructure/Repository/OrderCheckoutSessionRepository.php' => 130,
        ];
        $requiredHelpers = [
            __DIR__.'/../../../src/Module/User/Infrastructure/Repository/UserAdminCustomerQueries.php',
            __DIR__.'/../../../src/Module/Order/Infrastructure/Repository/OrderAdminQueries.php',
            __DIR__.'/../../../src/Module/Order/Infrastructure/Repository/OrderOperationsMetricsQueries.php',
            __DIR__.'/../../../src/Module/Order/Infrastructure/Repository/OrderCheckoutSessionAdminQueries.php',
            __DIR__.'/../../../src/Module/Order/Infrastructure/Repository/OrderCheckoutSessionDashboardQueries.php',
        ];
        $violations = [];

        foreach ($limits as $path => $limit) {
            $lines = file($path);
            self::assertIsArray($lines);
            if (count($lines) > $limit) {
                $violations[] = $this->relativePath($path).': '.count($lines).' lines';
            }
        }

        foreach ($requiredHelpers as $path) {
            if (!file_exists($path)) {
                $violations[] = $this->relativePath($path).': missing query helper';
            }
        }

        self::assertSame([], $violations);
    }

    public function testProductWriteUseCasesDoNotExposeLongPositionalSignatures(): void
    {
        $limits = [
            \App\Module\Admin\Application\Catalog\DTO\ProductWriteData::class => ['__construct' => 4],
            \App\Module\Catalog\Application\DTO\ProductWriteCommand::class => ['__construct' => 5, 'forCreate' => 4, 'forUpdate' => 5],
            \App\Module\Catalog\Application\Handler\ProductWriteHandler::class => ['create' => 1, 'update' => 1],
            \App\Module\Catalog\Application\Writer\ProductAttributeWriter::class => ['create' => 4, 'update' => 5],
        ];
        $violations = [];

        foreach ($limits as $class => $methods) {
            $reflection = new \ReflectionClass($class);
            foreach ($methods as $method => $limit) {
                $count = $reflection->getMethod($method)->getNumberOfParameters();
                if ($count > $limit) {
                    $violations[] = $class.'::'.$method.' has '.$count.' parameters';
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testProductWriteHandlerSeparatesCacheInvalidationFromDatabaseTransaction(): void
    {
        $handler = file_get_contents(__DIR__.'/../../../src/Module/Catalog/Application/Handler/ProductWriteHandler.php');
        self::assertIsString($handler);

        self::assertStringNotContainsString('CacheItemPoolInterface', $handler);
        self::assertStringNotContainsString('->clear()', $handler);
        self::assertStringContainsString('CatalogCacheInvalidator', $handler);
        self::assertStringContainsString('invalidateAfterWrite', $handler);
    }

    public function testDomainEntityTraitsStayFocused(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_contains($path, '/Domain/Entity/') || !str_ends_with($path, 'Trait.php')) {
                continue;
            }

            $lines = file($path);
            self::assertIsArray($lines);
            if (count($lines) > 180) {
                $violations[] = $this->relativePath($path).': '.count($lines).' lines';
            }
        }

        self::assertSame([], $violations);
    }

    public function testFormerLargeDomainTraitsAreReplacedByComposedObjects(): void
    {
        $expectations = [
            __DIR__.'/../../../src/Module/Catalog/Domain/Entity/Product.php' => [
                'forbidden' => ['ProductGalleryActionsTrait', 'ProductGalleryImagesTrait'],
                'required' => ['?ProductGallery $gallery'],
            ],
            __DIR__.'/../../../src/Module/Order/Domain/Entity/OrderCheckoutSession.php' => [
                'forbidden' => ['OrderCheckoutBillingTrait', 'OrderCheckoutCustomerIdentityTrait', 'OrderCheckoutShippingTrait'],
                'required' => ['CheckoutCustomerSnapshot', 'CheckoutShippingSnapshot', 'CheckoutBillingSnapshot'],
            ],
            __DIR__.'/../../../src/Module/TradeIn/Domain/Entity/TradeInRequest.php' => [
                'forbidden' => [
                    'TradeInRequestApplicantViewTrait',
                    'TradeInRequestEstimateViewTrait',
                    'TradeInRequestLifecycleViewTrait',
                    'TradeInRequestProductViewTrait',
                    'TradeInRequestSettlementViewTrait',
                ],
                'required' => ['TradeInApplicant', 'TradeInProductSnapshot', 'TradeInEstimate', 'TradeInClosure', 'TradeInPrivateDocument'],
            ],
        ];

        $violations = [];
        foreach ($expectations as $path => $rules) {
            $source = file_get_contents($path);
            self::assertIsString($source);
            foreach ($rules['forbidden'] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
            foreach ($rules['required'] as $required) {
                if (!str_contains($source, $required)) {
                    $violations[] = $this->relativePath($path).': missing '.$required;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testArchitectureNamingConventionDocumentsSuffixes(): void
    {
        $doc = file_get_contents(__DIR__.'/../../../docs/architecture-naming.md');
        self::assertIsString($doc);

        foreach (['DTO', 'Input', 'Command', 'Query', 'Result', 'ViewModel', 'ResponseMapper', 'Handler', 'Provider', 'Projection', 'Calculator', 'Policy', 'Workflow', 'Mapper', 'Gateway', 'Repository', 'Service', 'Manager'] as $suffix) {
            self::assertStringContainsString($suffix, $doc);
        }
    }

    public function testCleanedMarketingControllersDoNotDecodeJsonInline(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module/Admin/UI/Marketing/Controller') as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            if (str_contains($source, 'JsonPayload::decode')) {
                $violations[] = $this->relativePath($path).': JsonPayload::decode';
            }
        }

        self::assertSame([], $violations);
    }

    public function testModuleControllersDoNotDecodeJsonPayloadInline(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_ends_with($path, 'Controller.php')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            if (str_contains($source, 'JsonPayload::decode')) {
                $violations[] = $this->relativePath($path).': JsonPayload::decode';
            }
        }

        self::assertSame([], $violations);
    }

    public function testSourceDoesNotCatchThrowableOrBaseException(): void
    {
        $allowed = [
            'src/Module/Outbox/Application/OutboxDispatcher.php',
            'src/Shared/Infrastructure/Doctrine/DoctrineTransactionManager.php',
            'src/Shared/Infrastructure/Transaction/InMemoryTransactionSideEffectRegistry.php',
        ];
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src') as $path) {
            if (in_array($this->relativePath($path), $allowed, true)) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (['catch (\\Throwable', 'catch (\\Exception'] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testBetaDomainDoesNotReadSymfonyRoles(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module/BetaTest/Domain/Entity') as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (['ROLE_', 'getRoles('] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testDomainSecurityPoliciesDoNotGrantFrameworkAdminAccess(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_contains($path, '/Domain/Security/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (['isAdmin(', 'ROLE_', 'AuthorizationCheckerInterface', 'isGranted('] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testDomainDoesNotDependOnHttpFoundation(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_contains($path, '/Domain/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            if (str_contains($source, 'Symfony\\Component\\HttpFoundation')) {
                $violations[] = $this->relativePath($path).': Symfony\\Component\\HttpFoundation';
            }
        }

        self::assertSame([], $violations);
    }

    public function testDomainEntitiesDoNotDependOnSymfonySecurityContracts(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_contains($path, '/Domain/Entity/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            if (str_contains($source, 'Symfony\\Component\\Security\\Core\\User')) {
                $violations[] = $this->relativePath($path).': Symfony\\Component\\Security\\Core\\User';
            }
        }

        self::assertSame([], $violations);
    }

    public function testApplicationAndUiDependOnDocumentPortsInsteadOfPdfInfrastructure(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_contains($path, '/Application/') && !str_contains($path, '/UI/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (['AccessiblePdfRenderer', '\\Dompdf\\', 'Infrastructure\\Pdf\\'] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testControllersDoNotImplementOwnershipRulesInline(): void
    {
        $allowed = [
            'src/Module/Auth/UI/Controller/ProfileController.php',
        ];
        $violations = [];

        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_ends_with($path, 'Controller.php') || in_array($this->relativePath($path), $allowed, true)) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (['getRoles(', "isGranted('ROLE_", '->getUser()->getId() !==', '->getUser()->getId() ===', '->getClient()->getId() !==', 'getCustomerEmail()) !==', '->getUser() !==', '->getUser() ==='] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testCartTokensAreNeverAcceptedFromQueryString(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src') as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (["query->get('cartToken'", 'query->get("cartToken"', "request->query->get('cartToken'", 'request->query->get("cartToken"'] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testExternalProcessesUseSymfonyProcessAndNoErrorSuppression(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src') as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (['proc_open(', '@proc_open', '@unlink'] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testPdfRuntimeConfigurationIsNotTiedToAUnixUser(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src') as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (['/home/hocine', '/home/ubuntu/.local/lib/python', 'site-packages'] as $forbidden) {
                if (str_contains($source, $forbidden) && !str_ends_with($path, 'AccessiblePdfRenderer.php')) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testAttachmentResponsesDoNotBuildContentDispositionManually(): void
    {
        $allowed = [
            'src/Shared/Infrastructure/Http/AttachmentResponseFactory.php',
        ];
        $violations = [];

        foreach ($this->phpFiles(__DIR__.'/../../../src') as $path) {
            if (in_array($this->relativePath($path), $allowed, true)) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            if (str_contains($source, 'attachment; filename=')) {
                $violations[] = $this->relativePath($path).': attachment; filename=';
            }
        }

        self::assertSame([], $violations);
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        $paths = [];

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $paths[] = $file->getPathname();
            }
        }

        sort($paths);

        return $paths;
    }

    /** @return list<string> */
    private function applicationLayerPhpFiles(): array
    {
        $paths = [];

        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (str_contains($this->relativePath($path), '/Application/')) {
                $paths[] = $path;
            }
        }

        foreach ($this->phpFiles(__DIR__.'/../../../src/Shared/Application') as $path) {
            $paths[] = $path;
        }

        sort($paths);

        return $paths;
    }

    private function relativePath(string $path): string
    {
        $root = realpath(__DIR__.'/../../../');
        $realPath = realpath($path);

        return is_string($root) && is_string($realPath) ? str_replace($root.'/', '', $realPath) : $path;
    }

    /** @return list<string> */
    private function directories(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        $paths = [];

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isDir() && !in_array($file->getFilename(), ['.', '..'], true)) {
                $paths[] = $file->getPathname();
            }
        }

        sort($paths);

        return $paths;
    }
}
