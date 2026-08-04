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

            foreach (['EntityManagerInterface', 'DoctrinePersistence', '->persist(', '->flush(', '->remove('] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testSourceDoesNotCatchThrowableOrBaseException(): void
    {
        $violations = [];
        foreach ($this->phpFiles(__DIR__.'/../../../src') as $path) {
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
        foreach ($this->phpFiles(__DIR__.'/../../../src/Module/BetaTest/Entity') as $path) {
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
            'src/Module/Auth/Controller/ProfileController.php',
        ];
        $violations = [];

        foreach ($this->phpFiles(__DIR__.'/../../../src/Module') as $path) {
            if (!str_ends_with($path, 'Controller.php') || in_array($this->relativePath($path), $allowed, true)) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertIsString($source);

            foreach (['getRoles(', "isGranted('ROLE_", '->getUser()->getId() !==', '->getUser()->getId() ===', '->getUser() !==', '->getUser() ==='] as $forbidden) {
                if (str_contains($source, $forbidden)) {
                    $violations[] = $this->relativePath($path).': '.$forbidden;
                }
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
