<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class TradeInPrivateFileStorage
{
    private const MAX_RIB_BYTES = 5_242_880;

    public function __construct(private string $projectDir)
    {
    }

    /** @return array{path: string, originalName: string, size: int, sha256: string} */
    public function storeRib(UploadedFile $file): array
    {
        if (!$file->isValid() || self::MAX_RIB_BYTES < (int) $file->getSize()) {
            throw new \InvalidArgumentException('Le RIB PDF est invalide ou dépasse 5 Mo.');
        }

        $mime = $file->getMimeType();
        if ('application/pdf' !== $mime) {
            throw new \InvalidArgumentException('Le RIB doit être fourni au format PDF.');
        }

        $contents = file_get_contents($file->getPathname());
        if (false === $contents || !str_starts_with($contents, '%PDF-')) {
            throw new \InvalidArgumentException('Le fichier fourni n’est pas un PDF valide.');
        }

        $relativePath = 'var/private/trade-ins/'.bin2hex(random_bytes(24)).'.pdf';
        $absolutePath = $this->projectDir.'/'.$relativePath;
        $directory = dirname($absolutePath);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Impossible de préparer le stockage sécurisé du RIB.');
        }
        if (false === file_put_contents($absolutePath, $contents, LOCK_EX)) {
            throw new \RuntimeException('Impossible d’enregistrer le RIB.');
        }
        @chmod($absolutePath, 0600);

        return ['path' => $relativePath, 'originalName' => $file->getClientOriginalName(), 'size' => strlen($contents), 'sha256' => hash('sha256', $contents)];
    }

    public function storeReceipt(string $pdf): string
    {
        $relativePath = 'var/private/trade-ins/'.bin2hex(random_bytes(24)).'.pdf';
        $absolutePath = $this->projectDir.'/'.$relativePath;
        $directory = dirname($absolutePath);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Impossible de préparer le stockage sécurisé du justificatif.');
        }
        if (false === file_put_contents($absolutePath, $pdf, LOCK_EX)) {
            throw new \RuntimeException('Impossible d’enregistrer le justificatif.');
        }
        @chmod($absolutePath, 0600);

        return $relativePath;
    }

    public function read(string $relativePath): string
    {
        $path = $this->resolvePrivateDocumentPath($relativePath);

        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new \RuntimeException('Document privé illisible.');
        }

        return $contents;
    }

    private function resolvePrivateDocumentPath(string $relativePath): string
    {
        $root = realpath($this->projectDir.'/var/private/trade-ins');
        $path = realpath($this->projectDir.'/'.$relativePath);
        if (false === $root || false === $path || !is_file($path)) {
            throw new \RuntimeException('Document privé introuvable.');
        }

        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
        $normalizedPath = str_replace('\\', '/', $path);
        if (!str_starts_with($normalizedPath, $normalizedRoot.'/')) {
            throw new \RuntimeException('Document privé introuvable.');
        }

        return $path;
    }
}
