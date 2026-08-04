<?php

declare(strict_types=1);

namespace App\Module\Admin\Backup\Service;

final readonly class BackupEncryptionService
{
    private const MAGIC = "HOCIATEC-BACKUP-V1\n";
    private const CHUNK_SIZE = 1024 * 1024;

    public function __construct(private string $keyFile)
    {
    }

    public function encryptFile(string $sourcePath, string $targetPath): void
    {
        $source = fopen($sourcePath, 'rb');
        if (false === $source) {
            throw new \RuntimeException('Impossible de lire la sauvegarde à chiffrer.');
        }

        $temporaryPath = $targetPath.'.tmp';
        $target = fopen($temporaryPath, 'wb');
        if (false === $target) {
            fclose($source);
            throw new \RuntimeException('Impossible de créer la sauvegarde chiffrée.');
        }

        try {
            $key = $this->key();
            [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
            fwrite($target, self::MAGIC.$header);

            do {
                $chunk = fread($source, self::CHUNK_SIZE);
                if (false === $chunk) {
                    throw new \RuntimeException('Lecture de sauvegarde interrompue.');
                }
                $tag = feof($source)
                    ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
                    : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
                $encrypted = sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, '', $tag);
                if (false === fwrite($target, $encrypted)) {
                    throw new \RuntimeException('Écriture de sauvegarde chiffrée interrompue.');
                }
            } while (!feof($source));

            fclose($source);
            fclose($target);
            $source = false;
            $target = false;
            chmod($temporaryPath, 0640);
            if (!rename($temporaryPath, $targetPath)) {
                throw new \RuntimeException('Impossible de finaliser la sauvegarde chiffrée.');
            }
        } catch (\Exception $exception) {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($target)) {
                fclose($target);
            }
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
            throw $exception;
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
}
