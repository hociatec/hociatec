<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Backup;

use App\Module\Admin\Application\Backup\Handler\RunBackupHandler;
use App\Module\Admin\Application\Backup\Port\DatabaseBackupDumper;
use App\Module\Admin\Application\Backup\Provider\BackupStatusProvider;
use App\Module\Admin\Application\Backup\State\BackupStateStore;
use App\Module\Admin\Application\Backup\Storage\BackupFileStorage;
use App\Module\Admin\Application\Backup\Workflow\BackupEncryptionService;
use App\Module\Admin\Application\Backup\Workflow\MaintenanceModeService;
use App\Module\Admin\Infrastructure\Backup\Dumper\DatabaseBackupDumper as InfrastructureDatabaseBackupDumper;
use PHPUnit\Framework\TestCase;

final class BackupWorkflowCoverageTest extends TestCase
{
    public function testRunBackupHandlerCompletesSuccessfulRunAndPersistsState(): void
    {
        $projectDir = $this->projectDir();
        $states = new BackupStateStore($projectDir);
        $files = new BackupFileStorage($projectDir);
        $maintenance = new MaintenanceModeService($projectDir);
        $database = new class implements DatabaseBackupDumper {
            public function dump(string $targetPath): void
            {
                file_put_contents($targetPath, 'plain-backup');
            }

            public function isAvailable(): bool
            {
                return true;
            }
        };

        $handler = new RunBackupHandler(
            $states,
            $files,
            new BackupEncryptionService($this->keyFile($projectDir)),
            $database,
            new BackupStatusProvider($projectDir, $maintenance, $states, $files, $database),
        );

        $status = $handler->run('scheduler');

        self::assertSame('scheduler', $status['history'][0]['trigger']);
        self::assertSame('success', $status['history'][0]['status']);
        self::assertSame('Sauvegarde terminée.', $status['history'][0]['message']);
        self::assertNotNull($status['settings']['lastSuccessfulRunAt']);
        self::assertCount(1, $status['backups']);
        self::assertStringEndsWith('.sql.gz.enc', $status['backups'][0]['filename']);
    }

    public function testRunBackupHandlerCleansFilesAndRecordsFailureWhenDumpFails(): void
    {
        $projectDir = $this->projectDir();
        $states = new BackupStateStore($projectDir);
        $files = new BackupFileStorage($projectDir);
        $maintenance = new MaintenanceModeService($projectDir);
        $database = new class implements DatabaseBackupDumper {
            public function dump(string $targetPath): void
            {
                file_put_contents($targetPath, 'partial-backup');
                throw new \RuntimeException('dump failed');
            }

            public function isAvailable(): bool
            {
                return true;
            }
        };

        $handler = new RunBackupHandler(
            $states,
            $files,
            new BackupEncryptionService($this->keyFile($projectDir)),
            $database,
            new BackupStatusProvider($projectDir, $maintenance, $states, $files, $database),
        );

        try {
            $handler->run();
            self::fail('Expected dump failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('dump failed', $exception->getMessage());
        }

        $state = $states->read();
        self::assertSame('failed', $state['history'][0]['status']);
        self::assertSame('dump failed', $state['history'][0]['message']);
        self::assertSame([], $files->list());
        self::assertSame([], glob($projectDir.'/var/backups/*.plain') ?: []);
    }

    public function testRunBackupHandlerRejectsConcurrentLock(): void
    {
        $projectDir = $this->projectDir();
        $states = new BackupStateStore($projectDir);
        $files = new BackupFileStorage($projectDir);
        $maintenance = new MaintenanceModeService($projectDir);
        $database = new class implements DatabaseBackupDumper {
            public function dump(string $targetPath): void
            {
                file_put_contents($targetPath, 'plain-backup');
            }

            public function isAvailable(): bool
            {
                return true;
            }
        };

        $handler = new RunBackupHandler(
            $states,
            $files,
            new BackupEncryptionService($this->keyFile($projectDir)),
            $database,
            new BackupStatusProvider($projectDir, $maintenance, $states, $files, $database),
        );

        $lock = fopen($files->lockFile(), 'c');
        self::assertIsResource($lock);
        flock($lock, LOCK_EX | LOCK_NB);

        try {
            $handler->run();
            self::fail('Expected concurrent backup exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Une sauvegarde est déjà en cours.', $exception->getMessage());
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function testBackupEncryptionServicePrivateHelpersCoverKeyAndWriteAll(): void
    {
        $projectDir = $this->projectDir();
        $keyFile = $this->keyFile($projectDir);
        $service = new BackupEncryptionService($keyFile);
        $reflection = new \ReflectionObject($service);

        $key = $reflection->getMethod('key');
        $key->setAccessible(true);
        self::assertSame(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES, strlen($key->invoke($service)));

        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);
        $writeAll = $reflection->getMethod('writeAll');
        $writeAll->setAccessible(true);
        $writeAll->invoke($service, $stream, 'abc123');
        rewind($stream);
        self::assertSame('abc123', stream_get_contents($stream));
        fclose($stream);

        $source = $projectDir.'/var/backups/missing.sql.gz';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Impossible de lire la sauvegarde à chiffrer.');
        $service->encryptFile($source, $projectDir.'/var/backups/target.enc');
    }

    public function testDatabaseBackupDumperPrivateConfigurationParsesMysqlUrl(): void
    {
        $dumper = new InfrastructureDatabaseBackupDumper('/tmp', 'mysql://user%40mail:pa%24%24@db.example.test:3307/app%2Dprod');
        $reflection = new \ReflectionObject($dumper);

        $configuration = $reflection->getMethod('configuration');
        $configuration->setAccessible(true);
        self::assertSame([
            'user' => 'user@mail',
            'password' => 'pa$$',
            'host' => 'db.example.test',
            'port' => 3307,
            'name' => 'app-prod',
        ], $configuration->invoke($dumper));

        $removePartialFile = $reflection->getMethod('removePartialFile');
        $removePartialFile->setAccessible(true);
        $path = sys_get_temp_dir().'/hociatec-backup-partial-'.bin2hex(random_bytes(4));
        $removePartialFile->invoke($dumper, $path);
        self::assertFileDoesNotExist($path);
    }

    private function projectDir(): string
    {
        $dir = sys_get_temp_dir().'/hociatec-backup-coverage-'.bin2hex(random_bytes(4));
        mkdir($dir.'/var/backups', 0777, true);

        return $dir;
    }

    private function keyFile(string $projectDir): string
    {
        $path = $projectDir.'/var/backup.key';
        file_put_contents(
            $path,
            sodium_bin2base64(
                random_bytes(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES),
                SODIUM_BASE64_VARIANT_ORIGINAL,
            ),
        );

        return $path;
    }
}
