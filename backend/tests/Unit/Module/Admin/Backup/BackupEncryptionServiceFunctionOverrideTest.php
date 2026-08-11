<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Backup\Workflow {
    final class BackupEncryptionServiceTestState
    {
        public static bool $forceWriteFailure = false;
        public static bool $forceRenameFailure = false;

        public static function reset(): void
        {
            self::$forceWriteFailure = false;
            self::$forceRenameFailure = false;
        }
    }

    function fwrite($stream, string $data, ?int $length = null): int|false
    {
        if (BackupEncryptionServiceTestState::$forceWriteFailure) {
            return false;
        }

        return null === $length ? \fwrite($stream, $data) : \fwrite($stream, $data, $length);
    }

    function rename(string $from, string $to): bool
    {
        if (BackupEncryptionServiceTestState::$forceRenameFailure) {
            return false;
        }

        return \rename($from, $to);
    }
}

namespace App\Tests\Unit\Module\Admin\Backup {

    use App\Module\Admin\Application\Backup\Workflow\BackupEncryptionService;
    use App\Module\Admin\Application\Backup\Workflow\BackupEncryptionServiceTestState;
    use PHPUnit\Framework\TestCase;

    final class BackupEncryptionServiceFunctionOverrideTest extends TestCase
    {
        protected function tearDown(): void
        {
            BackupEncryptionServiceTestState::reset();
        }

        public function testEncryptFileReportsInterruptedWriteAndCleansTemporaryFile(): void
        {
            $projectDir = $this->projectDir();
            $source = $projectDir.'/var/backups/source.sql.gz';
            $target = $projectDir.'/var/backups/source.sql.gz.enc';
            file_put_contents($source, 'plain');

            BackupEncryptionServiceTestState::$forceWriteFailure = true;

            try {
                (new BackupEncryptionService($this->keyFile($projectDir)))->encryptFile($source, $target);
                self::fail('Expected encrypted backup write failure.');
            } catch (\RuntimeException $exception) {
                self::assertSame('Écriture de sauvegarde chiffrée interrompue.', $exception->getMessage());
            }

            self::assertFileDoesNotExist($target);
            self::assertFileDoesNotExist($target.'.tmp');
        }

        public function testEncryptFileReportsFinalizeFailureAndCleansTemporaryFile(): void
        {
            $projectDir = $this->projectDir();
            $source = $projectDir.'/var/backups/source.sql.gz';
            $target = $projectDir.'/var/backups/source.sql.gz.enc';
            file_put_contents($source, 'plain');

            BackupEncryptionServiceTestState::$forceRenameFailure = true;

            try {
                (new BackupEncryptionService($this->keyFile($projectDir)))->encryptFile($source, $target);
                self::fail('Expected encrypted backup finalize failure.');
            } catch (\RuntimeException $exception) {
                self::assertSame('Impossible de finaliser la sauvegarde chiffrée.', $exception->getMessage());
            }

            self::assertFileDoesNotExist($target);
            self::assertFileDoesNotExist($target.'.tmp');
        }

        private function projectDir(): string
        {
            $dir = sys_get_temp_dir().'/hociatec-backup-encryption-'.bin2hex(random_bytes(4));
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
}
