<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Backup\Workflow;

final readonly class BackupEncryptionService
{
    private const MAGIC = "HOCIATEC-BACKUP-V1\n";
    private const CHUNK_SIZE = 1024 * 1024;

    public function __construct(private string $keyFile)
    {
    }

    public function encryptFile(string $sourcePath, string $targetPath): void
    {
        $temporaryPath = $targetPath.'.tmp';
        $completed = false;
        $source = fopen($sourcePath, 'rb');
        if (false === $source) {
            throw new \RuntimeException('Impossible de lire la sauvegarde à chiffrer.');
        }

        $target = fopen($temporaryPath, 'wb');
        if (false === $target) {
            fclose($source);
            throw new \RuntimeException('Impossible de créer la sauvegarde chiffrée.');
        }

        try {
            $key = $this->key();
            [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
            $this->writeAll($target, self::MAGIC.$header);

            do {
                $chunk = fread($source, self::CHUNK_SIZE);
                if (false === $chunk) {
                    throw new \RuntimeException('Lecture de sauvegarde interrompue.');
                }
                $tag = feof($source)
                    ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
                    : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
                $encrypted = sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, '', $tag);
                $this->writeAll($target, $encrypted);
            } while (!feof($source));

            fclose($source);
            fclose($target);
            $source = false;
            $target = false;
            if (!chmod($temporaryPath, 0640)) {
                throw new \RuntimeException('Impossible de sécuriser la sauvegarde chiffrée.');
            }
            if (!rename($temporaryPath, $targetPath)) {
                throw new \RuntimeException('Impossible de finaliser la sauvegarde chiffrée.');
            }
            $completed = true;
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($target)) {
                fclose($target);
            }
            if (!$completed && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    public function decryptFile(string $sourcePath, string $targetPath): void
    {
        $temporaryPath = $targetPath.'.tmp';
        $completed = false;
        $source = fopen($sourcePath, 'rb');
        if (false === $source) {
            throw new \RuntimeException('Impossible de lire la sauvegarde chiffrée.');
        }

        $target = fopen($temporaryPath, 'wb');
        if (false === $target) {
            fclose($source);
            throw new \RuntimeException('Impossible de créer la sauvegarde restaurée.');
        }

        try {
            $magic = fread($source, strlen(self::MAGIC));
            if (self::MAGIC !== $magic) {
                throw new \RuntimeException('Format de sauvegarde chiffrée invalide.');
            }

            $headerLength = SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES;
            $header = fread($source, $headerLength);
            if (false === $header || strlen($header) !== $headerLength) {
                throw new \RuntimeException('En-tête de sauvegarde chiffrée incomplet.');
            }

            $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $this->key());
            $cipherChunkSize = self::CHUNK_SIZE + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES;
            $finalChunkSeen = false;

            while (!feof($source)) {
                $chunk = fread($source, $cipherChunkSize);
                if (false === $chunk) {
                    throw new \RuntimeException('Lecture de sauvegarde chiffrée interrompue.');
                }
                if ('' === $chunk) {
                    continue;
                }

                $decrypted = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $chunk);
                if (false === $decrypted) {
                    throw new \RuntimeException('Le déchiffrement de la sauvegarde a échoué.');
                }

                [$plainChunk, $tag] = $decrypted;
                $this->writeAll($target, $plainChunk);
                if (SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL === $tag) {
                    $finalChunkSeen = true;
                }
            }

            if (!$finalChunkSeen) {
                throw new \RuntimeException('Balise finale de sauvegarde chiffrée absente.');
            }

            fclose($source);
            fclose($target);
            $source = false;
            $target = false;
            if (!chmod($temporaryPath, 0640)) {
                throw new \RuntimeException('Impossible de sécuriser la sauvegarde restaurée.');
            }
            if (!rename($temporaryPath, $targetPath)) {
                throw new \RuntimeException('Impossible de finaliser la sauvegarde restaurée.');
            }
            $completed = true;
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($target)) {
                fclose($target);
            }
            if (!$completed && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private function key(): string
    {
        $encoded = is_file($this->keyFile) ? file_get_contents($this->keyFile) : false;
        $key = false !== $encoded ? sodium_base642bin(trim($encoded), SODIUM_BASE64_VARIANT_ORIGINAL) : false;
        if (false === $key || SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES !== strlen($key)) {
            throw new \RuntimeException('La clé de chiffrement des sauvegardes est absente ou invalide.');
        }

        return $key;
    }

    /**
     * @param resource $stream
     */
    private function writeAll($stream, string $data): void
    {
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            $written = fwrite($stream, substr($data, $offset));
            if (false === $written || 0 === $written) {
                throw new \RuntimeException('Écriture de sauvegarde chiffrée interrompue.');
            }

            $offset += $written;
        }
    }
}
