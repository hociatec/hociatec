<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Module\Order\Domain\Entity\Order;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class OrderInvoiceDocumentService
{
    public function __construct(
        private readonly OrderInvoiceCalculator $calculator,
        private readonly OrderInvoicePdfService $pdfService,
        private readonly OrderInvoiceXmlService $xmlService,
        private readonly OrderPersistence $persistence,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function ensureGenerated(Order $order): void
    {
        $this->calculator->snapshot($order);
        $totals = $this->calculator->computeTotals($order);
        $baseName = $order->getInvoiceNumber() ?: $order->getNumber();
        $relativeDirectory = 'var/private/invoices';
        $absoluteDirectory = $this->projectDir.'/'.$relativeDirectory;

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new \RuntimeException('Impossible de créer le répertoire des factures.');
        }

        $pdf = $this->pdfService->render($order, $totals);
        $xml = $this->xmlService->render($order, $totals);
        $pdfRelativePath = 'private/invoices/'.$baseName.'.pdf';
        $xmlRelativePath = 'private/invoices/'.$baseName.'.xml';

        $pdfAbsolutePath = $this->projectDir.'/var/'.$pdfRelativePath;
        $xmlAbsolutePath = $this->projectDir.'/var/'.$xmlRelativePath;
        if (false === file_put_contents($pdfAbsolutePath, $pdf) || false === file_put_contents($xmlAbsolutePath, $xml)) {
            throw new \RuntimeException('Impossible d’enregistrer les documents de facture.');
        }
        chmod($pdfAbsolutePath, 0640);
        chmod($xmlAbsolutePath, 0640);

        $order
            ->setInvoicePdfPath($pdfRelativePath)
            ->setInvoiceXmlPath($xmlRelativePath);

        $this->persistence->save($order);
    }

    public function getPdf(Order $order): string
    {
        if (null !== $order->getInvoicePdfPath()) {
            $absolutePath = $this->resolvePath($order->getInvoicePdfPath());
            if (is_file($absolutePath)) {
                $pdf = file_get_contents($absolutePath);
                if (false !== $pdf) {
                    return $pdf;
                }
            }
        }

        $this->ensureGenerated($order);

        $absolutePath = $this->resolvePath((string) $order->getInvoicePdfPath());
        $pdf = file_get_contents($absolutePath);
        if (false === $pdf) {
            throw new \RuntimeException('Impossible de lire la facture PDF générée.');
        }

        return $pdf;
    }

    public function getXml(Order $order): string
    {
        if (null !== $order->getInvoiceXmlPath()) {
            $absolutePath = $this->resolvePath($order->getInvoiceXmlPath());
            if (is_file($absolutePath)) {
                $xml = file_get_contents($absolutePath);
                if (false !== $xml) {
                    return $xml;
                }
            }
        }

        $this->ensureGenerated($order);

        $absolutePath = $this->resolvePath((string) $order->getInvoiceXmlPath());
        $xml = file_get_contents($absolutePath);
        if (false === $xml) {
            throw new \RuntimeException('Impossible de lire la facture XML générée.');
        }

        return $xml;
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
