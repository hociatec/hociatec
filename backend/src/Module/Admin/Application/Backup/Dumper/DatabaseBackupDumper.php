<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Backup\Dumper;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

final readonly class DatabaseBackupDumper
{
    private const PROCESS_TIMEOUT_SECONDS = 900;

    public function __construct(private string $projectDir, private string $databaseUrl)
    {
    }

    public function dump(string $targetPath): void
    {
        $database = $this->configuration();
        if (!$this->isAvailable()) {
            throw new \RuntimeException('mysqldump est introuvable sur le serveur.');
        }
        $command = ['mysqldump', '-u'.$database['user'], '-h'.$database['host'], '--single-transaction', '--routines', '--triggers', $database['name']];
        if (null !== $database['port']) {
            array_splice($command, 4, 0, ['-P'.$database['port']]);
        }
        $tempPath = $targetPath.'.part';
        $this->removePartialFile($tempPath);

        $gzip = gzopen($tempPath, 'wb9');
        if (false === $gzip) {
            throw new \RuntimeException('Impossible de créer le fichier de sauvegarde.');
        }

        $stderr = '';
        $writeFailed = false;
        $process = new Process(
            $command,
            $this->projectDir,
            ['MYSQL_PWD' => $database['password'], 'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'],
        );
        $process->setInput('');
        $process->setTimeout(self::PROCESS_TIMEOUT_SECONDS);

        $runException = null;
        try {
            $process->run(function (string $type, string $buffer) use ($gzip, &$stderr, &$writeFailed): void {
                if (Process::ERR === $type) {
                    $stderr .= $buffer;

                    return;
                }

                if ('' !== $buffer && false === gzwrite($gzip, $buffer)) {
                    $writeFailed = true;
                    throw new \RuntimeException('Impossible d\'écrire le fichier de sauvegarde.');
                }
            });
        } catch (ProcessTimedOutException $exception) {
            $runException = new \RuntimeException('mysqldump a dépassé le délai autorisé.', 0, $exception);
        } catch (\RuntimeException $exception) {
            $runException = $exception;
        } finally {
            if (!gzclose($gzip)) {
                $writeFailed = true;
            }
        }

        if (null !== $runException) {
            $this->removePartialFile($tempPath);
            throw $runException;
        }

        if ($writeFailed || !$process->isSuccessful()) {
            $this->removePartialFile($tempPath);
            throw new \RuntimeException(trim($stderr) ?: 'mysqldump a échoué.');
        }

        if (!is_file($tempPath) || 0 >= filesize($tempPath)) {
            $this->removePartialFile($tempPath);
            throw new \RuntimeException('La sauvegarde générée est vide.');
        }

        if (!rename($tempPath, $targetPath)) {
            $this->removePartialFile($tempPath);
            throw new \RuntimeException('Impossible de finaliser le fichier de sauvegarde.');
        }
    }

    public function isAvailable(): bool
    {
        try {
            $this->configuration();
        } catch (\RuntimeException) {
            return false;
        }

        $process = new Process(
            ['mysqldump', '--version'],
            $this->projectDir,
            ['PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'],
        );
        $process->setTimeout(5);

        try {
            $process->run();
        } catch (\RuntimeException) {
            return false;
        }

        return $process->isSuccessful();
    }

    /** @return array{user: string, password: string, host: string, port: int|null, name: string} */
    private function configuration(): array
    {
        $parts = parse_url($this->databaseUrl);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'mysql') {
            throw new \RuntimeException('DATABASE_URL doit utiliser le protocole mysql.');
        }
        $name = isset($parts['path']) ? ltrim((string) $parts['path'], '/') : '';
        if ('' === $name || !isset($parts['user'], $parts['host'])) {
            throw new \RuntimeException('DATABASE_URL est incomplet.');
        }

        return [
            'user' => rawurldecode((string) $parts['user']),
            'password' => rawurldecode((string) ($parts['pass'] ?? '')),
            'host' => (string) $parts['host'],
            'port' => isset($parts['port']) ? (int) $parts['port'] : null,
            'name' => rawurldecode($name),
        ];
    }

    private function removePartialFile(string $path): void
    {
        if (is_file($path) && !unlink($path)) {
            throw new \RuntimeException('Impossible de supprimer le fichier temporaire de sauvegarde.');
        }
    }
}
