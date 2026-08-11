<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Infrastructure\Storage;

use App\Module\TradeIn\Application\Port\TradeInPrivateFileStoragePort;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Process;

final readonly class TradeInPrivateFileStorage implements TradeInPrivateFileStoragePort
{
    private const MAX_RIB_BYTES = 5_242_880;
    private const ANTIVIRUS_TIMEOUT_SECONDS = 20;

    public function __construct(private string $projectDir, private ?string $clamScanBinary = null)
    {
    }

    /** @return array{path: string, originalName: string, size: int, sha256: string} */
    public function storeRib(object $file): array
    {
        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException('Le RIB PDF est invalide.');
        }

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
        $this->securePrivateFile($absolutePath);
        $this->scanPdf($absolutePath);

        return ['path' => $relativePath, 'originalName' => $file->getClientOriginalName(), 'size' => strlen($contents), 'sha256' => hash('sha256', $contents)];
    }

    public function storeReceipt(string $pdf): string
    {
        if (!str_starts_with($pdf, '%PDF-')) {
            throw new \InvalidArgumentException('Le justificatif généré n’est pas un PDF valide.');
        }

        $relativePath = 'var/private/trade-ins/'.bin2hex(random_bytes(24)).'.pdf';
        $absolutePath = $this->projectDir.'/'.$relativePath;
        $directory = dirname($absolutePath);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Impossible de préparer le stockage sécurisé du justificatif.');
        }
        if (false === file_put_contents($absolutePath, $pdf, LOCK_EX)) {
            throw new \RuntimeException('Impossible d’enregistrer le justificatif.');
        }
        $this->securePrivateFile($absolutePath);

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

    public function delete(string $relativePath): void
    {
        $path = $this->resolvePrivateDocumentTargetPath($relativePath);
        if (!is_file($path)) {
            return;
        }

        if (!unlink($path) && is_file($path)) {
            throw new \RuntimeException('Impossible de supprimer le document privé.');
        }
    }

    private function resolvePrivateDocumentPath(string $relativePath, bool $mustExist = true): string
    {
        $root = realpath($this->projectDir.'/var/private/trade-ins');
        $absolute = $this->projectDir.'/'.$relativePath;
        $path = $mustExist ? realpath($absolute) : realpath(dirname($absolute));
        if (false === $root || false === $path) {
            throw new \RuntimeException('Document privé introuvable.');
        }

        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
        $normalizedPath = str_replace('\\', '/', $mustExist ? $path : dirname($absolute));
        if (!str_starts_with($normalizedPath, $normalizedRoot.'/')) {
            throw new \RuntimeException('Document privé introuvable.');
        }

        if ($mustExist) {
            if (!is_file($path)) {
                throw new \RuntimeException('Document privé introuvable.');
            }

            return $path;
        }

        return $absolute;
    }

    private function resolvePrivateDocumentTargetPath(string $relativePath): string
    {
        $root = realpath($this->projectDir.'/var/private/trade-ins');
        if (false === $root) {
            throw new \RuntimeException('Document privé introuvable.');
        }

        $absolute = $this->projectDir.'/'.$relativePath;
        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
        $normalizedPath = str_replace('\\', '/', $absolute);
        if (!str_starts_with($normalizedPath, $normalizedRoot.'/')) {
            throw new \RuntimeException('Document privé introuvable.');
        }

        return $absolute;
    }

    private function securePrivateFile(string $absolutePath): void
    {
        if (!chmod($absolutePath, 0600)) {
            unlink($absolutePath);
            throw new \RuntimeException('Impossible de sécuriser le document privé.');
        }
    }

    private function scanPdf(string $absolutePath): void
    {
        $binary = null !== $this->clamScanBinary ? trim($this->clamScanBinary) : '';
        if ('' === $binary) {
            return;
        }

        $process = new Process([$binary, '--no-summary', '--infected', $absolutePath]);
        $process->setTimeout(self::ANTIVIRUS_TIMEOUT_SECONDS);
        $process->run();

        if (0 === $process->getExitCode()) {
            return;
        }

        if (1 === $process->getExitCode()) {
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
            throw new \InvalidArgumentException('Le fichier PDF a été rejeté par l’antivirus.');
        }

        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
        throw new \RuntimeException('Analyse antivirus du document privé impossible.');
    }
}
