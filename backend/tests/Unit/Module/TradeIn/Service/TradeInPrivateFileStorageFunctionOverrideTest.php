<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Infrastructure\Storage {
    final class TradeInPrivateFileStorageTestState
    {
        public static bool $forceMkdirFailure = false;
        public static bool $forceWriteFailure = false;
        public static bool $forceReadFailure = false;
        public static bool $forceChmodFailure = false;
        public static bool $unlinkCalled = false;

        public static function reset(): void
        {
            self::$forceMkdirFailure = false;
            self::$forceWriteFailure = false;
            self::$forceReadFailure = false;
            self::$forceChmodFailure = false;
            self::$unlinkCalled = false;
        }
    }

    function mkdir(string $directory, int $permissions = 0777, bool $recursive = false): bool
    {
        if (TradeInPrivateFileStorageTestState::$forceMkdirFailure) {
            return false;
        }

        return \mkdir($directory, $permissions, $recursive);
    }

    function file_put_contents(string $filename, mixed $data, int $flags = 0, $context = null): int|false
    {
        if (TradeInPrivateFileStorageTestState::$forceWriteFailure) {
            return false;
        }

        return null === $context
            ? \file_put_contents($filename, $data, $flags)
            : \file_put_contents($filename, $data, $flags, $context);
    }

    function file_get_contents(string $filename, bool $use_include_path = false, $context = null, int $offset = 0, ?int $length = null): string|false
    {
        if (TradeInPrivateFileStorageTestState::$forceReadFailure && str_contains($filename, 'var/private/trade-ins/')) {
            return false;
        }

        if (null === $context) {
            return null === $length
                ? \file_get_contents($filename, $use_include_path, null, $offset)
                : \file_get_contents($filename, $use_include_path, null, $offset, $length);
        }

        return null === $length
            ? \file_get_contents($filename, $use_include_path, $context, $offset)
            : \file_get_contents($filename, $use_include_path, $context, $offset, $length);
    }

    function chmod(string $filename, int $permissions): bool
    {
        if (TradeInPrivateFileStorageTestState::$forceChmodFailure) {
            return false;
        }

        return \chmod($filename, $permissions);
    }

    function unlink(string $filename): bool
    {
        TradeInPrivateFileStorageTestState::$unlinkCalled = true;

        return \unlink($filename);
    }
}

namespace App\Tests\Unit\Module\TradeIn\Service {

    use App\Module\TradeIn\Infrastructure\Storage\TradeInPrivateFileStorage;
    use App\Module\TradeIn\Infrastructure\Storage\TradeInPrivateFileStorageTestState;
    use PHPUnit\Framework\TestCase;
    use Symfony\Component\HttpFoundation\File\UploadedFile;

    final class TradeInPrivateFileStorageFunctionOverrideTest extends TestCase
    {
        protected function tearDown(): void
        {
            TradeInPrivateFileStorageTestState::reset();
        }

        public function testStoreRibRejectsOversizedAndInvalidUploadedFiles(): void
        {
            $projectDir = $this->temporaryProjectDir();
            $storage = new TradeInPrivateFileStorage($projectDir);
            $pdfPath = $projectDir.'/large.pdf';
            file_put_contents($pdfPath, '%PDF-large');

            try {
                $storage->storeRib(new class($pdfPath) extends UploadedFile {
                    public function __construct(string $path)
                    {
                        parent::__construct($path, 'large.pdf', 'application/pdf', null, true);
                    }

                    public function getSize(): int
                    {
                        return 5_242_881;
                    }

                    public function getMimeType(): ?string
                    {
                        return 'application/pdf';
                    }
                });
                self::fail('Expected oversized RIB rejection.');
            } catch (\InvalidArgumentException $exception) {
                self::assertSame('Le RIB PDF est invalide ou dépasse 5 Mo.', $exception->getMessage());
            }

            try {
                $storage->storeRib(new class($pdfPath) extends UploadedFile {
                    public function __construct(string $path)
                    {
                        parent::__construct($path, 'invalid.pdf', 'application/pdf', null, true);
                    }

                    public function isValid(): bool
                    {
                        return false;
                    }
                });
                self::fail('Expected invalid upload rejection.');
            } catch (\InvalidArgumentException $exception) {
                self::assertSame('Le RIB PDF est invalide ou dépasse 5 Mo.', $exception->getMessage());
            }
        }

        public function testTradeInPrivateFileStorageReportsMkdirWriteReadAndSecureFailures(): void
        {
            $projectDir = $this->temporaryProjectDir();
            $storage = new TradeInPrivateFileStorage($projectDir);
            $pdfPath = $projectDir.'/rib.pdf';
            file_put_contents($pdfPath, '%PDF-secure');
            $upload = new UploadedFile($pdfPath, 'rib.pdf', 'application/pdf', null, true);

            TradeInPrivateFileStorageTestState::$forceMkdirFailure = true;
            try {
                $storage->storeReceipt('%PDF-generated');
                self::fail('Expected receipt mkdir failure.');
            } catch (\RuntimeException $exception) {
                self::assertSame('Impossible de préparer le stockage sécurisé du justificatif.', $exception->getMessage());
            }

            TradeInPrivateFileStorageTestState::reset();
            TradeInPrivateFileStorageTestState::$forceWriteFailure = true;
            try {
                $storage->storeRib($upload);
                self::fail('Expected RIB write failure.');
            } catch (\RuntimeException $exception) {
                self::assertSame('Impossible d’enregistrer le RIB.', $exception->getMessage());
            }

            TradeInPrivateFileStorageTestState::reset();
            $relativePath = $storage->storeReceipt('%PDF-readable');
            TradeInPrivateFileStorageTestState::$forceReadFailure = true;
            try {
                $storage->read($relativePath);
                self::fail('Expected read failure.');
            } catch (\RuntimeException $exception) {
                self::assertSame('Document privé illisible.', $exception->getMessage());
            }

            TradeInPrivateFileStorageTestState::reset();
            TradeInPrivateFileStorageTestState::$forceChmodFailure = true;
            try {
                $storage->storeReceipt('%PDF-secure-failure');
                self::fail('Expected secure failure.');
            } catch (\RuntimeException $exception) {
                self::assertSame('Impossible de sécuriser le document privé.', $exception->getMessage());
            }
            self::assertTrue(TradeInPrivateFileStorageTestState::$unlinkCalled);
        }

        private function temporaryProjectDir(): string
        {
            $projectDir = sys_get_temp_dir().'/hociatec-trade-in-override-'.bin2hex(random_bytes(4));
            mkdir($projectDir, 0777, true);

            return $projectDir;
        }
    }
}
