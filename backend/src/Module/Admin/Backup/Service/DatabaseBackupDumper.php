<?php

declare(strict_types=1);

namespace App\Module\Admin\Backup\Service;

final readonly class DatabaseBackupDumper
{
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
        $process = proc_open(
            $command,
            [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->projectDir,
            ['MYSQL_PWD' => $database['password'], 'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'],
        );
        if (!is_resource($process)) {
            throw new \RuntimeException('Impossible de lancer mysqldump.');
        }
        $gzip = gzopen($targetPath, 'wb9');
        if (false === $gzip) {
            proc_terminate($process);
            throw new \RuntimeException('Impossible de créer le fichier de sauvegarde.');
        }
        while (!feof($pipes[1])) {
            $chunk = fread($pipes[1], 1024 * 1024);
            if (false === $chunk) {
                break;
            }
            gzwrite($gzip, $chunk);
        }
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[2]);
        gzclose($gzip);
        if (0 !== proc_close($process)) {
            throw new \RuntimeException(trim($stderr) ?: 'mysqldump a échoué.');
        }
    }

    public function isAvailable(): bool
    {
        return '' !== trim((string) shell_exec('command -v mysqldump 2>/dev/null'));
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
}
