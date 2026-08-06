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

    public function testSharedHttpDoesNotDuplicateApplicationProblemExceptions(): void
    {
        self::assertFileDoesNotExist(__DIR__.'/../../../src/Shared/Infrastructure/Http/ApiProblemException.php');
        self::assertFileDoesNotExist(__DIR__.'/../../../src/Shared/Infrastructure/Http/ExternalServiceException.php');

        $publicApiException = file_get_contents(__DIR__.'/../../../src/Shared/Infrastructure/Http/PublicApiException.php');
        self::assertIsString($publicApiException);
        self::assertStringContainsString('extends \\App\\Shared\\Application\\Exception\\PublicApiException', $publicApiException);
        self::assertStringNotContainsString('extends ApiProblemException', $publicApiException);
    }

    public function testUserDomainSecurityContractsDoNotImportSymfonySecurityInterfaces(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module/User/Domain/Security') as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            if (str_contains($source, 'Symfony\\Component\\Security\\Core\\User')) {
                $violations[] = $this->relativePath($path).': Symfony Security user interface';
            }
        }

        self::assertSame([], $violations);
    }

    public function testStripeApiClientNormalizesTransportAndInvalidJsonFailures(): void
    {
        $client = file_get_contents(__DIR__.'/../../../src/Module/Order/Application/Workflow/StripeApiClient.php');
        self::assertIsString($client);

        foreach ([
            'CURLOPT_CONNECTTIMEOUT',
            'curl_error($curl)',
            'catch (\\JsonException $exception)',
            'Stripe a retourné une réponse invalide.',
            'if (!\\is_array($decoded))',
            'Stripe a refusé la requête avec le statut HTTP %d.',
        ] as $expected) {
            self::assertStringContainsString($expected, $client);
        }
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

    public function testApplicationLayerDoesNotKnowHttpFoundationOutsideDocumentedMigrationBacklog(): void
    {
        $allowedBacklog = [];

        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src') as $path) {
            $relativePath = $this->relativePath($path);
            if (!str_contains($relativePath, '/Application/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);
            if (!str_contains($source, 'Symfony\\Component\\HttpFoundation')) {
                continue;
            }

            if (!in_array($relativePath, $allowedBacklog, true)) {
                $violations[] = $relativePath;
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

    public function testIntermoduleDependenciesAreDocumented(): void
    {
        $path = __DIR__.'/../../../../docs/architecture/intermodule-dependencies.md';
        self::assertFileExists($path);

        $documentation = file_get_contents($path);
        self::assertIsString($documentation);

        foreach (['Matrice autorisée', 'Règles `User`', 'Module `Admin`', 'Revue des `OperationsService`', 'Revue des entités volumineuses'] as $section) {
            self::assertStringContainsString($section, $documentation);
        }
    }

    public function testDoctrineDomainCompromiseIsDocumentedPrecisely(): void
    {
        $decision = file_get_contents(__DIR__.'/../../../../docs/architecture/decisions/0001-doctrine-in-domain.md');
        $exceptions = file_get_contents(__DIR__.'/../../../docs/architecture-exceptions.md');
        self::assertIsString($decision);
        self::assertIsString($exceptions);

        foreach ([
            "le domaine n'est pas indépendant de Doctrine",
            'architecture modulaire en couches',
            'pas une Clean Architecture stricte',
            'Les interfaces Symfony Security ne doivent pas être placées dans un contrat de domaine',
        ] as $expected) {
            self::assertStringContainsString($expected, $decision);
        }

        foreach ([
            'Deprecation Doctrine DBAL 5784',
            'doctrine/dbal` `^3.10.6',
            'retiree lors de la prochaine mise a jour Doctrine',
        ] as $expected) {
            self::assertStringContainsString($expected, $exceptions);
        }
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

    public function testUiLayerOnlyUsesApprovedSharedInfrastructureAdapters(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            $relativePath = $this->relativePath($path);
            if (!str_contains($relativePath, '/UI/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            if (preg_match_all('/use App\\\\Shared\\\\Infrastructure\\\\(?!Http\\\\|Validation\\\\)[^;]+;/', $source, $matches)) {
                foreach ($matches[0] as $import) {
                    $violations[] = $relativePath.': '.$import;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testControllersDelegateRequestNormalizationToMappers(): void
    {
        $forbiddenPatterns = [
            '/->query->/',
            '/\bgetInt\s*\(/',
            '/\btrim\s*\(\s*\(string\)\s*\$/',
            '/\b\(int\)\s*\(\s*\$/',
            '/\b\(string\)\s*\(\s*\$/',
            '/\bis_numeric\s*\(/',
            '/\bfilter_var\s*\(/',
            '/Pagination::fromRequest\s*\(/',
        ];
        $violations = [];

        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_ends_with($path, 'Controller.php')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach ($forbiddenPatterns as $pattern) {
                if (preg_match($pattern, $source, $match)) {
                    $violations[] = $this->relativePath($path).': '.$match[0];
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
            \App\Module\Catalog\Application\Workflow\ProductVariantService::class => ['createVariantCopy' => 1],
            \App\Module\Catalog\Application\Factory\ProductVariantFactory::class => ['createVariantCopy' => 1],
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

    public function testProductListUseCasesUseCriteriaObjectsInsteadOfLongPositionalSignatures(): void
    {
        $expectations = [
            \App\Module\Catalog\Application\Port\ProductCatalogRepository::class => [
                'findAllForAdmin' => \App\Module\Catalog\Application\Query\ProductAdminCriteria::class,
                'countForAdmin' => \App\Module\Catalog\Application\Query\ProductAdminCriteria::class,
                'findPublished' => \App\Module\Catalog\Application\Query\ProductCatalogCriteria::class,
                'findPublishedListProjection' => \App\Module\Catalog\Application\Query\ProductCatalogCriteria::class,
                'countPublished' => \App\Module\Catalog\Application\Query\ProductCatalogCriteria::class,
                'collectPublishedFacets' => \App\Module\Catalog\Application\Query\ProductCatalogCriteria::class,
            ],
            \App\Module\Catalog\Application\Workflow\ProductQueryService::class => [
                'listForAdmin' => \App\Module\Catalog\Application\Query\ProductAdminCriteria::class,
                'countForAdmin' => \App\Module\Catalog\Application\Query\ProductAdminCriteria::class,
                'listPublished' => \App\Module\Catalog\Application\Query\ProductCatalogCriteria::class,
                'listPublishedProjection' => \App\Module\Catalog\Application\Query\ProductCatalogCriteria::class,
                'countPublished' => \App\Module\Catalog\Application\Query\ProductCatalogCriteria::class,
                'collectPublishedFacets' => \App\Module\Catalog\Application\Query\ProductCatalogCriteria::class,
            ],
        ];
        $violations = [];

        foreach ($expectations as $class => $methods) {
            $reflection = new \ReflectionClass($class);
            foreach ($methods as $method => $criteriaClass) {
                $parameters = $reflection->getMethod($method)->getParameters();
                $type = $parameters[0]?->getType();
                if (1 !== count($parameters) || !$type instanceof \ReflectionNamedType || $criteriaClass !== $type->getName()) {
                    $violations[] = $class.'::'.$method;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testProductWriteHandlerSeparatesCacheInvalidationFromDatabaseTransaction(): void
    {
        $handler = file_get_contents(__DIR__.'/../../../src/Module/Catalog/Application/Handler/ProductWriteHandler.php');
        $execution = file_get_contents(__DIR__.'/../../../src/Module/Catalog/Application/Handler/ProductWriteExecution.php');
        self::assertIsString($handler);
        self::assertIsString($execution);

        self::assertStringNotContainsString('CacheItemPoolInterface', $handler);
        self::assertStringNotContainsString('->clear()', $handler);
        self::assertStringContainsString('ProductWriteExecution', $handler);
        self::assertStringContainsString('CatalogCacheInvalidator', $execution);
        self::assertStringContainsString('invalidateAfterWrite', $handler);
    }

    public function testMarketingCampaignLaunchUsesOutboxInsteadOfSynchronousRecipientPreparation(): void
    {
        $sender = file_get_contents(__DIR__.'/../../../src/Module/Marketing/Application/Notification/MarketingCampaignSender.php');
        $prepareHandler = file_get_contents(__DIR__.'/../../../src/Module/Marketing/Application/Outbox/PrepareMarketingCampaignHandler.php');
        $dispatchHandler = file_get_contents(__DIR__.'/../../../src/Module/Marketing/Application/Outbox/DispatchMarketingCampaignRecipientEmailHandler.php');
        self::assertIsString($sender);
        self::assertIsString($prepareHandler);
        self::assertIsString($dispatchHandler);

        self::assertStringContainsString('PrepareMarketingCampaignHandler::TYPE', $sender);
        self::assertStringContainsString('TransactionManager', $sender);
        self::assertStringNotContainsString('resolveRecipients(', $sender);
        self::assertStringNotContainsString('messageBus->dispatch', $sender);
        self::assertStringContainsString('resolveRecipientsAfterId', $prepareHandler);
        self::assertStringContainsString('findExistingUserIdsForCampaign', $prepareHandler);
        self::assertStringContainsString('recipientEmailKey', $prepareHandler);
        self::assertStringContainsString('AsyncMessageDispatcher', $dispatchHandler);
    }

    public function testBackendTransactionConventionIsDocumented(): void
    {
        $doc = file_get_contents(__DIR__.'/../../../../docs/backend-transaction-conventions.md');
        self::assertIsString($doc);

        self::assertStringContainsString('UnitOfWork::commit()', $doc);
        self::assertStringContainsString('TransactionManager::transactional()', $doc);
        self::assertStringContainsString('outbox event', $doc);
    }

    public function testSecureInvoiceStorageCommandDoesNotLoadAllOrders(): void
    {
        $command = file_get_contents(__DIR__.'/../../../src/Module/Order/Infrastructure/Command/SecureInvoiceStorageCommand.php');
        $repository = file_get_contents(__DIR__.'/../../../src/Module/Order/Infrastructure/Repository/OrderRepository.php');
        self::assertIsString($command);
        self::assertIsString($repository);

        self::assertStringNotContainsString('findAll()', $command);
        self::assertStringContainsString('findWithInvoiceDocumentsAfterId', $command);
        self::assertStringContainsString('after-id', $command);
        self::assertStringContainsString('o.id > :lastId', $repository);
    }

    public function testCatalogListReadsUseScalarProjectionAndVersionedCache(): void
    {
        $provider = file_get_contents(__DIR__.'/../../../src/Module/Catalog/Application/Provider/ProductCatalogSearchProvider.php');
        $repository = file_get_contents(__DIR__.'/../../../src/Module/Catalog/Infrastructure/Repository/ProductPublicQueries.php');
        $invalidator = file_get_contents(__DIR__.'/../../../src/Module/Catalog/Application/Cache/CatalogCacheInvalidator.php');
        self::assertIsString($provider);
        self::assertIsString($repository);
        self::assertIsString($invalidator);

        self::assertStringContainsString('listPublishedProjection', $provider);
        self::assertStringContainsString('ProductCatalogListProjectionFormatter', $provider);
        self::assertStringContainsString('findPublishedListProjection', $repository);
        self::assertStringContainsString('getArrayResult()', $repository);
        self::assertStringContainsString('CatalogCacheVersion', $provider);
        self::assertStringContainsString('current()', $provider);
        self::assertStringContainsString('bump($operation)', $invalidator);
        self::assertStringNotContainsString('->clear()', $invalidator);
    }

    public function testCatalogFormattersAreInjectedInsteadOfCalledStatically(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach ([
                'CatalogFormatter::',
                'OrderFormatter::',
                'ProductCatalogListProjectionFormatter::',
                'ProductReviewFormatter::',
                'PromotionFormatter::',
                'QuoteFormatter::',
                'ShippingAddressFormatter::',
                'TradeInFormatter::',
                'VoucherFormatter::',
            ] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testControllersDoNotCallExternalServicesDirectly(): void
    {
        $forbiddenPatterns = [
            '/\buse\s+Symfony\\\\Component\\\\Mailer\\\\MailerInterface;/',
            '/\buse\s+Symfony\\\\Contracts\\\\HttpClient\\\\HttpClientInterface;/',
            '/\buse\s+App\\\\Module\\\\Order\\\\Application\\\\Workflow\\\\StripeApiClient;/',
            '/\buse\s+App\\\\Module\\\\Order\\\\Application\\\\Port\\\\StripeRefundClient;/',
            '/->mailer->send\s*\(/',
            '/->send\s*\(\s*\$email/',
        ];
        $violations = [];

        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_ends_with($path, 'Controller.php')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach ($forbiddenPatterns as $pattern) {
                if (preg_match($pattern, $source, $match)) {
                    $violations[] = $this->relativePath($path).': '.$match[0];
                }
            }
        }

        self::assertSame([], $violations);
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

    public function testCheckoutLifecycleStatusIsBackedByEnum(): void
    {
        $state = file_get_contents(__DIR__.'/../../../src/Module/Order/Domain/Entity/CheckoutLifecycleState.php');
        self::assertIsString($state);

        self::assertFileExists(__DIR__.'/../../../src/Module/Order/Domain/Entity/CheckoutStatus.php');
        self::assertStringContainsString('enumType: CheckoutStatus::class', $state);
        self::assertStringContainsString('private CheckoutStatus $status', $state);
        self::assertStringNotContainsString('private string $status', $state);
    }

    public function testArchitectureNamingConventionDocumentsSuffixes(): void
    {
        $doc = file_get_contents(__DIR__.'/../../../docs/architecture-naming.md');
        self::assertIsString($doc);

        foreach (['DTO', 'Input', 'Command', 'Query', 'Result', 'ViewModel', 'ResponseMapper', 'Handler', 'Provider', 'Projection', 'Calculator', 'Policy', 'Workflow', 'Mapper', 'Gateway', 'Repository', 'Service', 'Manager'] as $suffix) {
            self::assertStringContainsString($suffix, $doc);
        }
    }

    public function testArchitectureDocumentsDescribeAdminRoleAndDomainInvariants(): void
    {
        $architecture = file_get_contents(__DIR__.'/../../../docs/architecture-naming.md');
        $invariants = file_get_contents(__DIR__.'/../../../docs/domain-invariants.md');
        self::assertIsString($architecture);
        self::assertIsString($invariants);
        self::assertStringContainsString('## Admin Module Role', $architecture);
        self::assertStringContainsString('Business invariants remain owned by the domain module', $architecture);
        self::assertStringContainsString('## Monetary and Numeric Values', $invariants);
        self::assertStringContainsString('InvoiceDocument', $invariants);
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
