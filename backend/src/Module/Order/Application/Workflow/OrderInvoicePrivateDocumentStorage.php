<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\DTO\InvoiceDocument;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class OrderInvoicePrivateDocumentStorage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array{pdf: string, xml: string}
     */
    public function save(string $baseName, InvoiceDocument $document): array
    {
        $this->ensureDirectoryExists();

        $pdfRelativePath = 'private/invoices/'.$baseName.'.pdf';
        $xmlRelativePath = 'private/invoices/'.$baseName.'.xml';
        $pdfAbsolutePath = $this->resolvePath($pdfRelativePath);
        $xmlAbsolutePath = $this->resolvePath($xmlRelativePath);

        if (false === file_put_contents($pdfAbsolutePath, $document->pdf) || false === file_put_contents($xmlAbsolutePath, $document->xml)) {
            throw new \RuntimeException('Impossible d’enregistrer les documents de facture.');
        }

        chmod($pdfAbsolutePath, 0640);
        chmod($xmlAbsolutePath, 0640);

        return [
            'pdf' => $pdfRelativePath,
            'xml' => $xmlRelativePath,
        ];
    }

    public function readPdf(string $storedPath): string
    {
        $pdf = file_get_contents($this->resolvePath($storedPath));
        if (false === $pdf) {
            throw new \RuntimeException('Impossible de lire la facture PDF générée.');
        }

        return $pdf;
    }

    public function readXml(string $storedPath): string
    {
        $xml = file_get_contents($this->resolvePath($storedPath));
        if (false === $xml) {
            throw new \RuntimeException('Impossible de lire la facture XML générée.');
        }

        return $xml;
    }

    public function documentsExist(?string $pdfPath, ?string $xmlPath): bool
    {
        if (null === $pdfPath || null === $xmlPath) {
            return false;
        }

        return is_file($this->resolvePath($pdfPath))
            && is_file($this->resolvePath($xmlPath));
    }

    public function canRead(?string $storedPath): bool
    {
        return null !== $storedPath && is_file($this->resolvePath($storedPath));
    }

    private function ensureDirectoryExists(): void
    {
        $absoluteDirectory = $this->projectDir.'/var/private/invoices';

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new \RuntimeException('Impossible de créer le répertoire des factures.');
        }
    }

    private function resolvePath(string $storedPath): string
    {
        $filename = basename($storedPath);
        if ($filename !== $storedPath && !str_ends_with($storedPath, '/'.$filename)) {
            throw new \RuntimeException('Chemin de facture invalide.');
        }
        if (!preg_match('/^[A-Za-z0-9._-]+\\.(pdf|xml)$/i', $filename)) {
            throw new \RuntimeException('Nom de facture invalide.');
        }

        return $this->projectDir.'/var/private/invoices/'.$filename;
    }
}
