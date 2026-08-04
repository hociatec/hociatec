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

        $unitOfWork = file_get_contents(__DIR__.'/../../../src/Infrastructure/Persistence/DoctrineUnitOfWork.php');
        self::assertIsString($unitOfWork);
        foreach (['implements TransactionManager', 'wrapInTransaction(', 'function transactional('] as $forbidden) {
            if (str_contains($unitOfWork, $forbidden)) {
                $violations[] = 'src/Infrastructure/Persistence/DoctrineUnitOfWork.php: '.$forbidden;
            }
        }

        self::assertSame([], $violations);
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
        $subscriber = file_get_contents(__DIR__.'/../../../src/Infrastructure/Http/ApiExceptionSubscriber.php');
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

    public function testSourceDoesNotCatchThrowableOrBaseException(): void
    {
        $allowed = [
            'src/Module/Outbox/Application/OutboxDispatcher.php',
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
            'src/Infrastructure/Http/AttachmentResponseFactory.php',
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

    private function relativePath(string $path): string
    {
        $root = realpath(__DIR__.'/../../../');
        $realPath = realpath($path);

        return is_string($root) && is_string($realPath) ? str_replace($root.'/', '', $realPath) : $path;
    }
}
