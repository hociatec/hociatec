<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Backup;

use App\Module\Admin\Application\Backup\Handler\RunBackupHandler;
use App\Module\Admin\Application\Backup\Handler\RunDueBackupsHandler;
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

    public function testDatabaseBackupDumperAvailabilityAndDumpFailureUseRealMysqldumpBinary(): void
    {
        $projectDir = $this->projectDir();
        $targetPath = $projectDir.'/var/backups/database.sql.gz';
        $dumper = new InfrastructureDatabaseBackupDumper($projectDir, 'mysql://user:pass@127.0.0.1:9/app');

        self::assertTrue($dumper->isAvailable());

        try {
            $dumper->dump($targetPath);
            self::fail('Expected mysqldump connection failure.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('Can\'t connect to MySQL server', $exception->getMessage());
        }

        self::assertFileDoesNotExist($targetPath);
        self::assertFileDoesNotExist($targetPath.'.part');
    }

    public function testRunDueBackupsHandlerTriggersOnlyWhenBackupIsDue(): void
    {
        $projectDir = $this->projectDir();
        $states = new BackupStateStore($projectDir);

        $database = new class implements DatabaseBackupDumper {
            public int $runs = 0;

            public function dump(string $targetPath): void
            {
                ++$this->runs;
                file_put_contents($targetPath, 'plain-backup');
            }

            public function isAvailable(): bool
            {
                return true;
            }
        };
        $handler = new RunBackupHandler(
            $states,
            new BackupFileStorage($projectDir),
            new BackupEncryptionService($this->keyFile($projectDir)),
            $database,
            new BackupStatusProvider($projectDir, new MaintenanceModeService($projectDir), $states, new BackupFileStorage($projectDir), $database),
        );
        $runDue = new RunDueBackupsHandler($states, $handler);

        $states->write(['settings' => ['enabled' => true, 'intervalHours' => 24, 'retentionCount' => 2], 'history' => []]);
        self::assertIsArray($runDue->runDue());
        self::assertSame(1, $database->runs);

        $states->write([
            'settings' => ['enabled' => true, 'intervalHours' => 24, 'retentionCount' => 2],
            'history' => [],
            'lastSuccessfulRunAt' => (new \DateTimeImmutable('-1 hour'))->format(\DateTimeInterface::ATOM),
        ]);
        self::assertNull($runDue->runDue());
        self::assertSame(1, $database->runs);
    }

    public function testBackupEncryptionServiceEncryptsFileAndStateStoreTrimsAndDeduplicatesHistory(): void
    {
        $projectDir = $this->projectDir();
        $source = $projectDir.'/var/backups/source.sql.gz';
        $target = $projectDir.'/var/backups/source.sql.gz.enc';
        file_put_contents($source, 'plain backup payload');

        (new BackupEncryptionService($this->keyFile($projectDir)))->encryptFile($source, $target);

        self::assertFileExists($target);
        self::assertFileDoesNotExist($target.'.tmp');
        self::assertSame('640', substr(sprintf('%o', fileperms($target)), -3));
        self::assertStringStartsWith("HOCIATEC-BACKUP-V1\n", (string) file_get_contents($target));

        $states = new BackupStateStore($projectDir);
        $state = ['settings' => $states->settings([]), 'history' => []];
        for ($i = 0; $i < 85; ++$i) {
            $state = $states->recordRun(['id' => 'run-'.$i, 'status' => 'success'], $state);
        }

        self::assertCount(80, $state['history']);
        self::assertSame('run-84', $state['history'][0]['id']);
        self::assertSame('run-5', $state['history'][79]['id']);

        $deduplicated = $states->recordRun(['id' => 'run-42', 'status' => 'replayed'], $state);
        self::assertSame('run-42', $deduplicated['history'][0]['id']);
        self::assertSame('replayed', $deduplicated['history'][0]['status']);
        self::assertCount(80, $deduplicated['history']);
    }

    public function testEncryptedBackupCanBeDecryptedAndRestoredToSqlPayload(): void
    {
        $projectDir = $this->projectDir();
        $keyFile = $this->keyFile($projectDir);
        $source = $projectDir.'/var/backups/restore-check.sql.gz';
        $target = $source.'.enc';
        $sql = "CREATE TABLE backup_restore_check (id INT PRIMARY KEY);\nINSERT INTO backup_restore_check VALUES (1);\n";
        file_put_contents($source, gzencode($sql, 9));

        (new BackupEncryptionService($keyFile))->encryptFile($source, $target);

        $restoredArchive = $this->decryptBackup($target, $keyFile);
        self::assertSame($sql, gzdecode($restoredArchive));
    }

    public function testBackupStateStoreHandlesInvalidJsonAndOutputScheduleVariants(): void
    {
        $projectDir = $this->projectDir();
        file_put_contents($projectDir.'/var/backups/backup-state.json', '{invalid');
        $states = new BackupStateStore($projectDir);

        self::assertSame(['settings' => $states->settings([]), 'history' => []], $states->read());

        $disabled = $states->outputSettings($states->settings(['enabled' => false]), []);
        self::assertFalse($disabled['enabled']);
        self::assertNull($disabled['nextRunAt']);

        $enabled = $states->outputSettings(
            $states->settings(['enabled' => true, 'intervalHours' => 12, 'retentionCount' => 2]),
            ['lastSuccessfulRunAt' => '2026-08-01T10:00:00+00:00'],
        );
        self::assertSame('2026-08-01T22:00:00+00:00', $enabled['nextRunAt']);
    }

    public function testBackupEncryptionServiceRejectsUnreadableSourceFile(): void
    {
        $projectDir = $this->projectDir();
        $service = new BackupEncryptionService($this->keyFile($projectDir));
        set_error_handler(static fn (): bool => true);
        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Impossible de lire la sauvegarde à chiffrer.');

            $service->encryptFile($projectDir.'/var/backups/missing.sql.gz', $projectDir.'/var/backups/missing.sql.gz.enc');
        } finally {
            restore_error_handler();
        }
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

    private function decryptBackup(string $encryptedPath, string $keyFile): string
    {
        $payload = file_get_contents($encryptedPath);
        $encodedKey = file_get_contents($keyFile);
        self::assertIsString($payload);
        self::assertIsString($encodedKey);
        self::assertStringStartsWith("HOCIATEC-BACKUP-V1\n", $payload);

        $payload = substr($payload, strlen("HOCIATEC-BACKUP-V1\n"));
        self::assertNotFalse($payload);

        $headerLength = SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES;
        $header = substr($payload, 0, $headerLength);
        $ciphertext = substr($payload, $headerLength);
        self::assertNotFalse($header);
        self::assertNotFalse($ciphertext);

        $key = sodium_base642bin(trim($encodedKey), SODIUM_BASE64_VARIANT_ORIGINAL);
        $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);

        $plaintext = '';
        $offset = 0;
        $chunkSize = 1024 * 1024 + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES;
        $length = strlen($ciphertext);

        while ($offset < $length) {
            $chunk = substr($ciphertext, $offset, min($chunkSize, $length - $offset));
            $offset += strlen($chunk);
            $decrypted = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $chunk);
            self::assertIsArray($decrypted);
            [$message, $tag] = $decrypted;
            $plaintext .= $message;

            if (SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL === $tag) {
                self::assertSame($length, $offset);
            }
        }

        return $plaintext;
    }
}
