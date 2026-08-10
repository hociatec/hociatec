<?php

declare(strict_types=1);

namespace App\Module\Admin\Infrastructure\Backup\Dumper {
    final class DatabaseBackupDumperTestState
    {
        public static bool $forceRenameFailure = false;
        public static bool $forceUnlinkFailure = false;
        public static bool $forceGzWriteFailure = false;
        public static bool $forceGzCloseFailure = false;
        public static bool $forceEmptyFileSize = false;

        public static function reset(): void
        {
            self::$forceRenameFailure = false;
            self::$forceUnlinkFailure = false;
            self::$forceGzWriteFailure = false;
            self::$forceGzCloseFailure = false;
            self::$forceEmptyFileSize = false;
        }
    }

    function gzwrite($stream, string $data, ?int $length = null): int|false
    {
        if (DatabaseBackupDumperTestState::$forceGzWriteFailure) {
            return false;
        }

        return null === $length ? \gzwrite($stream, $data) : \gzwrite($stream, $data, $length);
    }

    function gzclose($stream): bool
    {
        if (DatabaseBackupDumperTestState::$forceGzCloseFailure) {
            \gzclose($stream);

            return false;
        }

        return \gzclose($stream);
    }

    function rename(string $from, string $to): bool
    {
        if (DatabaseBackupDumperTestState::$forceRenameFailure) {
            return false;
        }

        return \rename($from, $to);
    }

    function unlink(string $filename): bool
    {
        if (DatabaseBackupDumperTestState::$forceUnlinkFailure) {
            return false;
        }

        return \unlink($filename);
    }

    function filesize(string $filename): int|false
    {
        if (DatabaseBackupDumperTestState::$forceEmptyFileSize) {
            return 0;
        }

        return \filesize($filename);
    }
}

namespace App\Tests\Unit\Module\Admin\Backup {

    use App\Module\Admin\Infrastructure\Backup\Dumper\DatabaseBackupDumper;
    use App\Module\Admin\Infrastructure\Backup\Dumper\DatabaseBackupDumperTestState;
    use PHPUnit\Framework\TestCase;

    final class DatabaseBackupDumperFunctionOverrideTest extends TestCase
    {
        protected function tearDown(): void
        {
            DatabaseBackupDumperTestState::reset();
        }

        public function testDumpReportsTemporaryCleanupFailureWhenPartialFileCannotBeDeleted(): void
        {
            $projectDir = $this->projectDir();
            $targetPath = $projectDir.'/var/backups/cleanup-failure.sql.gz';
            file_put_contents($targetPath.'.part', 'stale');
            DatabaseBackupDumperTestState::$forceUnlinkFailure = true;

            try {
                (new DatabaseBackupDumper($projectDir, 'mysql://user:pass@127.0.0.1:9/app'))->dump($targetPath);
                self::fail('Expected partial cleanup failure.');
            } catch (\RuntimeException $exception) {
                self::assertSame('Impossible de supprimer le fichier temporaire de sauvegarde.', $exception->getMessage());
            }
        }

        private function projectDir(): string
        {
            $dir = sys_get_temp_dir().'/hociatec-backup-dumper-'.bin2hex(random_bytes(4));
            mkdir($dir.'/var/backups', 0777, true);

            return $dir;
        }
    }
}
